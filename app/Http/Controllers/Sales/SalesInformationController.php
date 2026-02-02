<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SalesInformationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Chart Data (Status Distribution)
        // Grouping logic:
        // - Processing: confirmed, processing, shipped
        // - Completed: completed, paid
        // - Cancelled: cancelled
        // - Draft: draft
        
        $stats = SalesOrder::select('status', DB::raw('count(*) as total'))
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
            
            if ($s === 'draft') {
                $chartData['draft'] += $count;
            } elseif (in_array($s, ['confirmed', 'processing', 'shipped'])) {
                 $chartData['processing'] += $count;
            } elseif (in_array($s, ['completed', 'paid'])) {
                 $chartData['completed'] += $count;
            } elseif ($s === 'cancelled') {
                 $chartData['cancelled'] += $count;
            }
        }

        $totalOrders = array_sum($chartData);
        $chartPercentages = [];
        foreach ($chartData as $key => $val) {
            $chartPercentages[$key] = $totalOrders > 0 ? round(($val / $totalOrders) * 100, 1) : 0;
        }

        return Inertia::render('Sales/Information', [
            'chartData' => $chartData,
            'chartPercentages' => $chartPercentages,
        ]);
    }
}
