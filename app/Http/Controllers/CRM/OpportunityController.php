<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Opportunity;
use App\Models\CRM\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class OpportunityController extends Controller
{
    public function index()
    {
        $opportunities = Opportunity::with('lead')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($opp) => [
                'id' => $opp->id,
                'name' => $opp->name,
                'lead' => $opp->lead ? $opp->lead->name : 'N/A',
                'amount' => $opp->amount,
                'stage' => $opp->stage,
                'close_date' => $opp->close_date ? $opp->close_date->format('Y-m-d') : null,
                'probability' => $opp->probability,
            ]);

        return Inertia::render('CRM/Opportunities/Index', [
            'opportunities' => $opportunities,
            'leads' => Lead::select('id', 'name')->get(),
            'title' => 'Opportunity Tracking'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lead_id' => 'nullable|exists:leads,id',
            'amount' => 'required|numeric|min:0',
            'stage' => 'required|string',
            'close_date' => 'nullable|date',
            'probability' => 'required|integer|min:0|max:100',
        ]);

        Opportunity::create($validated);

        return Redirect::back()->with('success', 'Opportunity created successfully.');
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lead_id' => 'nullable|exists:leads,id',
            'amount' => 'required|numeric|min:0',
            'stage' => 'required|string',
            'close_date' => 'nullable|date',
            'probability' => 'required|integer|min:0|max:100',
        ]);

        $opportunity->update($validated);

        return Redirect::back()->with('success', 'Opportunity updated successfully.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();
        return Redirect::back()->with('success', 'Opportunity deleted successfully.');
    }
}
