<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SalesForecast;
use App\Models\DeliverySchedule;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesInvoice;
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
        
        // Previous month for trend comparison
        $prevStart = $currentMonth->copy()->subMonth()->startOfMonth();
        $prevEnd = $currentMonth->copy()->subMonth()->endOfMonth();

        // ════════════════════════════════════════════
        // 1. FORECAST vs ACTUAL (by Customer)
        // ════════════════════════════════════════════
        $forecasts = SalesForecast::whereBetween('period', [$startOfMonth, $endOfMonth])
            ->select('customer_id', DB::raw('SUM(qty_forecast) as total_forecast'))
            ->groupBy('customer_id')
            ->with('customer:id,name,code')
            ->get();
            
        $actuals = SalesOrderItem::whereHas('salesOrder', function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                  ->whereNotIn('status', ['cancelled']);
            })
            ->select('sales_orders.customer_id', DB::raw('SUM(qty) as total_actual'))
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereIn('sales_orders.customer_id', $forecasts->pluck('customer_id'))
            ->groupBy('sales_orders.customer_id')
            ->get()
            ->keyBy('customer_id');

        // Get Deliveries (DO) for same customers in this month
        $deliveries = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('delivery_date', [$startOfMonth, $endOfMonth])
                  ->whereNotIn('status', ['draft', 'cancelled']); // Only delivered/shipped
            })
            ->select('delivery_orders.customer_id', DB::raw('SUM(qty_delivered) as total_delivery'))
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->whereIn('delivery_orders.customer_id', $forecasts->pluck('customer_id'))
            ->groupBy('delivery_orders.customer_id')
            ->get()
            ->keyBy('customer_id');

        $chartData = $forecasts->map(function($f) use ($actuals, $deliveries) {
            $forecast = (float) $f->total_forecast;
            $actual = (float) ($actuals[$f->customer_id]->total_actual ?? 0);
            $delivery = (float) ($deliveries[$f->customer_id]->total_delivery ?? 0);
            
            $achievement = $forecast > 0 ? round(($actual / $forecast) * 100, 1) : 0;
            return [
                'id' => $f->customer_id,
                'customer' => $f->customer->name ?? 'Unknown',
                'forecast' => $forecast,
                'actual' => $actual,
                'delivery' => $delivery,
                'achievement' => $achievement,
                'gap' => $actual - $forecast,
            ];
        })->values();

        // ════════════════════════════════════════════
        // 2. PREVIOUS MONTH ACTUALS (for trend arrows)
        // ════════════════════════════════════════════
        $prevForecasts = SalesForecast::whereBetween('period', [$prevStart, $prevEnd])
            ->select(DB::raw('SUM(qty_forecast) as total'))
            ->first();

        $prevActuals = SalesOrderItem::whereHas('salesOrder', function($q) use ($prevStart, $prevEnd) {
                $q->whereBetween('order_date', [$prevStart, $prevEnd])
                  ->whereNotIn('status', ['cancelled']);
            })
            ->select(DB::raw('SUM(qty) as total'))
            ->first();

        // ════════════════════════════════════════════
        // 3. REVENUE (SO total value)
        // ════════════════════════════════════════════
        $revenueThisMonth = SalesOrder::whereBetween('order_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $revenuePrevMonth = SalesOrder::whereBetween('order_date', [$prevStart, $prevEnd])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        // ════════════════════════════════════════════
        // 4. DELIVERY SCHEDULES
        // ════════════════════════════════════════════
        $schedules = DeliverySchedule::whereBetween('delivery_date', [$startOfMonth, $endOfMonth])->get();
        
        $delayedCount = $schedules->where('delivery_date', '<', Carbon::today())
                                  ->where('qty_scheduled', '>', 0)
                                  ->count();
        
        $upcomingCount = $schedules->whereBetween('delivery_date', [Carbon::today(), Carbon::today()->addDays(7)])->count();

        // Upcoming 7-day timeline (grouped by date)
        $deliveryTimeline = DeliverySchedule::whereBetween('delivery_date', [Carbon::today(), Carbon::today()->addDays(6)])
            ->with(['customer:id,name', 'product:id,name,sku'])
            ->orderBy('delivery_date')
            ->get()
            ->groupBy(function($item) {
                return $item->delivery_date->format('Y-m-d');
            })
            ->map(function($group, $date) {
                return [
                    'date' => $date,
                    'day_label' => Carbon::parse($date)->format('D'),
                    'date_label' => Carbon::parse($date)->format('d M'),
                    'is_today' => Carbon::parse($date)->isToday(),
                    'count' => $group->count(),
                    'total_qty' => $group->sum('qty_scheduled'),
                    'items' => $group->take(3)->map(fn($s) => [
                        'customer' => $s->customer->name ?? '-',
                        'product' => $s->product->name ?? '-',
                        'qty' => (float) $s->qty_scheduled,
                    ])->values(),
                ];
            })->values();

        // ════════════════════════════════════════════
        // 5. TOP 5 & BOTTOM 5 CUSTOMERS
        // ════════════════════════════════════════════
        $ranked = $chartData->sortByDesc('achievement')->values();
        $topCustomers = $ranked->take(5)->values();
        $bottomCustomers = $ranked->sortBy('achievement')->take(5)->values();

        // ════════════════════════════════════════════
        // 6. INVOICE / AR SUMMARY
        // ════════════════════════════════════════════
        $totalAR = SalesInvoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->sum('balance');
        
        $overdueInvoices = SalesInvoice::where('status', 'overdue')
            ->orWhere(function($q) {
                $q->whereIn('status', ['sent', 'partial'])
                  ->where('due_date', '<', Carbon::today());
            })
            ->count();

        $collectionThisMonth = SalesInvoice::whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'paid')
            ->sum('total');

        $openInvoicesCount = SalesInvoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->count();

        // ════════════════════════════════════════════
        // 7. SO STATUS BREAKDOWN (this month)
        // ════════════════════════════════════════════
        $soStatusBreakdown = SalesOrder::whereBetween('order_date', [$startOfMonth, $endOfMonth])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ════════════════════════════════════════════
        // BUILD STATS
        // ════════════════════════════════════════════
        $totalForecast = $forecasts->sum('total_forecast');
        $totalActual = $actuals->sum('total_actual');

        $stats = [
            'total_forecast_qty' => (float) $totalForecast,
            'total_actual_qty' => (float) $totalActual,
            'achievement_pct' => $totalForecast > 0 ? round(($totalActual / $totalForecast) * 100, 1) : 0,
            'delayed_schedules' => $delayedCount,
            'upcoming_schedules' => $upcomingCount,
            // Revenue
            'revenue_this_month' => (float) $revenueThisMonth,
            'revenue_prev_month' => (float) $revenuePrevMonth,
            'revenue_change_pct' => $revenuePrevMonth > 0 
                ? round((($revenueThisMonth - $revenuePrevMonth) / $revenuePrevMonth) * 100, 1) 
                : 0,
            // Trend (previous month)
            'prev_forecast_qty' => (float) ($prevForecasts->total ?? 0),
            'prev_actual_qty' => (float) ($prevActuals->total ?? 0),
            // AR
            'total_ar' => (float) $totalAR,
            'overdue_invoices' => $overdueInvoices,
            'collection_this_month' => (float) $collectionThisMonth,
            'open_invoices_count' => $openInvoicesCount,
            // SO Status
            'so_status' => $soStatusBreakdown,
        ];

        return Inertia::render('Sales/Planning/Dashboard', [
            'chartData' => $chartData,
            'stats' => $stats,
            'month' => $currentMonth->format('Y-m'),
            'topCustomers' => $topCustomers,
            'bottomCustomers' => $bottomCustomers,
            'deliveryTimeline' => $deliveryTimeline,
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

        // Get Deliveries (Schedules) by Product to match chart
        $deliveries = \App\Models\DeliverySchedule::whereBetween('delivery_date', [$startOfMonth, $endOfMonth])
            ->where('customer_id', $customerId)
            ->select('product_id', DB::raw('SUM(qty_scheduled) as total_qty'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // Merge Data
        $allProductIds = $forecasts->keys()
            ->merge($actuals->keys())
            ->merge($deliveries->keys())
            ->unique();
        
        $details = $allProductIds->map(function($productId) use ($forecasts, $actuals, $deliveries) {
            $forecast = $forecasts[$productId] ?? null;
            $actual = $actuals[$productId] ?? null;
            $delivery = $deliveries[$productId] ?? null;
            
            $productName = $forecast['product_name'] ?? $actual->product->name ?? 'Unknown Product';
            
            // Fallback for Delivery-only items
            if ($productName === 'Unknown Product' && $delivery) {
                 $prod = \App\Models\Product::find($productId);
                 if ($prod) {
                     $productName = $prod->name;
                     $sku = $prod->sku;
                     $unit = $prod->unit->code ?? 'PCS';
                 }
            }

            $sku = $forecast['sku'] ?? $actual->product->sku ?? ($sku ?? '-');
            $unit = $forecast['unit'] ?? 'PCS';
            
            $forecastQty = (float) ($forecast['forecast_qty'] ?? 0);
            $actualQty = (float) ($actual->total_qty ?? 0);
            $deliveryQty = (float) ($delivery->total_qty ?? 0);

            $accuracy = 0;
            if ($forecastQty > 0) {
                $accuracy = ($actualQty / $forecastQty) * 100;
            }

            return [
                'product_id' => $productId,
                'name' => $productName,
                'sku' => $sku,
                'unit' => $unit,
                'forecast' => $forecastQty,
                'actual' => $actualQty,
                'delivery' => $deliveryQty,
                'achievement' => $accuracy,
                'variance' => $actualQty - $forecastQty,
            ];
        })->values();

        return response()->json($details);
    }
}
