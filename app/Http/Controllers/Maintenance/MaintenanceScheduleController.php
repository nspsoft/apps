<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Models\Machine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MaintenanceScheduleController extends Controller
{
    public function index()
    {
        $schedules = MaintenanceSchedule::with('machine')
            ->orderBy('next_due_date', 'asc')
            ->get()
            ->map(function ($schedule) {
                // Calculate status color and days due
                $nextDue = Carbon::parse($schedule->next_due_date);
                $daysDiff = Carbon::now()->diffInDays($nextDue, false);
                
                $statusColor = 'bg-emerald-500'; // Default healthy
                if ($daysDiff < 0) {
                    $statusColor = 'bg-rose-500'; // Overdue
                } elseif ($daysDiff <= 7) {
                    $statusColor = 'bg-amber-500'; // Warning
                }

                return [
                    'id' => $schedule->id,
                    'machine' => $schedule->machine->name,
                    'machine_code' => $schedule->machine->code,
                    'task' => $schedule->task_name,
                    'description' => $schedule->description,
                    'frequency' => $schedule->frequency_days . ' Days',
                    'last_performed' => $schedule->last_performed_at ? $schedule->last_performed_at->format('d M Y') : '-',
                    'next_due' => $nextDue->format('d M Y'),
                    'days_due' => $daysDiff,
                    'status' => $schedule->status,
                    'status_color' => $statusColor,
                ];
            });

        // KPI Stats
        $stats = [
            'total_schedules' => $schedules->count(),
            'overdue' => $schedules->filter(fn($s) => $s['days_due'] < 0)->count(),
            'upcoming' => $schedules->filter(fn($s) => $s['days_due'] >= 0 && $s['days_due'] <= 7)->count(),
            'healthy' => $schedules->filter(fn($s) => $s['days_due'] > 7)->count(),
        ];

        return Inertia::render('Maintenance/Schedule', [
            'schedules' => $schedules,
            'stats' => $stats,
            'machines' => Machine::select('id', 'name', 'code')->get(), // For dropdown in create
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'task_name' => 'required|string|max:255',
            'description' => 'required|string',
            'frequency_days' => 'required|integer|min:1',
        ]);

        $validated['last_performed_at'] = Carbon::now(); // Assume just started
        $validated['next_due_date'] = Carbon::now()->addDays($validated['frequency_days']);
        $validated['status'] = 'active';

        MaintenanceSchedule::create($validated);

        return redirect()->back()->with('success', 'Preventive Schedule Created');
    }

    public function complete(Request $request, MaintenanceSchedule $schedule)
    {
        $schedule->update([
            'last_performed_at' => Carbon::now(),
            'next_due_date' => Carbon::now()->addDays($schedule->frequency_days),
        ]);

        return redirect()->back()->with('success', 'Maintenance Task Completed');
    }
}
