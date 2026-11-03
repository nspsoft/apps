<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sSchedule;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class Audit5sScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit5sSchedule::with(['auditor:id,name,email', 'building:id,name,code', 'sessions.zone'])
            ->withCount('sessions');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('month')) {
            $query->whereMonth('scheduled_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('scheduled_date', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('auditor', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $schedules = $query->orderBy('scheduled_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones:id,building_id,name,code'])
            ->select('id', 'name', 'code')
            ->get();

        $auditors = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Summary counts
        $stats = [
            'total' => Audit5sSchedule::count(),
            'scheduled' => Audit5sSchedule::where('status', 'Scheduled')->count(),
            'in_progress' => Audit5sSchedule::where('status', 'In Progress')->count(),
            'completed' => Audit5sSchedule::where('status', 'Completed')->count(),
        ];

        return Inertia::render('Audit5S/Schedules/Index', [
            'schedules' => $schedules,
            'buildings' => $buildings,
            'auditors' => $auditors,
            'filters' => $request->only(['status', 'building_id', 'month', 'year', 'search']),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'frequency' => 'required|in:one_time,weekly,monthly,quarterly',
            'scheduled_date' => 'required|date',
            'auditor_id' => 'required|exists:users,id',
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'Scheduled';

        Audit5sSchedule::create($validated);

        return redirect()->back()->with('success', 'Audit schedule created successfully.');
    }

    public function update(Request $request, Audit5sSchedule $schedule)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'frequency' => 'required|in:one_time,weekly,monthly,quarterly',
            'scheduled_date' => 'required|date',
            'auditor_id' => 'required|exists:users,id',
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'status' => 'required|in:Scheduled,In Progress,Completed,Cancelled',
            'notes' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return redirect()->back()->with('success', "Audit schedule {$schedule->title} updated successfully.");
    }

    public function destroy(Audit5sSchedule $schedule)
    {
        $title = $schedule->title;
        $schedule->delete();

        return redirect()->back()->with('success', "Audit schedule '{$title}' removed successfully.");
    }
}
