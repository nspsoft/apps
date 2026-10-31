<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrolLog;
use App\Models\SecurityPatrolSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SecurityPatrolAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->input('period', 14); // 7, 14, 30 days
        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        // Base sessions query in date range
        $sessionsQuery = SecurityPatrolSession::whereBetween('patrol_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]);

        $totalSessions = (clone $sessionsQuery)->count();
        $completedSessions = (clone $sessionsQuery)->where('status', 'completed')->count();
        $incompleteSessions = (clone $sessionsQuery)->where('status', 'incomplete')->count();
        $inProgressSessions = (clone $sessionsQuery)->where('status', 'in_progress')->count();

        $complianceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0;

        $avgDurationSeconds = (clone $sessionsQuery)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds') ?: 0;
        $avgDurationMinutes = (int) round(abs($avgDurationSeconds) / 60);

        $totalMissedPoints = (clone $sessionsQuery)->sum('missed_checkpoints');
        $totalIncidents = (clone $sessionsQuery)->sum('incidents_count');

        // 1. Daily Compliance Trend (Past N days)
        $dailyTrend = [];
        $cursor = $startDate->copy();
        while ($cursor->lte(Carbon::today())) {
            $dStr = $cursor->format('Y-m-d');
            $daySessions = (clone $sessionsQuery)->where('patrol_date', $dStr)->get();
            $dayTotal = $daySessions->count();
            $dayCompleted = $daySessions->where('status', 'completed')->count();
            $dayRate = $dayTotal > 0 ? round(($dayCompleted / $dayTotal) * 100, 1) : 100;

            $dailyTrend[] = [
                'date' => $cursor->format('d M'),
                'full_date' => $dStr,
                'compliance_rate' => $dayRate,
                'total_sessions' => $dayTotal,
                'completed' => $dayCompleted,
            ];
            $cursor->addDay();
        }

        // 2. Top Missed Checkpoints
        // Calculate which checkpoints were not logged across historical sessions
        $allCheckpoints = SecurityCheckpoint::where('is_active', true)->get(['id', 'code', 'name']);
        $missedCounts = [];
        foreach ($allCheckpoints as $cp) {
            $missedCounts[$cp->id] = [
                'id' => $cp->id,
                'code' => $cp->code,
                'name' => $cp->name,
                'missed_count' => 0,
            ];
        }

        $allHistoricalSessions = (clone $sessionsQuery)->with('logs')->get();
        foreach ($allHistoricalSessions as $sess) {
            $loggedCpIds = $sess->logs->pluck('checkpoint_id')->toArray();
            foreach ($allCheckpoints as $cp) {
                if (!in_array($cp->id, $loggedCpIds) && $sess->status !== 'in_progress') {
                    $missedCounts[$cp->id]['missed_count']++;
                }
            }
        }

        // Sort descending and take top 5
        usort($missedCounts, fn($a, $b) => $b['missed_count'] <=> $a['missed_count']);
        $topMissed = array_slice($missedCounts, 0, 5);

        // 3. Status Distribution
        $statusDistribution = [
            'labels' => ['Selesai Tepat Waktu', 'Tidak Lengkap (Missed)', 'Sedang Berlangsung'],
            'data' => [$completedSessions, $incompleteSessions, $inProgressSessions],
        ];

        // 4. Incidents by Zone
        $incidentsByZone = SecurityPatrolLog::where('condition_status', 'incident')
            ->whereHas('session', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('patrol_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->join('security_checkpoints', 'security_patrol_logs.checkpoint_id', '=', 'security_checkpoints.id')
            ->select('security_checkpoints.area_zone', DB::raw('count(*) as count'))
            ->groupBy('security_checkpoints.area_zone')
            ->get();

        return Inertia::render('SecurityPatrol/Analytics/Index', [
            'kpis' => [
                'compliance_rate' => $complianceRate,
                'avg_duration_minutes' => $avgDurationMinutes,
                'total_missed_points' => $totalMissedPoints,
                'total_incidents' => $totalIncidents,
                'total_sessions' => $totalSessions,
            ],
            'dailyTrend' => $dailyTrend,
            'topMissed' => $topMissed,
            'statusDistribution' => $statusDistribution,
            'incidentsByZone' => $incidentsByZone,
            'period' => $days,
        ]);
    }
}
