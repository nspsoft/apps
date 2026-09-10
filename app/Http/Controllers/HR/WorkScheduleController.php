<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDetail;
use App\Models\PenaltyRule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    public function index(): Response
    {
        $workSchedules = WorkSchedule::with(['details'])
            ->withCount('employees')
            ->orderBy('id')
            ->get();

        $penaltyRules = PenaltyRule::orderBy('id')->get();

        return Inertia::render('HR/WorkSchedules/Index', [
            'workSchedules' => $workSchedules,
            'penaltyRules' => $penaltyRules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:hr_work_schedules,code',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'details' => 'required|array|min:7|max:7',
            'details.*.day_of_week' => 'required|integer|between:0,6',
            'details.*.is_workday' => 'required|boolean',
            'details.*.start_time' => 'nullable|string',
            'details.*.end_time' => 'nullable|string',
            'details.*.break_minutes' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            if (!empty($validated['is_default'])) {
                WorkSchedule::where('is_default', true)->update(['is_default' => false]);
            }

            $schedule = WorkSchedule::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['details'] as $detail) {
                WorkScheduleDetail::create([
                    'work_schedule_id' => $schedule->id,
                    'day_of_week' => $detail['day_of_week'],
                    'is_workday' => $detail['is_workday'],
                    'start_time' => $detail['is_workday'] ? ($detail['start_time'] ? substr($detail['start_time'], 0, 8) : null) : null,
                    'end_time' => $detail['is_workday'] ? ($detail['end_time'] ? substr($detail['end_time'], 0, 8) : null) : null,
                    'break_minutes' => $detail['break_minutes'] ?? 0,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Jadwal kerja berhasil dibuat.');
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:hr_work_schedules,code,' . $workSchedule->id,
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'details' => 'required|array|min:7|max:7',
            'details.*.day_of_week' => 'required|integer|between:0,6',
            'details.*.is_workday' => 'required|boolean',
            'details.*.start_time' => 'nullable|string',
            'details.*.end_time' => 'nullable|string',
            'details.*.break_minutes' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $workSchedule) {
            if (!empty($validated['is_default']) && !$workSchedule->is_default) {
                WorkSchedule::where('id', '!=', $workSchedule->id)->update(['is_default' => false]);
            }

            $workSchedule->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['details'] as $detail) {
                WorkScheduleDetail::updateOrCreate(
                    [
                        'work_schedule_id' => $workSchedule->id,
                        'day_of_week' => $detail['day_of_week'],
                    ],
                    [
                        'is_workday' => $detail['is_workday'],
                        'start_time' => $detail['is_workday'] ? ($detail['start_time'] ? substr($detail['start_time'], 0, 8) : null) : null,
                        'end_time' => $detail['is_workday'] ? ($detail['end_time'] ? substr($detail['end_time'], 0, 8) : null) : null,
                        'break_minutes' => $detail['break_minutes'] ?? 0,
                    ]
                );
            }
        });

        return redirect()->back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->is_default) {
            return redirect()->back()->with('error', 'Jadwal default tidak dapat dihapus.');
        }

        if ($workSchedule->employees()->exists()) {
            return redirect()->back()->with('error', 'Jadwal sedang digunakan oleh karyawan.');
        }

        $workSchedule->delete();

        return redirect()->back()->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    public function updatePenaltyRule(Request $request, PenaltyRule $penaltyRule)
    {
        $validated = $request->validate([
            'rounding_minutes' => 'required|integer|min:1|max:180',
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'deduction_basis' => 'required|in:hourly_rate,fixed_amount',
            'fixed_amount' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        $penaltyRule->update($validated);

        return redirect()->back()->with('success', "Aturan penalti '{$penaltyRule->name}' berhasil diperbarui.");
    }
}
