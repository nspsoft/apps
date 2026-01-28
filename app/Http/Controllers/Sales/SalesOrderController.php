<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesOrder::with(['customer', 'warehouse', 'createdBy'])
            ->withCount('items')
            ->withSum('items as total_qty_ordered', 'qty')
            ->withSum('items as total_qty_delivered', 'qty_delivered')
            ->withSum('items as total_qty_returned', 'qty_returned')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('so_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->customer, function ($q, $customer) {
                $q->where('customer_id', $customer);
            });

        $salesOrders = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Calculate stats based on the same query filters
        $statsQuery = clone $query;
        $stats = [
            'total_qty' => (float) $statsQuery->sum(DB::raw('(select sum(qty) from sales_order_items where sales_order_id = sales_orders.id)')),
            'total_delivered' => (float) $statsQuery->sum(DB::raw('(select sum(qty_delivered) from sales_order_items where sales_order_id = sales_orders.id)')),
            'total_returned' => (float) $statsQuery->sum(DB::raw('(select sum(qty_returned) from sales_order_items where sales_order_id = sales_orders.id)')),
        ];
        $stats['total_balance'] = $stats['total_qty'] - ($stats['total_delivered'] - $stats['total_returned']);

        return Inertia::render('Sales/Orders/Index', [
            'salesOrders' => $salesOrders,
            'stats' => $stats,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'customer']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'processing', 'label' => 'Processing'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => null,
            'soNumber' => SalesOrder::generateSoNumber(),
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->with('unit')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function createFromAi(Request $request): Response
    {
        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => null,
            'soNumber' => SalesOrder::generateSoNumber(),
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->with('unit')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'aiData' => $request->input('data')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_number' => 'required|string|max:30|unique:sales_orders,so_number',
            'customer_po_number' => 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            $so = SalesOrder::create([
                'so_number' => $validated['so_number'],
                'customer_po_number' => $validated['customer_po_number'] ?? null,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => 'draft',
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
                'shipping_name' => $validated['shipping_name'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $so->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);
            }
        });

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order created successfully.');
    }

    public function show(SalesOrder $order): Response
    {
        $order->load(['customer', 'warehouse', 'items.product' => function($q) { $q->withTrashed(); }, 'items.unit', 'createdBy', 'confirmedBy', 'deliveryOrders', 'invoices']);

        return Inertia::render('Sales/Orders/Show', [
            'salesOrder' => $order,
        ]);
    }

    public function edit(SalesOrder $order): Response
    {
        if (!$order->isEditable()) {
            return redirect()->route('sales.orders.show', $order)
                ->with('error', 'This sales order cannot be edited.');
        }

        $order->load(['items.product', 'items.unit']);

        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => $order,
            'soNumber' => $order->so_number,
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->with('unit')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SalesOrder $order)
    {
        if (!$order->isEditable()) {
            return back()->with('error', 'This sales order cannot be edited.');
        }

        $validated = $request->validate([
            'customer_po_number' => 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:sales_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $order->update([
                'customer_po_number' => $validated['customer_po_number'] ?? null,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
                'shipping_name' => $validated['shipping_name'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
            ]);

            $existingIds = collect($validated['items'])->pluck('id')->filter()->all();
            
            // Delete removed items via Eloquent for logging
            $order->items()->whereNotIn('id', $existingIds)->get()->each(function($item) {
                $item->delete();
            });

            foreach ($validated['items'] as $item) {
                if (isset($item['id'])) {
                    $order->items()->where('id', $item['id'])->update([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                } else {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                }
            }

            $order->refresh();
            $order->calculateTotals();
        });

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order updated successfully.');
    }

    public function confirm(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be confirmed.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Sales Order confirmed.');
    }

    public function cancel(SalesOrder $order)
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Sales Order cancelled.');
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $order->delete();

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order deleted successfully.');
    }

    public function updateItemQty(Request $request, \App\Models\SalesOrderItem $item)
    {
        $validated = $request->validate([
            'qty' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQty = $item->qty;
        $newQty = $validated['qty'];

        if ($oldQty == $newQty) {
            return back();
        }

        DB::transaction(function () use ($item, $newQty, $validated) {
            $item->update(['qty' => $newQty]);
            
            // Recalculate totals for the parent SO
            $item->salesOrder->calculateTotals();

            // Explicitly log the reason if provided
            if (!empty($validated['reason'])) {
                activity()
                    ->performedOn($item)
                    ->causedBy(auth()->user())
                    ->withProperties(['old_qty' => $oldQty, 'new_qty' => $newQty, 'reason' => $validated['reason']])
                    ->log("Adjusted quantity from {$oldQty} to {$newQty}. Reason: " . $validated['reason']);
            }
        });

        return back()->with('success', 'Quantity updated and logged successfully.');
    }


    public function createInvoice(SalesOrder $order)
    {
         $invoice = \App\Models\SalesInvoice::create([
             'company_id' => $order->company_id ?? 1,
             'invoice_number' => \App\Models\SalesInvoice::generateInvoiceNumber(),
             'sales_order_id' => $order->id,
             'customer_id' => $order->customer_id,
             'invoice_date' => now(),
             'due_date' => now()->addDays(30),
             'status' => 'draft',
             'subtotal' => $order->subtotal,
             'tax_amount' => $order->tax_amount,
             'discount_amount' => $order->discount_amount,
             'total' => $order->total,
             'paid_amount' => 0,
             'balance' => $order->total,
             'created_by' => auth()->id(),
         ]);
         
         foreach($order->items as $item) {
             $remainingToInvoice = $item->qty - $item->qty_invoiced;
             
             if ($remainingToInvoice <= 0) continue;

             $invoice->items()->create([
                 'sales_order_item_id' => $item->id,
                 'product_id' => $item->product_id,
                 'qty' => $remainingToInvoice,
                 'unit_id' => $item->unit_id,
                 'unit_price' => $item->unit_price,
                 'discount_percent' => $item->discount_percent,
                 'discount_amount' => $item->discount_amount * ($remainingToInvoice / $item->qty),
                 'subtotal' => $item->subtotal * ($remainingToInvoice / $item->qty),
             ]);

             $item->qty_invoiced += $remainingToInvoice;
             $item->save();
         }
         
         $invoice->calculateTotals();
         
         return redirect()->route('sales.invoices.index')->with('success', 'Invoice created.');
    }
}
