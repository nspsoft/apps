<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\ProductionEntry;
use App\Models\WorkOrder;
use App\Models\Machine;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Overall Metrics
        $todayProduction = ProductionEntry::whereDate('production_date', $today)->sum('qty_produced');
        $yesterdayProduction = ProductionEntry::whereDate('production_date', $yesterday)->sum('qty_produced');
        $productionGrowth = $yesterdayProduction > 0 ? (($todayProduction - $yesterdayProduction) / $yesterdayProduction) * 100 : 0;

        // 2. OEE Components (Quality, Performance, Availability)
        // For Quality
        $totalProducedAll = ProductionEntry::sum('qty_produced');
        $totalRejectedAll = ProductionEntry::sum('qty_rejected');
        $qualityRate = $totalProducedAll > 0 ? (($totalProducedAll - $totalRejectedAll) / $totalProducedAll) * 100 : 0;

        // For Performance (Actual vs Plan for active/recent WOs)
        $activeWOs = WorkOrder::whereIn('status', ['confirmed', 'in_progress'])->get();
        $totalPlanned = $activeWOs->sum('qty_planned');
        $totalProduced = $activeWOs->sum('qty_produced');
        $performanceRate = $totalPlanned > 0 ? ($totalProduced / $totalPlanned) * 100 : 0;

        // For Availability (Run Time vs Downtime)
        // Assume daily available time = 24h * machines (simplification)
        $machineCount = Machine::where('is_active', true)->count();
        $totalScheduledMinutes = $machineCount * 24 * 60;
        $totalDowntimeToday = ProductionEntry::whereDate('production_date', $today)->sum('downtime_minutes');
        $availabilityRate = $totalScheduledMinutes > 0 ? (($totalScheduledMinutes - $totalDowntimeToday) / $totalScheduledMinutes) * 100 : 0;

        $oeeValue = ($qualityRate / 100) * ($performanceRate / 100) * ($availabilityRate / 100) * 100;

        // 3. Shift Productivity (Today)
        $shiftProductivity = ProductionEntry::whereDate('production_date', $today)
            ->select('shift', DB::raw('SUM(qty_produced) as total_qty'))
            ->groupBy('shift')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->shift => $item->total_qty]);

        // 4. Machine Status (Today)
        $machines = Machine::where('is_active', true)->get()->map(function ($machine) use ($today) {
            $latestEntry = ProductionEntry::where('machine_line', $machine->name)
                ->whereDate('production_date', $today)
                ->latest()
                ->first();
            
            $status = 'Idle';
            if ($latestEntry) {
                $status = $latestEntry->downtime_minutes > 0 ? 'Downtime' : 'Running';
            }

            return [
                'id' => $machine->id,
                'name' => $machine->name,
                'status' => $status,
                'last_qty' => $latestEntry->qty_produced ?? 0,
                'last_update' => $latestEntry ? $latestEntry->created_at->diffForHumans() : '-',
            ];
        });

        return Inertia::render('Manufacturing/Production/Index', [
            'stats' => [
                'oee' => round($oeeValue, 1),
                'quality' => round($qualityRate, 1),
                'performance' => round($performanceRate, 1),
                'availability' => round($availabilityRate, 1),
                'today_qty' => $todayProduction,
                'yesterday_qty' => $yesterdayProduction,
                'growth' => round($productionGrowth, 1),
            ],
            'shift_data' => $shiftProductivity,
            'machine_statuses' => $machines,
            'recent_entries' => ProductionEntry::with('workOrder.product')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
