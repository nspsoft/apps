<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                ['value' => 'picking', 'label' => 'Picking'],
                ['value' => 'packed', 'label' => 'Packed'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered (Driver)'],
                ['value' => 'completed', 'label' => 'Completed (Admin)'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $salesOrder = null;
        if ($request->sales_order_id) {
            $salesOrder = SalesOrder::with(['customer', 'warehouse'])->find($request->sales_order_id);
        }

        $pendingSalesOrders = SalesOrder::whereIn('status', ['confirmed', 'processing', 'partial'])
            ->with(['customer', 'items.deliveryOrderItems.deliveryOrder'])
            ->orderByDesc('id')
            ->get()
            ->filter(function($so) {
                // Only show SOs that have at least one item with remaining_qty > 0
                return $so->items->some(fn($item) => $item->remaining_qty > 0);
            })
            ->take(100)
            ->values();

        return Inertia::render('Sales/Deliveries/Create', [
            'salesOrder' => $salesOrder,
            'salesOrders' => $pendingSalesOrders,
            'vehicles' => Vehicle::where('is_active', true)->orderBy('license_plate')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function getSoItems(SalesOrder $sales_order)
    {
        $sales_order->load(['items.product', 'items.unit', 'items.deliveryOrderItems.deliveryOrder']);

        $items = $sales_order->items->map(function ($item) {
            $remaining = $item->remaining_qty;
            
            if ($remaining <= 0) return null;

            return [
                'sales_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'sku' => $item->product->sku,
                'qty_ordered' => (float) $item->qty,
                'remaining' => (float) $remaining,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name,
            ];
        })->filter()->values();

        return response()->json([
            'customer_id' => $sales_order->customer_id,
            'warehouse_id' => $sales_order->warehouse_id,
            'shipping_address' => $sales_order->shipping_address,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_date' => 'required|date',
            'vehicle_id' => 'nullable',
            'vehicle_number' => 'required_if:vehicle_id,manual',
            'driver_name' => 'required',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.qty_delivered' => 'required|numeric|gt:0',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $order = SalesOrder::findOrFail($request->sales_order_id);
                
                // Generate DO Number
                $lastDO = DeliveryOrder::orderBy('id', 'desc')->first();
                $number = 'DO/' . date('Ymd') . '/' . str_pad(($lastDO ? $lastDO->id : 0) + 1, 4, '0', STR_PAD_LEFT);

                $do = DeliveryOrder::create([
                    'do_number' => $number,
                    'sales_order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'warehouse_id' => $request->warehouse_id,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_id' => $request->vehicle_id === 'manual' ? null : $request->vehicle_id,
                    'vehicle_number' => $request->vehicle_number,
                    'driver_name' => $request->driver_name,
                    'shipping_address' => $request->shipping_address ?? $order->shipping_address,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);

                foreach ($request->items as $itemData) {
                    $soItem = SalesOrderItem::findOrFail($itemData['sales_order_item_id']);
                    
                    // Cross-check allowable again in backend
                    $allowable = $soItem->remaining_qty;
                    if ($itemData['qty_delivered'] > $allowable) {
                        throw new \Exception("Kuantitas untuk [{$soItem->product->name}] melebihi sisa pesanan (Maks: {$allowable}).");
                    }

                    $do->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $soItem->product_id,
                        'qty_ordered' => $soItem->qty,
                        'qty_delivered' => $itemData['qty_delivered'],
                        'unit_id' => $soItem->unit_id,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                return redirect()->route('sales.deliveries.show', $do->id)->with('success', 'Delivery Order created successfully.');
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat DO: ' . $e->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder): Response
    {
        $deliveryOrder->load(['salesOrder.customer', 'warehouse', 'items.product', 'items.unit', 'items.salesOrderItem']);

        return Inertia::render('Sales/Deliveries/Show', [
            'deliveryOrder' => $deliveryOrder,
            'vehicles' => Vehicle::where('is_active', true)->orderBy('license_plate')->get(),
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
        if ($deliveryOrder->status === DeliveryOrder::STATUS_COMPLETED) {
            return back()->with('error', 'Delivery Order is already completed.');
        }

        try {
            \DB::transaction(function () use ($deliveryOrder) {
                $deliveryOrder->complete();
                // Override status if complete() set it to delivered, ensure it is completed
                $deliveryOrder->status = DeliveryOrder::STATUS_COMPLETED;
                $deliveryOrder->save();
            });
            return back()->with('success', 'Delivery Order verified, stock deducted, and Sales Order updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error completing delivery: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,picking,packed,shipped,delivered,completed',
        ]);

        $status = $validated['status'];

        // Logic restrictions
        if ($status === DeliveryOrder::STATUS_COMPLETED) {
            // Must use the complete() method via the main endpoint or call it here
            // But usually Drag & Drop to Completed should trigger the same logic
            return $this->complete($deliveryOrder);
        }

        $deliveryOrder->status = $status;
        $deliveryOrder->save();

        return back()->with('success', "Delivery Order status updated to {$status}.");
    }

    public function updateItems(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft deliveries can be updated.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'delivery_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:delivery_order_items,id',
            'items.*.qty_delivered' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $deliveryOrder->update([
            'vehicle_id' => $validated['vehicle_id'],
            'vehicle_number' => $validated['vehicle_number'],
            'driver_name' => $validated['driver_name'],
            'delivery_date' => $validated['delivery_date'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = \App\Models\DeliveryOrderItem::with('salesOrderItem')->find($itemData['id']);
            
            // Validation: Cannot deliver more than SO Qty
            $soItem = $item->salesOrderItem;
            
            // Total Net Delivered (Status: Delivered - status: Returned)
            $deliveredNet = $soItem->qty_delivered - $soItem->qty_returned;
            
            // Total Reserved by OTHER active DOs (Status: Draft/Picking/Packed/Shipped)
            $reservedByOthers = (float) $soItem->deliveryOrderItems()
                ->whereHas('deliveryOrder', function ($query) use ($deliveryOrder) {
                    $query->whereNotIn('status', ['delivered', 'cancelled'])
                          ->where('id', '!=', $deliveryOrder->id);
                })
                ->sum('qty_delivered');
            
            $allowable = $soItem->qty - $deliveredNet - $reservedByOthers;

            if ($itemData['qty_delivered'] > $allowable) {
                return back()->with('error', "Gagal: Pengiriman untuk [{$item->product->name}] melebihi sisa pesanan + reservasi DO lain (Maks: {$allowable}).");
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
