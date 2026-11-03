<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class Audit5sLeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit5sZone::with([
            'building:id,name,code',
            'department:id,name',
            'picEmployee:id,nik,full_name,profile_picture,phone,section',
        ]);

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Zones with an audit score
        $allZones = (clone $query)->orderByDesc('last_score')->get();

        // Separate Top 3 for podium
        $top3 = $allZones->take(3);

        // Department ranking summary
        $deptRankings = Audit5sZone::select('department_id')
            ->selectRaw('AVG(last_score) as avg_score, COUNT(*) as total_zones')
            ->whereNotNull('last_score')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('avg_score')
            ->with('department:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'department_id' => $row->department_id,
                    'department_name' => $row->department?->name ?? 'Unassigned',
                    'avg_score' => round((float)$row->avg_score, 1),
                    'total_zones' => $row->total_zones,
                ];
            });

        $buildings = Audit5sBuilding::where('is_active', true)->select('id', 'name', 'code')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->get();

        $stats = [
            'total_zones' => $allZones->count(),
            'green_zones' => $allZones->where('last_score', '>=', 90)->count(),
            'yellow_zones' => $allZones->whereBetween('last_score', [70, 89.99])->count(),
            'red_zones' => $allZones->where('last_score', '<', 70)->whereNotNull('last_score')->count(),
            'unaudited' => $allZones->whereNull('last_score')->count(),
        ];

        return Inertia::render('Audit5S/Leaderboard/Index', [
            'zones' => $allZones,
            'top3' => $top3,
            'deptRankings' => $deptRankings,
            'buildings' => $buildings,
            'departments' => $departments,
            'filters' => $request->only(['building_id', 'department_id']),
            'stats' => $stats,
        ]);
    }
}
