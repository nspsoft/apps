<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use App\Models\HR\AttendanceRequest;
use App\Models\HR\OvertimeRequest;
use App\Models\PayrollSetting;

use App\Imports\AttendanceImport;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Attendance::with(['employee.department', 'employee.position'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        if ($request->search) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }

        if ($request->date) {
            $query->where('date', $request->date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requestsQuery = AttendanceRequest::with(['employee.department'])
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $requestsQuery->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('HR/Attendance/Index', [
            'attendances' => $query->paginate(15)->withQueryString(),
            'attendanceRequests' => $requestsQuery->paginate(10, ['*'], 'requests_page')->withQueryString(),
            'departments' => Department::all(),
            'filters' => $request->only(['search', 'date', 'status']),
        ]);
    }

    public function template()
    {
        return Excel::download(new AttendanceTemplateExport, 'attendance-template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new AttendanceImport, $request->file('file'));
            return redirect()->back()->with('success', 'Attendance data imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing attendance: ' . $e->getMessage());
        }
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ]);

        $today = Carbon::today()->toDateString();
        
        // Check if already clocked in today
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            return redirect()->back()->with('error', 'Employee already clocked in for today.');
        }

        $now = Carbon::now();
        $status = 'present';
        
        // Simple logic: Late if after 08:30
        if ($now->format('H:i') > '08:30') {
            $status = 'late';
        }

        Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $today,
            'clock_in' => $now,
            'status' => $status,
            'location_lat' => $request->lat,
            'location_lng' => $request->lng,
        ]);

        return redirect()->back()->with('success', 'Clock-in recorded successfully.');
    }

    public function clockOut(Request $request, Attendance $attendance)
    {
        if ($attendance->clock_out) {
            return redirect()->back()->with('error', 'Employee already clocked out.');
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Clock-out recorded successfully.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'HR & Payroll', 'IT Administrator']) && !$user->can('hr_payroll.attendance.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'clock_in' => 'nullable|string',
            'clock_out' => 'nullable|string',
            'status' => 'required|in:present,late,absent,leave,sick,overtime',
            'note' => 'nullable|string',
        ]);

        $dateStr = Carbon::parse($validated['date'])->toDateString();

        $clockIn = null;
        if (!empty($validated['clock_in'])) {
            // Check if full datetime or just time
            if (strlen($validated['clock_in']) <= 8) {
                $clockIn = Carbon::parse($dateStr . ' ' . $validated['clock_in']);
            } else {
                $clockIn = Carbon::parse($validated['clock_in']);
            }
        }

        $clockOut = null;
        if (!empty($validated['clock_out'])) {
            if (strlen($validated['clock_out']) <= 8) {
                $clockOut = Carbon::parse($dateStr . ' ' . $validated['clock_out']);
            } else {
                $clockOut = Carbon::parse($validated['clock_out']);
            }
        }

        $attendance->update([
            'date' => $dateStr,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => $validated['status'],
            'note' => $validated['note'],
        ]);

        return redirect()->back()->with('success', 'Attendance record updated successfully.');
    }

    public function dashboard(Request $request)
    {
        return Inertia::render('HR/Attendance/Dashboard');
    }

    public function getDashboardData(Request $request)
    {
        $date = $request->date ?: Carbon::today('Asia/Jakarta')->toDateString();
        $totalActive = Employee::where('is_active', true)->count();
        
        $attendances = Attendance::where('date', $date)->get();
        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $leaveCount = $attendances->whereIn('status', ['leave', 'sick'])->count();
        $absentCount = max(0, $totalActive - ($presentCount + $lateCount + $leaveCount));

        // Recent check-ins / outs (Tampilkan aksi terbaru di urutan paling atas)
        $recentLogs = Attendance::with(['employee.department'])
            ->where('date', $date)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('updated_at', 'desc')
            ->orderByRaw('COALESCE(clock_out, clock_in) DESC')
            ->take(20)
            ->get();

        // 7-day Weekly Trend
        $weeklyLabels = [];
        $weeklyPresent = [];
        $weeklyLate = [];
        $weeklyAbsent = [];

        for ($i = 6; $i >= 0; $i--) {
            $dayDate = Carbon::parse($date)->subDays($i)->toDateString();
            $dayLabel = Carbon::parse($dayDate)->locale('id')->isoFormat('dddd');
            
            $dayAttendances = Attendance::where('date', $dayDate)->get();
            $pres = $dayAttendances->where('status', 'present')->count();
            $lat = $dayAttendances->where('status', 'late')->count();
            $lea = $dayAttendances->whereIn('status', ['leave', 'sick'])->count();
            $abs = max(0, $totalActive - ($pres + $lat + $lea));

            $weeklyLabels[] = $dayLabel . ' (' . Carbon::parse($dayDate)->format('d/m') . ')';
            $weeklyPresent[] = $pres;
            $weeklyLate[] = $lat;
            $weeklyAbsent[] = $abs;
        }

        // Department distribution
        $departments = Department::all();
        $deptLabels = [];
        $deptCounts = [];
        
        foreach ($departments as $dept) {
            $count = Attendance::where('date', $date)
                ->whereIn('status', ['present', 'late'])
                ->whereHas('employee', function ($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                })->count();
            
            if ($count > 0) {
                $deptLabels[] = $dept->name;
                $deptCounts[] = $count;
            }
        }

        if (empty($deptLabels)) {
            $deptLabels[] = 'Belum Ada';
            $deptCounts[] = 0;
        }

        // --- Monthly Leaderboard & Discipline Analytics ---
        $startOfMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($date)->endOfMonth()->toDateString();

        // 1. Top Disciplined (Highest on-time check-ins this month)
        $topDisciplined = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'present')
            ->select('employee_id', \DB::raw('COUNT(*) as on_time_count'))
            ->groupBy('employee_id')
            ->orderByDesc('on_time_count')
            ->take(6)
            ->with(['employee.department'])
            ->get()
            ->map(function ($att, $idx) use ($startOfMonth, $endOfMonth) {
                $totalAtt = Attendance::where('employee_id', $att->employee_id)
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->count();
                $punctualityRate = $totalAtt > 0 ? round(($att->on_time_count / $totalAtt) * 100, 1) : 100;
                return [
                    'rank' => $idx + 1,
                    'employee' => $att->employee,
                    'on_time_count' => (int) $att->on_time_count,
                    'total_attendance' => (int) $totalAtt,
                    'punctuality_rate' => $punctualityRate,
                    'streak_days' => min((int) $att->on_time_count, 30),
                ];
            });

        // 2. Top Late (Highest late count and accumulated late minutes this month)
        $topLate = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'late')
            ->select('employee_id', \DB::raw('COUNT(*) as late_count'), \DB::raw('SUM(late_minutes) as total_late_minutes'))
            ->groupBy('employee_id')
            ->orderByDesc('late_count')
            ->orderByDesc('total_late_minutes')
            ->take(6)
            ->with(['employee.department'])
            ->get()
            ->map(function ($att, $idx) {
                return [
                    'rank' => $idx + 1,
                    'employee' => $att->employee,
                    'late_count' => (int) $att->late_count,
                    'total_late_minutes' => (int) $att->total_late_minutes,
                ];
            });

        // 3. Department punctuality rankings
        $deptRankings = [];
        foreach ($departments as $dept) {
            $totalDeptAtt = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereHas('employee', fn($q) => $q->where('department_id', $dept->id))
                ->count();
            if ($totalDeptAtt > 0) {
                $onTimeDeptAtt = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->where('status', 'present')
                    ->whereHas('employee', fn($q) => $q->where('department_id', $dept->id))
                    ->count();
                $punctuality = round(($onTimeDeptAtt / $totalDeptAtt) * 100, 1);
                $deptRankings[] = [
                    'name' => $dept->name,
                    'total' => $totalDeptAtt,
                    'on_time' => $onTimeDeptAtt,
                    'punctuality' => $punctuality,
                ];
            }
        }
        usort($deptRankings, fn($a, $b) => $b['punctuality'] <=> $a['punctuality']);

        // Kiosk schedule settings
        $kioskSettings = [
            'morning_in_start' => PayrollSetting::getByKey('kiosk_morning_in_start', '07:00'),
            'morning_in_end' => PayrollSetting::getByKey('kiosk_morning_in_end', '08:30'),
            'evening_out_start' => PayrollSetting::getByKey('kiosk_evening_out_start', '16:30'),
            'evening_out_end' => PayrollSetting::getByKey('kiosk_evening_out_end', '20:00'),
            'slider_interval' => (int) PayrollSetting::getByKey('kiosk_slider_interval_seconds', 20),
            'schedule_mode' => PayrollSetting::getByKey('kiosk_schedule_mode', 'auto'),
        ];

        return response()->json([
            'date' => $date,
            'summary' => [
                'total_employees' => $totalActive,
                'present' => $presentCount,
                'late' => $lateCount,
                'leave' => $leaveCount,
                'absent' => $absentCount
            ],
            'recent_logs' => $recentLogs,
            'charts' => [
                'weekly' => [
                    'labels' => $weeklyLabels,
                    'present' => $weeklyPresent,
                    'late' => $weeklyLate,
                    'absent' => $weeklyAbsent
                ],
                'department' => [
                    'labels' => $deptLabels,
                    'counts' => $deptCounts
                ]
            ],
            'leaderboard' => [
                'top_disciplined' => $topDisciplined,
                'top_late' => $topLate,
                'dept_rankings' => $deptRankings,
            ],
            'kiosk_settings' => $kioskSettings,
        ]);
    }

    public function updateKioskSettings(Request $request)
    {
        $validated = $request->validate([
            'morning_in_start' => 'nullable|string',
            'morning_in_end' => 'nullable|string',
            'evening_out_start' => 'nullable|string',
            'evening_out_end' => 'nullable|string',
            'slider_interval' => 'nullable|integer|min:5|max:120',
            'schedule_mode' => 'nullable|string|in:auto,camera_only,leaderboard_only',
        ]);

        foreach ($validated as $key => $val) {
            if ($val !== null) {
                $dbKey = 'kiosk_' . $key;
                if ($key === 'slider_interval') $dbKey = 'kiosk_slider_interval_seconds';
                PayrollSetting::updateOrCreate(
                    ['key' => $dbKey],
                    [
                        'category' => 'kiosk',
                        'label' => ucwords(str_replace('_', ' ', $key)),
                        'value' => (string) $val,
                        'type' => is_numeric($val) ? 'integer' : 'string',
                        'is_active' => true,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan jam tayang kiosk berhasil disimpan.'
        ]);
    }

    public function kiosk(Request $request)
    {
        $employees = Employee::where('is_active', true)
            ->whereNotNull('face_descriptor')
            ->get(['id', 'full_name', 'nik', 'profile_picture', 'department_id', 'face_descriptor']);

        return Inertia::render('HR/Attendance/Kiosk', [
            'employees' => $employees
        ]);
    }

    public function kioskClock(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id'
        ]);

        $employee = Employee::with('department')->findOrFail($request->employee_id);
        $now = Carbon::now('Asia/Jakarta');
        $date = Carbon::today('Asia/Jakarta')->toDateString();
        $timeStr = $now->format('H:i:s');
        
        // Prevent double scan / duplicate check-in in the database layer (within last 2 minutes)
        $recent = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where(function($q) use ($now) {
                $q->where('clock_in', '>=', $now->copy()->subMinutes(2))
                  ->orWhere('clock_out', '>=', $now->copy()->subMinutes(2));
            })->first();

        if ($recent) {
            return response()->json([
                'success' => true,
                'status' => 'ignored',
                'message' => 'Absensi sudah tercatat baru-baru ini.',
                'employee' => $employee,
                'attendance' => $recent
            ]);
        }

        // Check if there is already an attendance today
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        $isClockIn = !$attendance;

        $useRestriction = (bool) PayrollSetting::getByKey('kiosk_attendance_restriction', 0);
        
        // Fetch employee's schedule for today
        $scheduleDetail = $employee->getScheduleForDate($date);

        // Standard shift schedule (with fallback to kiosk settings)
        $kioskMorningStart = PayrollSetting::getByKey('kiosk_morning_in_start', '07:00');
        $kioskEveningStart = PayrollSetting::getByKey('kiosk_evening_out_start', '16:30');

        $standardStartTime = ($scheduleDetail && $scheduleDetail->is_workday && $scheduleDetail->start_time)
            ? Carbon::parse($date . ' ' . $scheduleDetail->start_time, 'Asia/Jakarta')
            : Carbon::parse($date . ' ' . $kioskMorningStart, 'Asia/Jakarta');

        $standardEndTime = ($scheduleDetail && $scheduleDetail->is_workday && $scheduleDetail->end_time)
            ? Carbon::parse($date . ' ' . $scheduleDetail->end_time, 'Asia/Jakarta')
            : Carbon::parse($date . ' ' . $kioskEveningStart, 'Asia/Jakarta');

        if ($standardEndTime->lessThan($standardStartTime)) {
            $standardEndTime->addDay();
        }

        if ($isClockIn) {
            // Validasi Absen Masuk Berdasarkan Jadwal Shift Karyawan (Opsi A)
            if ($scheduleDetail && !$scheduleDetail->is_workday) {
                $allowHolidayWithoutSpl = (bool) PayrollSetting::getByKey('kiosk_allow_holiday_without_spl', 0);
                if (!$allowHolidayWithoutSpl) {
                    $hasOvertime = OvertimeRequest::where('employee_id', $employee->id)
                        ->where('overtime_date', $date)
                        ->where('status', 'approved')
                        ->exists();

                    if (!$hasOvertime) {
                        return response()->json([
                            'success' => false,
                            'status' => 'rejected_schedule',
                            'message' => 'Maaf, hari ini bukan jadwal kerja Anda dan tidak ada SPL (Surat Perintah Lembur) yang disetujui.',
                            'employee' => $employee
                        ]);
                    }
                }
            }

            $earliestInMin = (int) PayrollSetting::getByKey('kiosk_earliest_in_minutes', 120);
            $latestInMin = (int) PayrollSetting::getByKey('kiosk_latest_in_minutes', 240);

            $earliestIn = $standardStartTime->copy()->subMinutes($earliestInMin);
            $latestIn = $standardStartTime->copy()->addMinutes($latestInMin);

            if ($now->lessThan($earliestIn)) {
                return response()->json([
                    'success' => false,
                    'status' => 'too_early_in',
                    'message' => 'Terlalu awal untuk absen masuk. Jam masuk Anda: ' . $standardStartTime->format('H:i') . ', absen masuk dibuka mulai pukul ' . $earliestIn->format('H:i') . ' WIB.',
                    'employee' => $employee
                ]);
            }

            if ($now->greaterThan($latestIn)) {
                return response()->json([
                    'success' => false,
                    'status' => 'too_late_in',
                    'message' => 'Sudah melewati batas waktu absen masuk. Jam masuk Anda: ' . $standardStartTime->format('H:i') . ', batas akhir absen pukul ' . $latestIn->format('H:i') . ' WIB.',
                    'employee' => $employee
                ]);
            }
        } else {
            // Karyawan SUDAH absen masuk hari ini -> Validasi Ketat Absen Pulang (Clock Out)
            if (!empty($attendance->clock_out)) {
                return response()->json([
                    'success' => true,
                    'status' => 'ignored',
                    'message' => 'Anda sudah melakukan absen masuk (' . Carbon::parse($attendance->clock_in, 'Asia/Jakarta')->format('H:i') . ') dan absen pulang (' . Carbon::parse($attendance->clock_out, 'Asia/Jakarta')->format('H:i') . ') hari ini.',
                    'employee' => $employee,
                    'attendance' => $attendance
                ]);
            }

            // 1. Proteksi Minimal Durasi Kerja (Wajib minimal 4 jam atau tidak boleh langsung pulang di pagi hari)
            $clockInTime = Carbon::parse($attendance->clock_in, 'Asia/Jakarta');
            $workedMinutes = $clockInTime->diffInMinutes($now);
            $minWorkHours = (int) PayrollSetting::getByKey('kiosk_min_work_hours', 4);
            $minWorkMinutes = $minWorkHours * 60;

            if ($workedMinutes < $minWorkMinutes) {
                $hoursWorked = round($workedMinutes / 60, 1);
                return response()->json([
                    'success' => false,
                    'status' => 'too_early_out',
                    'message' => "Minimal jam kerja belum terpenuhi. Anda baru absen masuk pukul {$clockInTime->format('H:i')} WIB ({$hoursWorked} jam yang lalu). Minimal durasi kerja adalah {$minWorkHours} jam.",
                    'employee' => $employee
                ]);
            }

            // 2. Proteksi Range Jam Pulang (Hanya boleh absen pulang mendekati jam pulang atau di range jam sibuk pulang)
            $earliestOutMin = (int) PayrollSetting::getByKey('kiosk_earliest_out_minutes', 60);
            $earliestOut = $standardEndTime->copy()->subMinutes($earliestOutMin);

            if ($now->lessThan($earliestOut)) {
                return response()->json([
                    'success' => false,
                    'status' => 'too_early_out',
                    'message' => "Belum waktunya absen pulang. Jam pulang Anda: {$standardEndTime->format('H:i')} WIB. Absen pulang dibuka mulai pukul {$earliestOut->format('H:i')} WIB.",
                    'employee' => $employee
                ]);
            }
        }

        $isLate = false;
        $lateMinutes = 0;

        if ($scheduleDetail && $scheduleDetail->is_workday && $scheduleDetail->start_time) {
            $standardStartTime = Carbon::parse($date . ' ' . $scheduleDetail->start_time);
            if ($now->greaterThan($standardStartTime)) {
                $isLate = true;
                $lateMinutes = $now->diffInMinutes($standardStartTime);
            }
        } else {
            // Fallback: Check department / time windows
            $deptName = strtolower($employee->department->name ?? '');
            $isOfficeDept = in_array($deptName, ['hr', 'finance', 'purchasing', 'sales', 'it', 'management', 'ppic', 'office', 'general', 'accounting']);
            
            if ($isOfficeDept) {
                if ($timeStr > '08:00:00') {
                    $isLate = true;
                    $lateMinutes = $now->diffInMinutes(Carbon::parse($date . ' 08:00:00'));
                }
            } else {
                if ($timeStr >= '05:00:00' && $timeStr < '13:00:00') {
                    if ($timeStr > '07:00:00') {
                        $isLate = true;
                        $lateMinutes = $now->diffInMinutes(Carbon::parse($date . ' 07:00:00'));
                    }
                } elseif ($timeStr >= '13:00:00' && $timeStr < '21:00:00') {
                    if ($timeStr > '15:00:00') {
                        $isLate = true;
                        $lateMinutes = $now->diffInMinutes(Carbon::parse($date . ' 15:00:00'));
                    }
                } else {
                    if ($timeStr > '23:00:00' || ($timeStr < '05:00:00' && $timeStr > '00:00:00')) {
                        $isLate = true;
                        $lateMinutes = 1;
                    }
                }
            }
        }

        $lateRule = \App\Models\PenaltyRule::getActiveRule('late');
        $penaltyLateMinutes = $lateRule ? $lateRule->calculatePenaltyMinutes($lateMinutes) : $lateMinutes;
        $status = $isLate ? 'late' : 'present';

        if (!$attendance) {
            // Clock In
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'clock_in' => $now,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'penalty_late_minutes' => $penaltyLateMinutes,
            ]);
            $action = 'clock_in';
            $message = "Absen masuk berhasil. Selamat pagi {$employee->full_name}, selamat bekerja!";
        } else {
            // Clock Out
            if (empty($attendance->clock_out)) {
                $earlyLeaveMinutes = 0;
                $overtimeMinutes = 0;

                if ($scheduleDetail && $scheduleDetail->is_workday && $scheduleDetail->end_time) {
                    $standardEndTime = Carbon::parse($date . ' ' . $scheduleDetail->end_time);
                    if ($now->lessThan($standardEndTime)) {
                        $earlyLeaveMinutes = $standardEndTime->diffInMinutes($now);
                    } elseif ($now->greaterThan($standardEndTime)) {
                        $overtimeMinutes = $now->diffInMinutes($standardEndTime);
                    }
                }

                $earlyRule = \App\Models\PenaltyRule::getActiveRule('early_leave');
                $penaltyEarlyMinutes = $earlyRule ? $earlyRule->calculatePenaltyMinutes($earlyLeaveMinutes) : $earlyLeaveMinutes;

                $attendance->update([
                    'clock_out' => $now,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'penalty_early_leave_minutes' => $penaltyEarlyMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                ]);
                $action = 'clock_out';
                $message = "Absen pulang berhasil. Terima kasih {$employee->full_name}, hati-hati di jalan!";
            } else {
                return response()->json([
                    'success' => true,
                    'status' => 'ignored',
                    'message' => 'Anda sudah melakukan absen masuk dan pulang hari ini.',
                    'employee' => $employee,
                    'attendance' => $attendance
                ]);
            }
        }

        $attendance->load(['employee.department']);

        return response()->json([
            'success' => true,
            'status' => $action,
            'message' => $message,
            'employee' => $employee,
            'attendance' => $attendance
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'HR & Payroll', 'IT Administrator']) && !$user->can('hr_payroll.attendance.delete'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $attendance->delete();
        return redirect()->back()->with('success', 'Attendance record deleted successfully.');
    }
}
