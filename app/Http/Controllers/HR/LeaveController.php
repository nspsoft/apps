<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Leave;
use App\Models\HR\LeaveBalance;
use App\Models\HR\LeaveType;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['employee.department', 'employee.position', 'leaveType'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $leaves = $query->paginate(15)->withQueryString();

        $employees = Employee::where('is_active', true)
            ->with(['department:id,name', 'position:id,name'])
            ->orderBy('full_name')
            ->get(['id', 'nik', 'full_name', 'department_id', 'position_id']);

        $leaveTypes = LeaveType::where('is_active', true)->get();

        return Inertia::render('HR/Leaves/Index', [
            'leaves' => $leaves,
            'filters' => $request->only(['status']),
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR', 'HR & Payroll']) && !$user->can('hr_payroll.leave_management.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'leave_type_id' => 'required|exists:hr_leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'auto_approve' => 'nullable|boolean',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $totalDays = $start->diffInDays($end) + 1;

        $status = 'pending';
        $approvedBy = null;
        $approvedAt = null;

        if (!empty($validated['auto_approve'])) {
            if ($leaveType->is_paid) {
                $year = $start->year;
                $balance = LeaveBalance::where('employee_id', $validated['employee_id'])
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    if ($balance->used_days + $totalDays > $balance->total_days) {
                        return redirect()->back()->withErrors(['start_date' => 'Sisa kuota cuti karyawan tidak mencukupi untuk persetujuan otomatis.']);
                    }
                    $balance->increment('used_days', $totalDays);
                } else {
                    LeaveBalance::create([
                        'employee_id' => $validated['employee_id'],
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                        'total_days' => 12,
                        'used_days' => $totalDays,
                    ]);
                }
            }

            $status = 'approved';
            $approvedBy = auth()->id();
            $approvedAt = now();
        }

        Leave::create([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
        ]);

        return redirect()->back()->with('success', 'Data pengajuan cuti berhasil disimpan' . ($status === 'approved' ? ' dan langsung disetujui.' : '.'));
    }

    public function approve(Leave $leave)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR', 'HR & Payroll']) && !$user->can('hr_payroll.leave_management.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $leaveType = $leave->leaveType;

        // Deduct balance
        if ($leaveType->is_paid) {
            $year = Carbon::parse($leave->start_date)->year;
            $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();

            if ($balance) {
                // Check again to be safe
                if ($balance->used_days + $leave->total_days > $balance->total_days) {
                     return back()->with('error', 'Approval failed: Insufficient leave balance.');
                }
                
                $balance->increment('used_days', $leave->total_days);
            } else {
                 return back()->with('error', 'Approval failed: Leave balance record not found.');
            }
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(Request $request, Leave $leave)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR', 'HR & Payroll']) && !$user->can('hr_payroll.leave_management.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request rejected.');
    }
}
