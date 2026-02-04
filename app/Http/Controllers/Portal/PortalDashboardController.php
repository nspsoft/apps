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
            ->where('status', 'draft') // Assuming 'draft' or a specific status for new POs
            ->count();
            
        $recentPOs = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Portal/Dashboard', [
            'metrics' => [
                'pending_pos' => $pendingPOs,
            ],
            'recent_pos' => $recentPOs
        ]);
    }
}
