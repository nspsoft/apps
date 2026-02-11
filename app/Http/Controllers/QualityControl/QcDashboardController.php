<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QcDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Inspections
        $totalInspections = \App\Models\QcInspection::count();

        // 2. Pass Rate (Last 30 Days)
        $inspectionsLast30Days = \App\Models\QcInspection::where('created_at', '>=', now()->subDays(30))->get();
        $totalLast30 = $inspectionsLast30Days->count();
        $passedLast30 = $inspectionsLast30Days->where('status', 'pass')->count();
        $passRate = $totalLast30 > 0 ? round(($passedLast30 / $totalLast30) * 100, 1) : 0;

        // 3. Open NCRs
        $openNcrs = \App\Models\NonConformanceReport::where('status', 'open')->count();

        // 4. Recent Inspections
        $recentInspections = \App\Models\QcInspection::with(['inspector'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($inspection) {
                return [
                    'id' => $inspection->id,
                    'reference_type' => $inspection->reference_type,
                    'reference_id' => $inspection->reference_id,
                    'status' => $inspection->status,
                    'date' => $inspection->created_at->diffForHumans(),
                    'inspector' => $inspection->inspector->name,
                ];
            });

        return inertia('QualityControl/Dashboard', [
            'stats' => [
                'total_inspections' => $totalInspections,
                'pass_rate' => $passRate,
                'open_ncrs' => $openNcrs,
            ],
            'recent_inspections' => $recentInspections,
        ]);
    }
}
