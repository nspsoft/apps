<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchasingInformationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Chart Data (Status Distribution)
        // Grouping logic:
        // - Processing: submitted, approved, ordered, acknowledged, partial
        // - Completed: received
        // - Cancelled: cancelled, rejected
        // - Draft: draft
        
        $stats = PurchaseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        
        $chartData = [
            'draft' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($stats as $stat) {
            $s = $stat->status;
            $count = $stat->total;
            
            if ($s === PurchaseOrder::STATUS_DRAFT) {
                $chartData['draft'] += $count;
            } elseif (in_array($s, [
                PurchaseOrder::STATUS_SUBMITTED, 
                PurchaseOrder::STATUS_APPROVED, 
                PurchaseOrder::STATUS_ORDERED, 
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIAL
            ])) {
                 $chartData['processing'] += $count;
            } elseif ($s === PurchaseOrder::STATUS_RECEIVED) {
                 $chartData['completed'] += $count;
            } elseif (in_array($s, [
                PurchaseOrder::STATUS_CANCELLED, 
                PurchaseOrder::STATUS_REJECTED
            ])) {
                 $chartData['cancelled'] += $count;
            }
        }

        $totalOrders = array_sum($chartData);
        $chartPercentages = [];
        foreach ($chartData as $key => $val) {
            $chartPercentages[$key] = $totalOrders > 0 ? round(($val / $totalOrders) * 100, 1) : 0;
        }

        return Inertia::render('Purchasing/Information', [
            'chartData' => $chartData,
            'chartPercentages' => $chartPercentages,
        ]);
    }
}
