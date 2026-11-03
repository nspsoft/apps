<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sFinding;
use App\Models\Audit5sSession;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Audit5sAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $buildingId = $request->input('building_id');

        $sessionQuery = Audit5sSession::whereYear('audit_date', $year);
        if ($buildingId) {
            $sessionQuery->whereHas('zone', fn($zq) => $zq->where('building_id', $buildingId));
        }

        // 1. Average scores across 5 Pillars (for Spider/Radar Chart)
        $pillarAverages = [
            'Sort' => round((float) (clone $sessionQuery)->avg('score_sort') ?? 0, 1),
            'Set In Order' => round((float) (clone $sessionQuery)->avg('score_set_in_order') ?? 0, 1),
            'Shine' => round((float) (clone $sessionQuery)->avg('score_shine') ?? 0, 1),
            'Standardize' => round((float) (clone $sessionQuery)->avg('score_standardize') ?? 0, 1),
            'Sustain' => round((float) (clone $sessionQuery)->avg('score_sustain') ?? 0, 1),
        ];

        // 2. Monthly Trend (Past 12 Months of the selected year)
        $monthlyScores = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStr = date('M', mktime(0, 0, 0, $m, 1));
            $avgMonth = (clone $sessionQuery)->whereMonth('audit_date', $m)->avg('total_score');
            $monthlyScores[] = [
                'month' => $monthStr,
                'avg_score' => $avgMonth ? round((float)$avgMonth, 1) : null,
                'audit_count' => (clone $sessionQuery)->whereMonth('audit_date', $m)->count(),
            ];
        }

        // 3. CAPA Resolution stats
        $findingQuery = Audit5sFinding::whereYear('created_at', $year);
        if ($buildingId) {
            $findingQuery->whereHas('zone', fn($zq) => $zq->where('building_id', $buildingId));
        }

        $totalFindings = (clone $findingQuery)->count();
        $closedFindings = (clone $findingQuery)->where('status', 'Closed')->count();
        $submittedFindings = (clone $findingQuery)->where('status', 'Submitted')->count();
        $openFindings = (clone $findingQuery)->whereIn('status', ['Open', 'In Progress'])->count();
        $redTagFindings = (clone $findingQuery)->where('is_red_tag', true)->count();
        $closureRate = $totalFindings > 0 ? round(($closedFindings / $totalFindings) * 100, 1) : 100;

        // 4. Grade distribution
        $gradeCounts = [
            'A' => (clone $sessionQuery)->where('grade', 'A')->count(),
            'B' => (clone $sessionQuery)->where('grade', 'B')->count(),
            'C' => (clone $sessionQuery)->where('grade', 'C')->count(),
            'D' => (clone $sessionQuery)->where('grade', 'D')->count(),
        ];

        $buildings = Audit5sBuilding::where('is_active', true)->select('id', 'name', 'code')->get();

        return Inertia::render('Audit5S/Analytics/Index', [
            'pillarAverages' => $pillarAverages,
            'monthlyScores' => $monthlyScores,
            'capaStats' => [
                'total' => $totalFindings,
                'closed' => $closedFindings,
                'submitted' => $submittedFindings,
                'open' => $openFindings,
                'red_tags' => $redTagFindings,
                'closure_rate' => $closureRate,
            ],
            'gradeCounts' => $gradeCounts,
            'buildings' => $buildings,
            'filters' => [
                'year' => (int)$year,
                'building_id' => $buildingId ? (int)$buildingId : null,
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $buildingId = $request->input('building_id');

        $query = Audit5sSession::with(['zone.building', 'zone.department', 'auditor'])
            ->whereYear('audit_date', $year);

        if ($buildingId) {
            $query->whereHas('zone', fn($zq) => $zq->where('building_id', $buildingId));
        }

        $sessions = $query->orderBy('audit_date', 'desc')->get();

        $filename = "5S_Audit_Report_{$year}_" . date('Ymd_His') . ".csv";

        return new StreamedResponse(function () use ($sessions) {
            $handle = fopen('php://output', 'w');
            // CSV BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Session Code',
                'Audit Date',
                'Building',
                'Zone Code',
                'Zone Name',
                'Department',
                'Auditor',
                '1S (Sort / Ringkas)',
                '2S (Set In Order / Rapi)',
                '3S (Shine / Resik)',
                '4S (Standardize / Rawat)',
                '5S (Sustain / Rajin)',
                'Total Score (%)',
                'Grade',
                'Status',
                'Notes',
            ]);

            foreach ($sessions as $s) {
                fputcsv($handle, [
                    $s->session_code,
                    $s->audit_date->format('Y-m-d'),
                    $s->zone?->building?->name ?? '-',
                    $s->zone?->code ?? '-',
                    $s->zone?->name ?? '-',
                    $s->zone?->department?->name ?? '-',
                    $s->auditor?->name ?? '-',
                    $s->score_sort,
                    $s->score_set_in_order,
                    $s->score_shine,
                    $s->score_standardize,
                    $s->score_sustain,
                    $s->total_score,
                    $s->grade,
                    $s->status,
                    $s->summary_notes,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
