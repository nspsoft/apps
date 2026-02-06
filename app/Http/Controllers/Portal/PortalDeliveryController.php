<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PortalDeliveryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $deliveries = GoodsReceipt::where('supplier_id', $user->supplier_id)
            ->with(['purchaseOrder', 'warehouse'])
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return Inertia::render('Portal/Deliveries/Index', [
            'deliveries' => $deliveries
        ]);
    }

    public function create(PurchaseOrder $order)
    {
        $user = auth()->user();

        // Security check
        if ($order->supplier_id != $user->supplier_id) {
            abort(403);
        }

        // Only allow creating delivery if not already fully received
        if (!$order->canReceive()) {
            return back()->with('error', 'This order cannot accept new deliveries.');
        }

        $order->load(['items.product', 'items.unit', 'warehouse']);

        // Calculate remaining quantity accounting for dispatched items (not yet received)
        foreach ($order->items as $item) {
            $dispatchedQty = \App\Models\GoodsReceiptItem::where('purchase_order_item_id', $item->id)
                ->whereHas('goodsReceipt', function ($q) {
                    $q->where('status', \App\Models\GoodsReceipt::STATUS_DISPATCHED);
                })
                ->sum('qty_ordered');

            $item->qty_remaining = max(0, $item->qty - $item->qty_received - $dispatchedQty);
        }

        return Inertia::render('Portal/Deliveries/Create', [
            'order' => $order
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'delivery_note_number' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'driver_name' => 'nullable|string|max:255',
            'truck_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_delivery' => 'required|numeric|min:0',
        ]);

        $po = PurchaseOrder::findOrFail($request->purchase_order_id);

        if ($po->supplier_id != $user->supplier_id) {
            abort(403);
        }

        DB::transaction(function () use ($request, $po, $user) {
            // Create Goods Receipt (as Dispatch/Surat Jalan)
            $gr = new GoodsReceipt();
            $gr->company_id = $po->company_id;
            $gr->grn_number = GoodsReceipt::generateGrnNumber();
            $gr->purchase_order_id = $po->id;
            $gr->supplier_id = $po->supplier_id;
            $gr->warehouse_id = $po->warehouse_id;
            $gr->receipt_date = $request->receipt_date;
            $gr->delivery_note_number = $request->delivery_note_number;
            $gr->driver_name = $request->driver_name;
            $gr->truck_number = $request->truck_number;
            $gr->status = GoodsReceipt::STATUS_DISPATCHED; // STATUS: ON THE WAY
            $gr->notes = $request->notes;
            $gr->save();

            // Create Items
            foreach ($request->items as $itemData) {
                if ($itemData['qty_delivery'] > 0) {
                    $poItem = $po->items()->find($itemData['id']);
                    
                    if ($poItem) {
                        $gr->items()->create([
                            'goods_receipt_id' => $gr->id,
                            'purchase_order_item_id' => $poItem->id,
                            'product_id' => $poItem->product_id,
                            'product_name' => $poItem->product->name, // Snapshot name
                            'qty_received' => 0, // Not received yet by warehouse
                            'qty_ordered' => $itemData['qty_delivery'], // Use this field temporarily to store dispatched qty, or add a new column if needed. 
                                                                    // For now we assume warehouse will update "qty_received" later.
                                                                    // Actually, we should probably treat "qty_received" as "qty_shipped" for dispatched status?
                                                                    // Let's use qty_received as the shipped amount for 'dispatched' status, 
                                                                    // and warehouse can later verify/edit it.
                            'unit_cost' => $poItem->unit_price,
                            'subtotal' => $itemData['qty_delivery'] * $poItem->unit_price,
                            'location_id' => \App\Models\Location::where('warehouse_id', $po->warehouse_id)->value('id') ?? 1, // Default location
                        ]);
                    }
                }
            }
        });

        return redirect()->route('portal.deliveries.index')->with('success', 'Delivery Note created successfully.');
    }

    public function show(GoodsReceipt $delivery)
    {
        $user = auth()->user();

        if ($delivery->supplier_id != $user->supplier_id) {
            abort(403);
        }

        $delivery->load(['items.product', 'items.unit', 'purchaseOrder', 'warehouse']);

        return Inertia::render('Portal/Deliveries/Show', [
            'delivery' => $delivery
        ]);
    }

    public function print(GoodsReceipt $delivery)
    {
        $user = auth()->user();

        if ($delivery->supplier_id != $user->supplier_id) {
            abort(403);
        }

        $delivery->load(['items.product.unit', 'purchaseOrder', 'warehouse', 'supplier']);
        
        return Inertia::render('Portal/Deliveries/PrintTypes/StandardDN', [
            'delivery' => $delivery,
            'company' => \App\Models\Company::find($delivery->company_id),
            'supplier' => $delivery->supplier,
            'verification_url' => route('purchasing.receipts.public-validate', $delivery->id),
        ]);
    }
}
