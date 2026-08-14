<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class SalesDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Years selection (dropdown options)
        $earliestOrder = SalesOrder::orderBy('order_date', 'asc')->first();
        $startYear = $earliestOrder ? Carbon::parse($earliestOrder->order_date)->year : now()->year;
        $endYear = now()->year + 1;
        $years = range($startYear, $endYear);
        if (empty($years)) {
            $years = [now()->year];
        }
        rsort($years);

        // Fetch inputs with defaults
        $year = intval($request->input('year', now()->year));
        $periodType = $request->input('period_type', 'month');
        $periodValue = $request->input('period_value');

        // Determine defaults if value not provided
        if (is_null($periodValue)) {
            if ($periodType === 'month') {
                $periodValue = now()->month;
            } elseif ($periodType === 'quarter') {
                $periodValue = 'Q' . ceil(now()->month / 3);
            } elseif ($periodType === 'semester') {
                $periodValue = now()->month <= 6 ? 'S1' : 'S2';
            }
        }

        // Parse date boundaries
        $startDate = null;
        $endDate = null;
        $periodLabel = '';

        if ($periodType === 'month') {
            $monthNum = intval($periodValue);
            $startDate = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $periodLabel = $startDate->translatedFormat('F Y');
        } elseif ($periodType === 'quarter') {
            $q = strval($periodValue);
            $startMonth = 1;
            if ($q === 'Q2') {
                $startMonth = 4;
            } elseif ($q === 'Q3') {
                $startMonth = 7;
            } elseif ($q === 'Q4') {
                $startMonth = 10;
            }
            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfMonth();
            $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
            $periodLabel = "Quartal " . substr($q, 1) . " ($year)";
        } elseif ($periodType === 'semester') {
            $s = strval($periodValue);
            $startMonth = ($s === 'S2') ? 7 : 1;
            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfMonth();
            $endDate = $startDate->copy()->addMonths(5)->endOfMonth();
            $periodLabel = "Semester " . ($s === 'S2' ? '2' : '1') . " ($year)";
        } else {
            // Full Year
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear();
            $periodLabel = "Tahun $year";
        }

        // 1. KPI Stats for the Period
        
        // Revenue (Confirmed/Processing/Shipped/Delivered SOs this period)
        $revenue = SalesOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('total') ?? 0;

        // Order Count
        $orderCount = SalesOrder::whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        // Pending Quotations (global/overall, or can be filtered - let's make it overall)
        $pendingQuotations = Quotation::whereIn('status', ['draft', 'pending'])->count();

        // Average Order Value
        $avgOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;

        $stats = [
            'monthly_revenue' => $revenue,
            'order_count' => $orderCount,
            'pending_quotations' => $pendingQuotations,
            'avg_order_value' => $avgOrderValue,
            'period_label' => $periodLabel,
        ];

        // 2. Sales Trend (group by day if single month, group by month otherwise)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $salesTrend = [];

        if ($periodType === 'month') {
            // Trend daily
            $dayFormat = $isSqlite ? "strftime('%d', order_date)" : "DATE_FORMAT(order_date, '%d')";
            $trendQuery = SalesOrder::select(
                    DB::raw("$dayFormat as day_key"),
                    DB::raw('SUM(total) as total')
                )
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->groupBy('day_key')
                ->orderBy('day_key')
                ->get()
                ->pluck('total', 'day_key')
                ->toArray();

            // Populate all days of the month to make a nice continuous chart
            $daysInMonth = $startDate->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayStr = str_pad($d, 2, '0', STR_PAD_LEFT);
                $salesTrend[] = [
                    'label' => $dayStr,
                    'total' => doubleval($trendQuery[$dayStr] ?? 0),
                ];
            }
        } else {
            // Trend monthly
            $monthFormat = $isSqlite ? "strftime('%m', order_date)" : "DATE_FORMAT(order_date, '%m')";
            $trendQuery = SalesOrder::select(
                    DB::raw("$monthFormat as month_key"),
                    DB::raw('SUM(total) as total')
                )
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->groupBy('month_key')
                ->orderBy('month_key')
                ->get()
                ->pluck('total', 'month_key')
                ->toArray();

            // Depending on period, populate the months
            $monthsToPopulate = [];
            if ($periodType === 'quarter') {
                $startM = $startDate->month;
                $monthsToPopulate = range($startM, $startM + 2);
            } elseif ($periodType === 'semester') {
                $startM = $startDate->month;
                $monthsToPopulate = range($startM, $startM + 5);
            } else {
                // Year
                $monthsToPopulate = range(1, 12);
            }

            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            foreach ($monthsToPopulate as $m) {
                $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                $salesTrend[] = [
                    'label' => $monthNames[$m] ?? $mStr,
                    'total' => doubleval($trendQuery[$mStr] ?? 0),
                ];
            }
        }

        // 3. Status Distribution
        $statusDist = SalesOrder::select('status', DB::raw('count(*) as count'))
            ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 4. Top Customers
        $topCustomers = SalesOrder::select('customers.name', DB::raw('SUM(sales_orders.total) as total_revenue'))
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereNotIn('sales_orders.status', ['cancelled', 'draft'])
            ->whereBetween('sales_orders.order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('customers.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // 5. Recent Orders
        $recentOrders = SalesOrder::with('customer')
            ->whereYear('order_date', $year)
            ->orderByDesc('order_date')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'so_number' => $o->so_number,
                'customer' => $o->customer->name ?? 'Unknown',
                'amount' => $o->total,
                'status' => $o->status,
                'date' => $o->order_date->format('d M'),
            ]);

        return Inertia::render('Sales/Dashboard', [
            'stats' => $stats,
            'salesTrend' => $salesTrend,
            'statusDist' => $statusDist,
            'topCustomers' => $topCustomers,
            'recentOrders' => $recentOrders,
            'filters' => [
                'year' => $year,
                'period_type' => $periodType,
                'period_value' => $periodValue,
            ],
            'years' => $years,
        ]);
    }
}
