<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index()
    {
        return Inertia::render('Manufacturing/Shifts/Index', [
            'shifts' => Shift::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shifts,name',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        Shift::create($validated);

        return redirect()->route('manufacturing.shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shifts,name,' . $shift->id,
            'start_time' => 'nullable|date_format:H:i:s,H:i',
            'end_time' => 'nullable|date_format:H:i:s,H:i',
            'is_active' => 'boolean',
        ]);

        $shift->update($validated);

        return redirect()->route('manufacturing.shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        // Check if shift is used in production entries before deleting
        // For now, just soft delete or delete. 
        // Better to check for usage.
        
        $shift->delete();

        return redirect()->route('manufacturing.shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}
