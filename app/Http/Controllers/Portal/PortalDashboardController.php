<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Ensure user is linked to a supplier
        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        // Key Dashboard Metrics
        $pendingPOs = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->whereIn('status', [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_APPROVED]) 
            ->count();
            
        $dispatchedDeliveries = \App\Models\GoodsReceipt::where('supplier_id', $user->supplier_id)
            ->where('status', \App\Models\GoodsReceipt::STATUS_DISPATCHED)
            ->count();

        $unpaidInvoiceTotal = \App\Models\PurchaseInvoice::where('supplier_id', $user->supplier_id)
            ->where('status', \App\Models\PurchaseInvoice::STATUS_UNPAID)
            ->sum('total_amount');

        // Recent Activity (Merged POs, Deliveries, Invoices sort of... or just stick to separate lists for now?)
        // Let's just pass recent objects for now.
        $recentPOs = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->latest()
            ->take(5)
            ->get();

        // Monthly Stats for Chart (Trailing 6 months)
        $monthlyStats = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, count(*) as count, sum(total) as total')
            ->where('order_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Portal/Dashboard', [
            'metrics' => [
                'pending_pos' => $pendingPOs,
                'active_deliveries' => $dispatchedDeliveries,
                'unpaid_invoices_amount' => $unpaidInvoiceTotal,
            ],
            'recent_pos' => $recentPOs,
            'chart_data' => $monthlyStats
        ]);
    }
}
