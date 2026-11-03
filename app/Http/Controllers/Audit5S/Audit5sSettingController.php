<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sChecklist;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Audit5sSettingController extends Controller
{
    public function index(Request $request)
    {
        $checklists = Audit5sChecklist::orderBy('pillar')
            ->orderBy('sort_order')
            ->get();

        $buildings = Audit5sBuilding::withCount('zones')
            ->orderBy('name')
            ->get();

        return Inertia::render('Audit5S/Settings/Index', [
            'checklists' => $checklists,
            'buildings' => $buildings,
        ]);
    }

    public function storeChecklist(Request $request)
    {
        $validated = $request->validate([
            'pillar' => 'required|in:Sort,Set In Order,Shine,Standardize,Sustain',
            'title' => 'required|string|max:255',
            'guideline_notes' => 'nullable|string',
            'weight_points' => 'required|integer|min:1|max:10',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        Audit5sChecklist::create($validated);

        return redirect()->back()->with('success', '5S Checklist item created successfully.');
    }

    public function updateChecklist(Request $request, Audit5sChecklist $checklist)
    {
        $validated = $request->validate([
            'pillar' => 'required|in:Sort,Set In Order,Shine,Standardize,Sustain',
            'title' => 'required|string|max:255',
            'guideline_notes' => 'nullable|string',
            'weight_points' => 'required|integer|min:1|max:10',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $checklist->update($validated);

        return redirect()->back()->with('success', '5S Checklist item updated successfully.');
    }

    public function destroyChecklist(Audit5sChecklist $checklist)
    {
        $title = $checklist->title;
        $checklist->delete();

        return redirect()->back()->with('success', "Checklist item '{$title}' removed successfully.");
    }
}
