<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where('description', 'like', "%{$request->search}%")
                ->orWhereHas('causer', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                });
        }

        if ($request->subject_type) {
            $query->where('subject_type', 'like', "%{$request->subject_type}%");
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate(20)
            ->withQueryString()
            ->through(function ($log) {
                $subject = class_basename($log->subject_type);
                if (!$subject && $log->log_name === 'auth' && isset($log->properties['ip'])) {
                    $subject = $log->properties['ip'];
                }

                $event = $log->event;
                if (!$event && $log->log_name === 'auth') {
                    $desc = strtolower($log->description);
                    if (str_contains($desc, 'login') || str_contains($desc, 'logged in')) {
                        $event = 'login';
                    } elseif (str_contains($desc, 'logout') || str_contains($desc, 'logged out')) {
                        $event = 'logout';
                    } elseif (str_contains($desc, 'failed')) {
                        $event = 'failed';
                    }
                }

                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_type' => $subject,
                    'subject_id' => $log->subject_id,
                    'causer_name' => $log->causer ? $log->causer->name : 'System',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'event' => $event,
                    'properties' => $log->properties,
                ];
            });

        $subjectTypes = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->get()
            ->map(fn($item) => [
                'value' => class_basename($item->subject_type),
                'full_class' => $item->subject_type
            ])
            ->sortBy('value')
            ->values();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'subject_type', 'start_date', 'end_date']),
            'subjectTypes' => $subjectTypes,
        ]);
    }

    public function show(Activity $activityLog)
    {
        $activityLog->load('causer');

        return Inertia::render('Admin/ActivityLogs/Show', [
            'log' => [
                'id' => $activityLog->id,
                'description' => $activityLog->description,
                'subject_type' => $activityLog->subject_type,
                'subject_id' => $activityLog->subject_id,
                'causer_name' => $activityLog->causer ? $activityLog->causer->name : 'System',
                'created_at' => $activityLog->created_at->format('Y-m-d H:i:s'),
                'properties' => $activityLog->properties,
                'event' => $activityLog->event,
            ]
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new ActivityLogExport($request->start_date, $request->end_date),
            'activity-logs-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function reset(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $count = Activity::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->delete();

        return back()->with('success', "Successfully deleted {$count} activity logs.");
    }
}
