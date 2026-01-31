<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Campaign;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::orderByDesc('created_at')
            ->get()
            ->map(fn ($cmp) => [
                'id' => $cmp->id,
                'name' => $cmp->name,
                'type' => $cmp->type,
                'status' => $cmp->status,
                'start_date' => $cmp->start_date ? $cmp->start_date->format('Y-m-d') : null,
                'end_date' => $cmp->end_date ? $cmp->end_date->format('Y-m-d') : null,
                'budget' => $cmp->budget,
            ]);

        return Inertia::render('CRM/Campaigns/Index', [
            'campaigns' => $campaigns,
            'title' => 'Marketing Campaigns'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'status' => 'required|in:planned,active,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
        ]);

        Campaign::create($validated);

        return Redirect::back()->with('success', 'Campaign created successfully.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'status' => 'required|in:planned,active,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
        ]);

        $campaign->update($validated);

        return Redirect::back()->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return Redirect::back()->with('success', 'Campaign deleted successfully.');
    }
}
