<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'required|exists:hr_departments,id',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $position = Position::create($validated);
        $position->load('department:id,name');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Position created successfully.',
                'position' => $position,
            ]);
        }

        return back()->with('success', 'Position created successfully.');
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'required|exists:hr_departments,id',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $position->update($validated);
        $position->load('department:id,name');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Position updated successfully.',
                'position' => $position,
            ]);
        }

        return back()->with('success', 'Position updated successfully.');
    }

    public function destroy(Request $request, Position $position)
    {
        if ($position->employees()->count() > 0) {
            $msg = 'Cannot delete position with assigned employees.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $position->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Position deleted successfully.']);
        }

        return back()->with('success', 'Position deleted successfully.');
    }
}
