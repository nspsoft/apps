<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrentStockController extends Controller
{
    /**
     * Display a listing of current stocks.
     */
    public function index(Request $request): Response
    {
        $query = ProductStock::query()
            ->with(['product', 'product.category', 'warehouse'])
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->select('product_stocks.*') // Keep primary selection on stocks
            ->when($request->search, function ($q, $search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('warehouse_id', $warehouse);
            })
            ->when($request->category, function ($q, $category) {
                $q->whereHas('product', function ($p) use ($category) {
                    $p->where('category_id', $category);
                });
            })
            ->addSelect([
                'on_order_qty' => \App\Models\PurchaseOrderItem::selectRaw('COALESCE(SUM(qty - qty_received), 0)')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->whereColumn('purchase_order_items.product_id', 'product_stocks.product_id')
                    ->whereColumn('purchase_orders.warehouse_id', 'product_stocks.warehouse_id')
                    ->whereIn('purchase_orders.status', ['ordered', 'partial'])
            ]);

        $stocks = $query->orderBy('products.name')
            ->orderBy('warehouse_id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Stocks/Index', [
            'stocks' => $stocks,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'warehouse_id', 'category']),
        ]);
    }
}
