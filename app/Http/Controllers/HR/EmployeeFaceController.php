<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\Storage;

class EmployeeFaceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all'); // 'all', 'pending', 'registered'
        $departmentId = $request->input('department_id');

        // Total active employees for metrics
        $baseQuery = Employee::query()->where('is_active', true);

        $totalEmployees = (clone $baseQuery)->count();
        $registeredCount = (clone $baseQuery)->whereNotNull('face_descriptor')->count();
        $pendingCount = (clone $baseQuery)->whereNull('face_descriptor')->count();
        $completionRate = $totalEmployees > 0 ? round(($registeredCount / $totalEmployees) * 100) : 0;

        // Filtered Query for the table
        $query = Employee::with(['department:id,name', 'position:id,name'])
            ->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'pending') {
            $query->whereNull('face_descriptor');
        } elseif ($status === 'registered') {
            $query->whereNotNull('face_descriptor');
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->orderBy('full_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('HR/FaceRegistration/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'department_id' => $departmentId,
            ],
            'metrics' => [
                'total_employees' => $totalEmployees,
                'registered_count' => $registeredCount,
                'pending_count' => $pendingCount,
                'completion_rate' => $completionRate,
            ],
        ]);
    }

    public function show(Employee $employee)
    {
        return Inertia::render('HR/Employees/FaceRegistration', [
            'employee' => $employee
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $request->validate([
            'face_descriptor' => 'required|string',
            'face_photo' => 'nullable|string',
            'redirect_to' => 'nullable|string',
        ]);

        if ($request->face_photo) {
            // Decode base64 image
            $image_parts = explode(";base64,", $request->face_photo);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $filename = 'employees/face_' . $employee->id . '_' . time() . '.jpg';
                
                // Store in public disk
                Storage::disk('public')->put($filename, $image_base64);
                
                // Delete old profile picture if exists
                if ($employee->profile_picture) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }
                
                // Update profile_picture path
                $employee->profile_picture = $filename;
            }
        }

        $employee->face_descriptor = $request->face_descriptor;
        $employee->save();

        $redirectRoute = $request->filled('redirect_to')
            ? $request->input('redirect_to')
            : route('hr.face-registration.index');

        return redirect($redirectRoute)->with('success', 'Data Face ID berhasil didaftarkan untuk ' . $employee->full_name . '.');
    }

    public function destroy(Employee $employee)
    {
        $employee->face_descriptor = null;
        
        if ($employee->profile_picture && strpos($employee->profile_picture, 'employees/face_') === 0) {
            Storage::disk('public')->delete($employee->profile_picture);
            $employee->profile_picture = null;
        }

        $employee->save();

        return redirect()->back()->with('success', 'Data Face ID berhasil dihapus untuk ' . $employee->full_name . '.');
    }
}
