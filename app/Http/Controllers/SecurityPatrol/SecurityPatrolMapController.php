<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrolSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityPatrolMapController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');

        // Checkpoints with geofence and SOPs
        $checkpoints = SecurityCheckpoint::where('is_active', true)
            ->select('id', 'code', 'name', 'area_zone', 'latitude', 'longitude', 'geofence_radius', 'sop_items')
            ->get();

        // Get sessions for today
        $todaySessions = SecurityPatrolSession::with(['user:id,name', 'schedule:id,name', 'logs.checkpoint'])
            ->where('patrol_date', $today)
            ->latest('start_time')
            ->get();

        // Active / latest session for real-time map tracking
        $activeSession = $todaySessions->firstWhere('status', 'in_progress') ?? $todaySessions->first();

        $activeLogs = [];
        $routePolyline = [];

        if ($activeSession) {
            $logs = $activeSession->logs()
                ->with('checkpoint:id,code,name,latitude,longitude')
                ->orderBy('scanned_at', 'asc')
                ->get();

            foreach ($logs as $log) {
                $activeLogs[$log->checkpoint_id] = [
                    'scanned_at' => $log->scanned_at ? $log->scanned_at->format('H:i:s') : null,
                    'condition_status' => $log->condition_status,
                    'is_deviation' => $log->is_deviation,
                    'distance_meters' => $log->distance_meters,
                    'notes' => $log->notes,
                    'photo_url' => $log->photo_url,
                ];

                if ($log->lat_actual && $log->lng_actual) {
                    $routePolyline[] = [
                        (float) $log->lat_actual,
                        (float) $log->lng_actual,
                    ];
                } elseif ($log->checkpoint) {
                    $routePolyline[] = [
                        (float) $log->checkpoint->latitude,
                        (float) $log->checkpoint->longitude,
                    ];
                }
            }
        }

        // Map status for each checkpoint
        $checkpointStatus = $checkpoints->map(function ($cp) use ($activeLogs) {
            $isLogged = isset($activeLogs[$cp->id]);
            $status = 'pending';
            $logDetails = null;

            if ($isLogged) {
                $logDetails = $activeLogs[$cp->id];
                $status = ($logDetails['condition_status'] === 'incident') ? 'incident' : 'completed';
            }

            return [
                'id' => $cp->id,
                'code' => $cp->code,
                'name' => $cp->name,
                'area_zone' => $cp->area_zone,
                'latitude' => (float) $cp->latitude,
                'longitude' => (float) $cp->longitude,
                'geofence_radius' => $cp->geofence_radius,
                'status' => $status,
                'log' => $logDetails,
            ];
        });

        // Quick statistics for today
        $totalSessionsToday = $todaySessions->count();
        $completedSessions = $todaySessions->where('status', 'completed')->count();
        $totalIncidentsToday = $todaySessions->sum('incidents_count');

        // Dynamic office coordinates from Smart Attendance preferences
        $officeCoordinates = AppSetting::getOfficeCoordinates();

        return Inertia::render('SecurityPatrol/Map/Index', [
            'checkpoints' => $checkpointStatus,
            'todaySessions' => $todaySessions,
            'activeSession' => $activeSession,
            'routePolyline' => $routePolyline,
            'officeCoordinates' => $officeCoordinates,
            'stats' => [
                'total_checkpoints' => $checkpoints->count(),
                'checked_checkpoints' => count($activeLogs),
                'total_sessions_today' => $totalSessionsToday,
                'completed_sessions' => $completedSessions,
                'total_incidents_today' => $totalIncidentsToday,
            ],
        ]);
    }
}
