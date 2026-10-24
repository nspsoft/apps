<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\OvertimeRequest;
use App\Models\Attendance;
use App\Models\PayrollSetting;
use App\Models\Employee;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    public function index()
    {
        $overtimes = OvertimeRequest::whereHas('employee')
            ->with(['employee.department', 'employee.position', 'approver'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($ot) {
                $attendance = Attendance::where('employee_id', $ot->employee_id)
                    ->whereDate('date', $ot->date)
                    ->first();
                
                $ot->attendance = $attendance ? [
                    'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
                    'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
                    'status' => $attendance->status
                ] : null;
                return $ot;
            });

        $employees = Employee::where('is_active', true)
            ->with(['department:id,name', 'position:id,name'])
            ->orderBy('full_name')
            ->get(['id', 'nik', 'full_name', 'department_id', 'position_id']);

        return Inertia::render('HR/Overtime/Index', [
            'overtimes' => $overtimes,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'type' => 'required|in:pre_planned,post_claim',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'auto_approve' => 'nullable|boolean',
        ]);

        $exists = OvertimeRequest::where('employee_id', $validated['employee_id'])
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['date' => 'Karyawan ini sudah memiliki pengajuan lembur yang pending atau disetujui pada tanggal tersebut.']);
        }

        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        $requestedMinutes = $start->diffInMinutes($end);

        if ($requestedMinutes <= 0) {
            return redirect()->back()->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        $status = 'pending';
        $approvedMinutes = null;
        $approvedBy = null;
        $approvedAt = null;

        if (!empty($validated['auto_approve'])) {
            $status = 'approved';
            $approvedMinutes = $requestedMinutes;
            $approvedBy = auth()->id();
            $approvedAt = now();
        }

        OvertimeRequest::create([
            'employee_id' => $validated['employee_id'],
            'type' => $validated['type'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'requested_minutes' => $requestedMinutes,
            'approved_minutes' => $approvedMinutes,
            'reason' => $validated['reason'],
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
        ]);

        return redirect()->back()->with('success', 'Data lembur karyawan berhasil disimpan' . ($status === 'approved' ? ' dan langsung disetujui.' : '.'));
    }

    public function approve(OvertimeRequest $overtime, Request $request)
    {
        // Reconcile with actual attendance
        $attendance = Attendance::where('employee_id', $overtime->employee_id)
            ->whereDate('date', $overtime->date)
            ->first();

        if (!$attendance || !$attendance->clock_out) {
            // No clock out, no overtime approved
            $overtime->update([
                'status' => 'approved',
                'approved_minutes' => 0,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => 'Otomatis disetujui 0 menit karena karyawan tidak memiliki data absensi pulang (Clock Out) pada tanggal ini.'
            ]);
            return redirect()->back()->with('success', 'Pengajuan lembur disetujui dengan 0 menit karena tidak ada absensi pulang.');
        }

        $standardEndStr = PayrollSetting::where('key', 'standard_end_time')->first()->value ?? '17:00';
        $standardEndTime = Carbon::parse($overtime->date->format('Y-m-d') . ' ' . $standardEndStr);
        $clockOutTime = Carbon::parse($attendance->clock_out);

        $actualOvertimeMinutes = 0;
        if ($clockOutTime->greaterThan($standardEndTime)) {
            $actualOvertimeMinutes = $standardEndTime->diffInMinutes($clockOutTime);
        }

        // Approved minutes is the minimum of requested minutes and actual overtime minutes
        $approvedMinutes = min($overtime->requested_minutes, $actualOvertimeMinutes);

        $overtime->update([
            'status' => 'approved',
            'approved_minutes' => $approvedMinutes,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', "Pengajuan lembur berhasil disetujui. Disetujui: {$approvedMinutes} menit (Rencana/Klaim: {$overtime->requested_minutes} menit, Kehadiran Aktual: {$actualOvertimeMinutes} menit).");
    }

    public function reject(OvertimeRequest $overtime, Request $request)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason
        ]);

        return redirect()->back()->with('success', 'Pengajuan lembur telah ditolak.');
    }
}
