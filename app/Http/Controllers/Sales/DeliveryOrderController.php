<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeliveryOrder::with(['salesOrder.customer', 'warehouse'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                      ->orWhere('shipping_name', 'like', "%{$search}%")
                      ->orWhereHas('salesOrder', function ($sq) use ($search) {
                          $sq->where('so_number', 'like', "%{$search}%")
                             ->orWhereHas('customer', function ($cq) use ($search) {
                                 $cq->where('name', 'like', "%{$search}%");
                             });
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $deliveryOrders = $query->orderByDesc('delivery_date')
            ->paginate(10)
            ->withQueryString();

        // SOs that can be delivered (Confirmed/Processing and have undelivered items)
        $pendingSalesOrders = \App\Models\SalesOrder::whereIn('status', ['confirmed', 'processing'])
            ->whereHas('items', function($q) {
                $q->whereRaw('qty > qty_delivered');
            })
            ->with('customer')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Sales/Deliveries/Index', [
            'deliveryOrders' => $deliveryOrders,
            'pendingSalesOrders' => $pendingSalesOrders,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function show(DeliveryOrder $deliveryOrder): Response
    {
        $deliveryOrder->load(['salesOrder.customer', 'warehouse', 'items.product', 'items.unit', 'items.salesOrderItem']);

        return Inertia::render('Sales/Deliveries/Show', [
            'deliveryOrder' => $deliveryOrder,
        ]);
    }

    public function print(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit']);
        return view('print.surat-jalan', ['order' => $deliveryOrder]);
    }

    public function publicValidate($uuid)
    {
        $order = DeliveryOrder::with(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit'])
            ->where('id', $uuid)
            ->firstOrFail();

        return view('print.public-delivery-validation', ['order' => $order]);
    }

    public function complete(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status === 'delivered') {
            return back()->with('error', 'Delivery Order is already completed.');
        }

        try {
            \DB::transaction(function () use ($deliveryOrder) {
                $deliveryOrder->complete();
            });
            return back()->with('success', 'Delivery Order completed and Sales Order updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error completing delivery: ' . $e->getMessage());
        }
    }

    public function updateItems(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft deliveries can be updated.');
        }

        $validated = $request->validate([
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'delivery_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:delivery_order_items,id',
            'items.*.qty_delivered' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $deliveryOrder->update([
            'vehicle_number' => $validated['vehicle_number'],
            'driver_name' => $validated['driver_name'],
            'delivery_date' => $validated['delivery_date'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = \App\Models\DeliveryOrderItem::with('salesOrderItem')->find($itemData['id']);
            
            // Validation: Cannot deliver more than SO Qty
            $soItem = $item->salesOrderItem;
            $previouslyDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                ->whereHas('deliveryOrder', function($q) use ($deliveryOrder) {
                    $q->where('status', 'delivered')
                      ->where('id', '!=', $deliveryOrder->id);
                })->sum('qty_delivered');
            
            $allowable = $soItem->qty - $previouslyDelivered;

            if ($itemData['qty_delivered'] > $allowable) {
                return back()->with('error', "Gagal: Pengiriman untuk [{$item->product->name}] melebihi sisa pesanan (Maks: {$allowable}).");
            }

            $item->update([
                'qty_delivered' => $itemData['qty_delivered'],
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Delivery Order updated successfully.');
    }

    public function destroyItem(\App\Models\DeliveryOrderItem $item)
    {
        if ($item->deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only items in draft deliveries can be removed.');
        }

        // Prevent deleting the last item
        if ($item->deliveryOrder->items()->count() <= 1) {
            return back()->with('error', 'A Delivery Order must have at least one item. Cancel the DO if you want to remove all items.');
        }

        $item->delete();

        return back()->with('success', 'Item removed from delivery.');
    }

    public function createInvoice(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'delivered') {
            return back()->with('error', 'Only completed deliveries can be invoiced.');
        }

        // Check if already invoiced (simple check for now)
        // You might want to add a relationship or flag in DO to track this properly

        try {
            return \DB::transaction(function () use ($deliveryOrder) {
                $so = $deliveryOrder->salesOrder;
                
                $invoice = \App\Models\SalesInvoice::create([
                    'company_id' => $deliveryOrder->company_id,
                    'invoice_number' => \App\Models\SalesInvoice::generateInvoiceNumber(),
                    'sales_order_id' => $so->id,
                    'customer_id' => $deliveryOrder->customer_id,
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(30),
                    'status' => 'draft',
                    'subtotal' => 0, // Will be calculated
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => 0,
                    'paid_amount' => 0,
                    'balance' => 0,
                    'created_by' => auth()->id(),
                ]);

                $subtotal = 0;
                foreach ($deliveryOrder->items as $item) {
                    $soItem = $item->salesOrderItem;
                    $itemAmount = $item->qty_delivered * $soItem->unit_price;
                    $discountAmt = $itemAmount * ($soItem->discount_percent / 100);
                    $lineTotal = $itemAmount - $discountAmt;

                    $invoice->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $item->product_id,
                        'qty' => $item->qty_delivered,
                        'unit_id' => $item->unit_id,
                        'unit_price' => $soItem->unit_price,
                        'discount_percent' => $soItem->discount_percent,
                        'discount_amount' => $discountAmt,
                        'subtotal' => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;

                    // Update SO item invoiced qty
                    $soItem->qty_invoiced += $item->qty_delivered;
                    $soItem->save();
                }

                // Update invoice totals
                $taxAmount = $subtotal * ($so->tax_percent / 100);
                $total = $subtotal + $taxAmount;

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'balance' => $total,
                ]);

                return redirect()->route('sales.invoices.index')->with('success', 'Invoice created from Delivery Order.');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft delivery orders can be deleted.');
        }

        try {
            DB::transaction(function () use ($deliveryOrder) {
                // Delete items via Eloquent so events are fired
                foreach ($deliveryOrder->items as $item) {
                    $item->delete();
                }
                $deliveryOrder->delete();
            });
            return back()->with('success', 'Delivery Order deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting delivery order: ' . $e->getMessage());
        }
    }
}
