<?php

namespace App\Http\Controllers\Employee;

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
        $employee = auth()->user()->employee;
        if (!$employee) {
            $user = auth()->user();
            if ($user && ($user->hasRole('Super Admin') || $user->can('hr_payroll.overtime.view'))) {
                return redirect()->route('hr.overtime.index')->with('info', 'Akun admin tidak ditautkan ke karyawan individu. Menampilkan Permintaan Lembur.');
            }
            return redirect()->route('dashboard')->with('error', 'Anda tidak terdaftar sebagai karyawan.');
        }

        $overtimes = OvertimeRequest::where('employee_id', $employee->id)
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Employee/Overtime/Index', [
            'overtimes' => $overtimes
        ]);
    }

    public function create()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            $user = auth()->user();
            if ($user && ($user->hasRole('Super Admin') || $user->can('hr_payroll.overtime.view'))) {
                return redirect()->route('hr.overtime.index')->with('info', 'Akun admin tidak ditautkan ke karyawan individu.');
            }
            return redirect()->route('dashboard')->with('error', 'Anda tidak terdaftar sebagai karyawan.');
        }

        $employees = Employee::where('is_active', true)
            ->with(['department:id,name', 'position:id,name'])
            ->orderBy('full_name')
            ->get(['id', 'nik', 'full_name', 'department_id', 'position_id']);

        return Inertia::render('Employee/Overtime/Create', [
            'employees' => $employees,
            'currentEmployeeId' => $employee->id,
        ]);
    }

    public function store(Request $request)
    {
        $currentEmployee = auth()->user()->employee;

        $validated = $request->validate([
            'employee_id' => 'nullable|exists:hr_employees,id',
            'type' => 'required|in:pre_planned,post_claim',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        $targetEmployeeId = $validated['employee_id'] ?? $currentEmployee?->id;
        if (!$targetEmployeeId) {
            return redirect()->back()->with('error', 'Karyawan tidak valid atau belum terdaftar.');
        }

        // Check if there is already an overtime request for this date (excluding rejected)
        $exists = OvertimeRequest::where('employee_id', $targetEmployeeId)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['date' => 'Karyawan ini sudah memiliki pengajuan lembur yang pending atau disetujui pada tanggal ini.']);
        }

        // Validate post-claim against actual attendance
        if ($validated['type'] === 'post_claim') {
            $attendance = Attendance::where('employee_id', $targetEmployeeId)
                ->whereDate('date', $validated['date'])
                ->first();

            if (!$attendance || !$attendance->clock_out) {
                return redirect()->back()->withErrors(['date' => 'Karyawan tidak memiliki riwayat absensi pulang (Clock Out) pada tanggal ini.']);
            }

            $standardEndStr = PayrollSetting::where('key', 'standard_end_time')->first()->value ?? '17:00';
            $standardEndTime = Carbon::parse($validated['date'] . ' ' . $standardEndStr);
            $clockOutTime = Carbon::parse($attendance->clock_out);

            if ($clockOutTime->lessThanOrEqualTo($standardEndTime)) {
                return redirect()->back()->withErrors(['date' => 'Karyawan tidak memenuhi syarat lembur (tidak ada kelebihan jam kerja setelah jam ' . $standardEndStr . ').']);
            }
        }

        // Calculate requested minutes
        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        $requestedMinutes = $start->diffInMinutes($end);

        if ($requestedMinutes <= 0) {
            return redirect()->back()->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        OvertimeRequest::create([
            'employee_id' => $targetEmployeeId,
            'type' => $validated['type'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'requested_minutes' => $requestedMinutes,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('employee.overtime.index')->with('success', 'Pengajuan lembur berhasil dikirim.');
    }
}
