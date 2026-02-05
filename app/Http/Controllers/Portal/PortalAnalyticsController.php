<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PortalAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $supplierId = $user->supplier_id;

        // Date range (default last 12 months)
        $endDate = now();
        $startDate = now()->subMonths(12);

        // ============ KPI METRICS ============

        // Total Orders (all time)
        $totalOrders = PurchaseOrder::where('supplier_id', $supplierId)->count();

        // Completed Orders (received status)
        $completedOrders = PurchaseOrder::where('supplier_id', $supplierId)
            ->whereIn('status', [PurchaseOrder::STATUS_RECEIVED])
            ->count();

        // On-Time Delivery Rate
        // Compare receipt_date of completed GoodsReceipt vs expected_date of PO
        $deliveredOrders = GoodsReceipt::where('supplier_id', $supplierId)
            ->where('status', GoodsReceipt::STATUS_COMPLETED)
            ->whereHas('purchaseOrder', function ($q) {
                $q->whereNotNull('expected_date');
            })
            ->with('purchaseOrder:id,expected_date')
            ->get();

        $onTimeCount = 0;
        $totalDelivered = $deliveredOrders->count();

        foreach ($deliveredOrders as $gr) {
            if ($gr->purchaseOrder && $gr->receipt_date <= $gr->purchaseOrder->expected_date) {
                $onTimeCount++;
            }
        }

        $otdRate = $totalDelivered > 0 ? round(($onTimeCount / $totalDelivered) * 100, 1) : 0;

        // Average Lead Time (days from order_date to receipt completion)
        $avgLeadTime = GoodsReceipt::where('supplier_id', $supplierId)
            ->where('status', GoodsReceipt::STATUS_COMPLETED)
            ->whereHas('purchaseOrder')
            ->with('purchaseOrder:id,order_date')
            ->get()
            ->filter(fn($gr) => $gr->purchaseOrder)
            ->map(fn($gr) => $gr->receipt_date->diffInDays($gr->purchaseOrder->order_date))
            ->avg();

        $avgLeadTime = $avgLeadTime ? round($avgLeadTime, 1) : 0;

        // Order Fulfillment Rate (qty_received / qty_ordered)
        $fulfillmentData = \DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->selectRaw('SUM(purchase_order_items.qty) as total_ordered, SUM(purchase_order_items.qty_received) as total_received')
            ->first();

        $fulfillmentRate = ($fulfillmentData && $fulfillmentData->total_ordered > 0)
            ? round(($fulfillmentData->total_received / $fulfillmentData->total_ordered) * 100, 1)
            : 0;

        // ============ CHART DATA ============

        // Monthly Order Volume (last 6 months)
        $monthlyOrders = PurchaseOrder::where('supplier_id', $supplierId)
            ->where('order_date', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, COUNT(*) as count, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Monthly OTD Trend (last 6 months)
        $monthlyOTD = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $monthDeliveries = GoodsReceipt::where('supplier_id', $supplierId)
                ->where('status', GoodsReceipt::STATUS_COMPLETED)
                ->whereBetween('receipt_date', [$monthStart, $monthEnd])
                ->whereHas('purchaseOrder', fn($q) => $q->whereNotNull('expected_date'))
                ->with('purchaseOrder:id,expected_date')
                ->get();

            $monthOnTime = $monthDeliveries->filter(fn($gr) => 
                $gr->purchaseOrder && $gr->receipt_date <= $gr->purchaseOrder->expected_date
            )->count();

            $monthTotal = $monthDeliveries->count();
            $monthOtd = $monthTotal > 0 ? round(($monthOnTime / $monthTotal) * 100, 1) : null;

            $monthlyOTD[] = [
                'month' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M'),
                'rate' => $monthOtd,
                'on_time' => $monthOnTime,
                'total' => $monthTotal,
            ];
        }

        // Order Status Distribution
        $statusDistribution = PurchaseOrder::where('supplier_id', $supplierId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('Portal/Analytics/Index', [
            'metrics' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'otd_rate' => $otdRate,
                'avg_lead_time' => $avgLeadTime,
                'fulfillment_rate' => $fulfillmentRate,
            ],
            'charts' => [
                'monthly_orders' => $monthlyOrders,
                'monthly_otd' => $monthlyOTD,
                'status_distribution' => $statusDistribution,
            ],
        ]);
    }
}
