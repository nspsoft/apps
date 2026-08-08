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
        $date = $request->date ?: Carbon::today()->toDateString();
        $totalActive = Employee::where('is_active', true)->count();
        
        $attendances = Attendance::where('date', $date)->get();
        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $leaveCount = $attendances->whereIn('status', ['leave', 'sick'])->count();
        $absentCount = max(0, $totalActive - ($presentCount + $lateCount + $leaveCount));

        // Recent check-ins
        $recentLogs = Attendance::with(['employee.department'])
            ->where('date', $date)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('clock_in', 'desc')
            ->take(10)
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
            ]
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
        $date = Carbon::today()->toDateString();
        
        // Prevent double scan / duplicate check-in in the database layer (within last 2 minutes)
        $recent = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where(function($q) {
                $q->where('clock_in', '>=', Carbon::now()->subMinutes(2))
                  ->orWhere('clock_out', '>=', Carbon::now()->subMinutes(2));
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

        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');
        $isLate = false;
        
        // Check if employee is in an Office department (starts at 08:00) vs Production shifts
        $deptName = strtolower($employee->department->name ?? '');
        $isOfficeDept = in_array($deptName, ['hr', 'finance', 'purchasing', 'sales', 'it', 'management', 'ppic', 'office', 'general', 'accounting']);
        
        if ($isOfficeDept) {
            // Office Schedule: Late if after 08:00
            if ($timeStr > '08:00:00') {
                $isLate = true;
            }
        } else {
            // Shift Schedule: auto-detect based on current time window
            if ($timeStr >= '05:00:00' && $timeStr < '13:00:00') {
                // Shift 1: 07:00 - 15:00. Late if after 07:00
                if ($timeStr > '07:00:00') {
                    $isLate = true;
                }
            } elseif ($timeStr >= '13:00:00' && $timeStr < '21:00:00') {
                // Shift 2: 15:00 - 23:00. Late if after 15:00
                if ($timeStr > '15:00:00') {
                    $isLate = true;
                }
            } else {
                // Shift 3: 23:00 - 07:00. Late if after 23:00
                if ($timeStr > '23:00:00' || ($timeStr < '05:00:00' && $timeStr > '00:00:00')) {
                    $isLate = true;
                }
            }
        }
        
        $status = $isLate ? 'late' : 'present';

        // Check if there is already an attendance today
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if (!$attendance) {
            // Clock In
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'clock_in' => $now,
                'status' => $status
            ]);
            $action = 'clock_in';
            $message = "Absen masuk berhasil. Selamat pagi {$employee->full_name}, selamat bekerja!";
        } else {
            // Clock Out
            if (empty($attendance->clock_out)) {
                $attendance->update([
                    'clock_out' => $now
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
