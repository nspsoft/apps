<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SalesForecast;
use App\Imports\SalesForecastImport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SalesForecastController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'period');
        $direction = $request->input('direction', 'desc');

        $query = SalesForecast::with(['customer', 'product.unit'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->when($request->month, function ($query, $month) {
                $query->whereDate('period', $month . '-01');
            });

        if ($sort === 'customer_name') {
            $query->join('customers', 'sales_forecasts.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('sales_forecasts.*');
        } elseif ($sort === 'product_name') {
            $query->join('products', 'sales_forecasts.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction)
                  ->select('sales_forecasts.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $forecasts = $query->paginate(10)->withQueryString();

        // Calculate actual Qty for the period
        $forecasts->getCollection()->transform(function ($forecast) {
            $startOfMonth = \Carbon\Carbon::parse($forecast->period)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($forecast->period)->endOfMonth();

            $actualQty = \App\Models\SalesOrderItem::whereHas('salesOrder', function($q) use ($startOfMonth, $endOfMonth, $forecast) {
                    $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                      ->where('customer_id', $forecast->customer_id)
                      ->whereNotIn('status', ['cancelled']);
                })
                ->where('product_id', $forecast->product_id)
                ->sum('qty');

            $forecast->qty_actual = (float) $actualQty;
            return $forecast;
        });

        return Inertia::render('Sales/Planning/Forecast/Index', [
            'forecasts' => $forecasts,
            'filters' => $request->only(['search', 'month', 'sort', 'direction']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new SalesForecastImport, $request->file('file'));
            return back()->with('success', 'Sales Forecast imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}
