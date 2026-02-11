<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NcrController extends Controller
{
    public function index()
    {
        $ncrs = \App\Models\NonConformanceReport::with(['inspection', 'approver'])
            ->latest()
            ->paginate(10);
            
        return inertia('QualityControl/NCR/Index', [
            'ncrs' => $ncrs,
        ]);
    }

    public function show($id)
    {
        // Eager load everything needed for the view
        $ncr = \App\Models\NonConformanceReport::with([
            'inspection.items.masterPoint', 
            'inspection.inspector',
            'approver'
        ])->findOrFail($id);
            
        return inertia('QualityControl/NCR/Show', [
            'ncr' => $ncr,
        ]);
    }

    public function update(Request $request, $id)
    {
        $ncr = \App\Models\NonConformanceReport::findOrFail($id);
        
        $validated = $request->validate([
            'disposition' => 'required|in:rework,scrap,return_to_vendor,use_as_is',
            'action_plan' => 'required|string',
            'approved_by' => 'required|exists:users,id', // or auth user
        ]);

        $ncr->update([
            'disposition' => $validated['disposition'],
            'action_plan' => $validated['action_plan'],
            'approved_by' => $validated['approved_by'],
            'status' => 'closed',
        ]);

        return back()->with('success', 'NCR Disposition updated successfully.');
    }
}
