<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UatScenario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UatController extends Controller
{
    public function index()
    {
        // Fetch all scenarios ordered by custom_order
        $scenarios = UatScenario::orderBy('custom_order')->get();

        // Group by module for display
        $groupedScenarios = $scenarios->groupBy('module');

        // Calculate progress stats
        $total = $scenarios->count();
        $passed = $scenarios->where('status', 'passed')->count();
        $failed = $scenarios->where('status', 'failed')->count();
        $pending = $scenarios->where('status', 'pending')->count();
        $progress = $total > 0 ? round(($passed / $total) * 100) : 0;

        return Inertia::render('Settings/Uat/Index', [
            'groupedScenarios' => $groupedScenarios,
            'stats' => [
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pending' => $pending,
                'progress' => $progress,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,passed,failed',
            'notes' => 'nullable|string',
        ]);

        $scenario = UatScenario::findOrFail($id);
        
        $data = [
            'status' => $request->status,
            'notes' => $request->notes,
        ];

        // Only update tester info if status changes to passed/failed
        if ($request->status !== 'pending') {
            $data['tested_by'] = auth()->id();
            $data['tested_at'] = now();
        }

        $scenario->update($data);

        return back()->with('success', 'Scenario updated successfully');
    }

    public function reset(Request $request)
    {
        // Reset all or specific module
        if ($request->module) {
            UatScenario::where('module', $request->module)->update([
                'status' => 'pending',
                'tested_by' => null,
                'tested_at' => null,
                'notes' => null,
            ]);
        } else {
            UatScenario::query()->update([
                'status' => 'pending',
                'tested_by' => null,
                'tested_at' => null,
                'notes' => null,
            ]);
        }

        return back()->with('success', 'UAT progress has been reset.');
    }
}
