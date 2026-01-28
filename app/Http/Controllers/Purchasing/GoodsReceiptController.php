<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GoodsReceipt::with(['purchaseOrder', 'supplier', 'warehouse', 'receivedBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('grn_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $receipts = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Purchasing/Receipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'inspected', 'label' => 'Inspected'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $purchaseOrder = null;
        if ($request->po_id) {
            $purchaseOrder = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])->find($request->po_id);
        }

        return Inertia::render('Purchasing/Receipts/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['ordered', 'partial'])->with('supplier')->orderByDesc('created_at')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->with('unit')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_ordered' => 'required|numeric|min:0',
            'items.*.qty_received' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        // Quantity validation against PO
        if ($validated['purchase_order_id']) {
            $po = PurchaseOrder::with('items')->find($validated['purchase_order_id']);
            foreach ($validated['items'] as $item) {
                $poItem = $po->items->where('id', $item['po_item_id'])->first();
                if ($poItem) {
                    $remaining = $poItem->qty - ($poItem->qty_received - $poItem->qty_returned);
                    $allowedMax = round($remaining * 1.1); // 10% tolerance rounded to whole number
                    if ($item['qty_received'] > $allowedMax + 0.0001) { 
                        return back()->with('error', "Cannot receive more than 110% of remaining quantity for product: {$poItem->product->name} (Remaining: {$remaining}, Max allowed: " . $allowedMax . ")")->withInput();
                    }
                }
            }
        }

        DB::transaction(function () use ($validated) {
            $receipt = GoodsReceipt::create([
                'grn_number' => GoodsReceipt::generateGrnNumber(),
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'receipt_date' => $validated['receipt_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'received_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $receipt->items()->create([
                    'purchase_order_item_id' => $item['po_item_id'],
                    'product_id' => $item['product_id'],
                    'qty_ordered' => $item['qty_ordered'],
                    'qty_received' => $item['qty_received'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }
        });

        return redirect()->route('purchasing.receipts.index')
            ->with('success', 'Goods Receipt created successfully.');
    }

    public function show(GoodsReceipt $receipt): Response
    {
        $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'receivedBy']);

        return Inertia::render('Purchasing/Receipts/Show', [
            'receipt' => $receipt,
        ]);
    }

    public function complete(GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return back()->with('error', 'Receipt is already completed.');
        }

        $receipt->complete();

        return back()->with('success', 'Goods Receipt completed and stock updated.');
    }

    public function destroy(GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return back()->with('error', 'Completed receipts cannot be deleted.');
        }

        $receipt->delete();

        return redirect()->route('purchasing.receipts.index')
            ->with('success', 'Goods Receipt deleted successfully.');
    }

    public function getPoItems(PurchaseOrder $order)
    {
        $order->load(['items.product', 'goodsReceipts.items']);

        $items = $order->items->map(function ($item) use ($order) {
            // Total Received (Sum from GR items)
            $receivedQty = $order->goodsReceipts->flatMap->items
                ->where('product_id', $item->product_id)
                ->sum('qty_received');
            
            // Total Returned (Sum from Return items linked to PO)
            $returnedQty = $item->qty_returned; // Using the cached field in PO Item

            // Effective Pending = Ordered - (Received - Returned)
            $remainingQty = max(0, $item->qty - ($receivedQty - $returnedQty));

            if ($remainingQty <= 0) {
                return null;
            }

            return [
                'po_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'qty_ordered' => $item->qty,
                'qty_received_total' => $receivedQty,
                'remaining_qty' => $remainingQty,
                'unit_cost' => $item->unit_price, // Default to PO price
            ];
        })->filter()->values();

        return response()->json([
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'items' => $items,
        ]);
    }

    public function print(GoodsReceipt $receipt)
    {
        return view('print.goods-receipt', [
            'receipt' => $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product.unit', 'receivedBy'])
        ]);
    }

    public function publicValidate($id)
    {
        $receipt = GoodsReceipt::with(['supplier', 'warehouse', 'items.product.unit', 'receivedBy'])
            ->findOrFail($id);

        return view('print.public-goods-receipt-validation', [
            'receipt' => $receipt
        ]);
    }
}
