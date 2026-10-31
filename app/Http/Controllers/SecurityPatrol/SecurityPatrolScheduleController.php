<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrolSchedule;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityPatrolScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = SecurityPatrolSchedule::with(['shift:id,name,start_time,end_time', 'checkpoints:id,code,name,area_zone'])
            ->latest()
            ->paginate(15);

        $shifts = Shift::where('is_active', true)->select('id', 'name', 'start_time', 'end_time')->get();
        $allCheckpoints = SecurityCheckpoint::where('is_active', true)
            ->select('id', 'code', 'name', 'area_zone')
            ->orderBy('code')
            ->get();

        return Inertia::render('SecurityPatrol/Schedules/Index', [
            'schedules' => $schedules,
            'shifts' => $shifts,
            'allCheckpoints' => $allCheckpoints,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'shift_id' => 'nullable|exists:shifts,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'checkpoint_ids' => 'required|array|min:1',
            'checkpoint_ids.*' => 'exists:security_checkpoints,id',
        ]);

        $schedule = SecurityPatrolSchedule::create([
            'name' => $validated['name'],
            'shift_id' => $validated['shift_id'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'checkpoints_count' => count($validated['checkpoint_ids']),
            'is_active' => true,
        ]);

        $syncData = [];
        foreach ($validated['checkpoint_ids'] as $idx => $cpId) {
            $syncData[$cpId] = ['sort_order' => $idx + 1];
        }
        $schedule->checkpoints()->sync($syncData);

        return redirect()->back()->with('success', 'Jadwal shift patroli berhasil dibuat.');
    }

    public function update(Request $request, SecurityPatrolSchedule $schedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'shift_id' => 'nullable|exists:shifts,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'checkpoint_ids' => 'required|array|min:1',
            'checkpoint_ids.*' => 'exists:security_checkpoints,id',
            'is_active' => 'boolean',
        ]);

        $schedule->update([
            'name' => $validated['name'],
            'shift_id' => $validated['shift_id'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'checkpoints_count' => count($validated['checkpoint_ids']),
            'is_active' => $validated['is_active'] ?? $schedule->is_active,
        ]);

        $syncData = [];
        foreach ($validated['checkpoint_ids'] as $idx => $cpId) {
            $syncData[$cpId] = ['sort_order' => $idx + 1];
        }
        $schedule->checkpoints()->sync($syncData);

        return redirect()->back()->with('success', 'Jadwal shift patroli berhasil diperbarui.');
    }

    public function destroy(SecurityPatrolSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal shift patroli berhasil dihapus.');
    }
}
