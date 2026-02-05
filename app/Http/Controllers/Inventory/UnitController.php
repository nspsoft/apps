<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Unit::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%");
        }

        $units = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Units/Index', [
            'units' => $units,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:units,code',
            'symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        // Default Company if exists
        $validated['company_id'] = 1;
        $validated['conversion_factor'] = 1; // Default base
        $validated['is_active'] = $validated['is_active'] ?? true;

        Unit::create($validated);

        return redirect()->back()->with('success', 'Unit created successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:units,code,' . $unit->id,
            'symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        // Check if used in products
        if ($unit->products()->exists()) {
            return back()->with('error', 'Cannot delete unit because it is used by products. You can deactivate it instead.');
        }

        $unit->delete();

        return redirect()->back()->with('success', 'Unit deleted successfully.');
    }
}
