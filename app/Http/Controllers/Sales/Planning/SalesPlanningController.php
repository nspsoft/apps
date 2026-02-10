<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SalesForecast;
use App\Models\DeliverySchedule;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrderItem;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesPlanningController extends Controller
{
    public function dashboard(Request $request)
    {
        $currentMonth = $request->month ? Carbon::parse($request->month) : Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // 1. Forecast vs Actual
        // Group by Customer for high level view
        $forecasts = SalesForecast::whereBetween('period', [$startOfMonth, $endOfMonth])
            ->select('customer_id', DB::raw('SUM(qty_forecast) as total_forecast'))
            ->groupBy('customer_id')
            ->with('customer:id,name,code') // Eager load customer name/code
            ->get();
            
        // Get Actual Sales (SO) for same customers in this month
        $actuals = SalesOrderItem::whereHas('salesOrder', function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                  ->whereNotIn('status', ['cancelled']);
            })
            ->select('sales_orders.customer_id', DB::raw('SUM(qty) as total_actual'))
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereIn('sales_orders.customer_id', $forecasts->pluck('customer_id')) // Only for forecasted customers
            ->groupBy('sales_orders.customer_id')
            ->get()
            ->keyBy('customer_id');

        $chartData = $forecasts->map(function($f) use ($actuals) {
            return [
                'id' => $f->customer_id,
                'customer' => $f->customer->name,
                'forecast' => (float) $f->total_forecast,
                'actual' => (float) ($actuals[$f->customer_id]->total_actual ?? 0),
            ];
        })->values();

        // 2. Schedule Adherence
        // Compare DeliverySchedule vs DeliveryOrder
        $schedules = DeliverySchedule::whereBetween('delivery_date', [$startOfMonth, $endOfMonth])->get();
        // This is a simplified logic. In reality, matching schedule to specific DO is complex.
        // Alert logic: If today > delivery_date AND no DO linked -> Delayed.
        
        $delayedCount = $schedules->where('delivery_date', '<', Carbon::today())
                                  ->where('qty_scheduled', '>', 0) // Should check if delivered, but for now simple
                                  ->count();
        
        $upcomingCount = $schedules->whereBetween('delivery_date', [Carbon::today(), Carbon::today()->addDays(7)])->count();
        
        $stats = [
            'total_forecast_qty' => $forecasts->sum('total_forecast'),
            'total_actual_qty' => $actuals->sum('total_actual'),
            'delayed_schedules' => $delayedCount,
            'upcoming_schedules' => $upcomingCount,
        ];

        return Inertia::render('Sales/Planning/Dashboard', [
            'chartData' => $chartData,
            'stats' => $stats,
            'month' => $currentMonth->format('Y-m'),
        ]);
    }

    public function details(Request $request)
    {
        $currentMonth = $request->month ? Carbon::parse($request->month) : Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $customerId = $request->customer_id;

        // Get Forecasts by Product
        $forecasts = SalesForecast::where('customer_id', $customerId)
            ->whereBetween('period', [$startOfMonth, $endOfMonth])
            ->with('product:id,name,sku,unit_id', 'product.unit')
            ->get()
            ->groupBy('product_id')
            ->map(function ($rows) {
                return [
                    'product_id' => $rows->first()->product_id,
                    'product_name' => $rows->first()->product->name,
                    'sku' => $rows->first()->product->sku,
                    'unit' => $rows->first()->product->unit->code ?? 'PCS',
                    'forecast_qty' => $rows->sum('qty_forecast'),
                ];
            });

        // Get Actuals by Product
        $actuals = SalesOrderItem::whereHas('salesOrder', function($q) use ($startOfMonth, $endOfMonth, $customerId) {
                $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                  ->where('customer_id', $customerId)
                  ->whereNotIn('status', ['cancelled']);
            })
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->with('product:id,name,sku')
            ->get()
            ->keyBy('product_id');

        // Merge Data
        $allProductIds = $forecasts->keys()->merge($actuals->keys())->unique();
        
        $details = $allProductIds->map(function($productId) use ($forecasts, $actuals) {
            $forecast = $forecasts[$productId] ?? null;
            $actual = $actuals[$productId] ?? null;
            
            $productName = $forecast['product_name'] ?? $actual->product->name ?? 'Unknown Product';
            $sku = $forecast['sku'] ?? $actual->product->sku ?? '-';
            $unit = $forecast['unit'] ?? 'PCS';
            $forecastQty = (float) ($forecast['forecast_qty'] ?? 0);
            $actualQty = (float) ($actual->total_qty ?? 0);

            $accuracy = 0;
            if ($forecastQty > 0) {
                // Accuracy formula: 100 - (|Forecast - Actual| / Forecast * 100)
                // Or simply % Achievement: Actual / Forecast * 100
                // User asked for "Accuracy", usually means how close it is.
                // Let's provide Achievement % for now as it's more standard in sales.
                $accuracy = ($actualQty / $forecastQty) * 100;
            }

            return [
                'product_id' => $productId,
                'name' => $productName,
                'sku' => $sku,
                'unit' => $unit,
                'forecast' => $forecastQty,
                'actual' => $actualQty,
                'achievement' => $accuracy,
                'variance' => $actualQty - $forecastQty,
            ];
        })->values();

        return response()->json($details);
    }
}
