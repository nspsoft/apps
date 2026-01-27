<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    public function inventoryBalance(Request $request): Response
    {
        $warehouseId = $request->warehouse_id;

        $query = Product::with(['unit', 'category']);

        if ($warehouseId) {
            $query->with(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        } else {
            $query->with('stocks.warehouse');
        }

        $products = $query->orderBy('name')->get()
            ->map(function ($product) use ($warehouseId) {
                if ($warehouseId) {
                    $stock = $product->stocks->first();
                    $qty = $stock ? $stock->qty_on_hand : 0;
                    $value = $stock ? $stock->value : 0;
                } else {
                    $qty = $product->stocks->sum('qty_on_hand');
                    $value = $product->stocks->sum(function ($s) {
                        return $s->qty_on_hand * $s->avg_cost;
                    });
                }

                return [
                    'code' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit->symbol ?? 'pcs',
                    'category' => $product->category->name ?? '-',
                    'qty' => $qty,
                    'value' => $value,
                ];
            });

        return Inertia::render('Reports/InventoryBalance', [
            'data' => $products,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['warehouse_id']),
            'date' => now()->format('d M Y H:i'),
        ]);
    }

    public function stockCard(Request $request): Response
    {
        $movements = collect([]);
        $product = null;
        $balance = 0;

        if ($request->product_id && $request->warehouse_id) {
            $product = Product::find($request->product_id);

            $movements = StockMovement::with(['reference'])
                ->where('product_id', $request->product_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereBetween('created_at', [
                    $request->start_date ?? now()->startOfMonth(),
                    $request->end_date ?? now()->endOfMonth(),
                ])
                ->orderBy('created_at')
                ->get();
        }

        return Inertia::render('Reports/StockCard', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['product_id', 'warehouse_id', 'start_date', 'end_date']),
        ]);
    }

    public function salesSummary(Request $request): Response
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $customerId = $request->customer_id;

        $query = SalesOrder::with('customer')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $orders = $query->orderBy('order_date')->get();

        return Inertia::render('Reports/SalesSummary', [
            'data' => $orders,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'customer_id' => $customerId,
            ],
            'date' => now()->format('d M Y H:i'),
        ]);
    }

    public function purchaseSummary(Request $request): Response
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $supplierId = $request->supplier_id;

        $query = PurchaseOrder::with('supplier')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled']);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $orders = $query->orderBy('order_date')->get();

        return Inertia::render('Reports/PurchaseSummary', [
            'data' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'supplier_id' => $supplierId,
            ],
            'date' => now()->format('d M Y H:i'),
        ]);
    }

    public function productionSummary(Request $request): Response
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $productId = $request->product_id;

        $query = WorkOrder::with('product')
            ->whereBetween('planned_start', [$startDate, $endDate]);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $orders = $query->orderBy('planned_start')->get();

        return Inertia::render('Reports/ProductionSummary', [
            'data' => $orders,
            'products' => Product::where('product_type', 'finished_good')->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'product_id' => $productId,
            ],
            'date' => now()->format('d M Y H:i'),
        ]);
    }

    public function exportSales(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $query = SalesOrder::with('customer')->whereBetween('order_date', [$startDate, $endDate]);
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        $data = $query->get();

        return Excel::download(new ReportExport(
            $data,
            ['Date', 'Number', 'Customer', 'Status', 'Total', 'Tax'],
            fn ($row) => [$row->order_date, $row->number, $row->customer->name, $row->status, $row->total, $row->tax]
        ), 'sales_report.xlsx');
    }

    public function exportPurchase(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $query = PurchaseOrder::with('supplier')->whereBetween('order_date', [$startDate, $endDate]);
        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        $data = $query->get();

        return Excel::download(new ReportExport(
            $data,
            ['Date', 'Number', 'Supplier', 'Status', 'Total', 'Tax'],
            fn ($row) => [$row->order_date, $row->number, $row->supplier->name, $row->status, $row->total, $row->tax]
        ), 'purchase_report.xlsx');
    }

    public function exportProduction(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $query = WorkOrder::with('product')->whereBetween('planned_start', [$startDate, $endDate]);
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        $data = $query->get();

        return Excel::download(new ReportExport(
            $data,
            ['Number', 'Product', 'Start', 'Status', 'Planned Qty', 'Actual Qty'],
            fn ($row) => [$row->number, $row->product->name, $row->planned_start, $row->status, $row->planned_qty, $row->actual_qty]
        ), 'production_report.xlsx');
    }
}
