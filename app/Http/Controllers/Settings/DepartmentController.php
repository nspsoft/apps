<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::query()
            ->withCount(['employees', 'positions'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $positions = Position::query()
            ->with(['department:id,name'])
            ->withCount('employees')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$s}%")))
            ->when($request->department_id, fn($q, $d) => $q->where('department_id', $d))
            ->orderBy('name')
            ->paginate(20, ['*'], 'pos_page')
            ->withQueryString();

        $allDepartments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Settings/Departments', [
            'departments' => $departments,
            'positions' => $positions,
            'allDepartments' => $allDepartments,
            'filters' => $request->only(['search', 'tab', 'department_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:20|unique:hr_departments,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Department::create($validated);

        return back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:20|unique:hr_departments,code,' . $department->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return back()->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete department with active employees.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted successfully.');
    }
}
