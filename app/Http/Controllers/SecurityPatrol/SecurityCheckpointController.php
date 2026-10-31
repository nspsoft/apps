<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\SecurityCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SecurityCheckpointController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityCheckpoint::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('area_zone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('area_zone')) {
            $query->where('area_zone', $request->area_zone);
        }

        $checkpoints = $query->orderBy('code')->paginate(15)->withQueryString();

        $zones = SecurityCheckpoint::whereNotNull('area_zone')
            ->distinct()
            ->pluck('area_zone');

        $company = \App\Models\Company::first();
        $companyData = [
            'name' => $company?->name ?: 'PT. JIDOKA RESULT INDONESIA',
            'logo' => $company?->logo ?: '/storage/logos/Po8YgfBcC9PjjRhnNcPRZH0u9UMDb5MIv8MUXnXe.png',
        ];

        $allActiveCheckpoints = SecurityCheckpoint::where('is_active', true)->orderBy('code')->get();

        return Inertia::render('SecurityPatrol/Checkpoints/Index', [
            'checkpoints' => $checkpoints,
            'allActiveCheckpoints' => $allActiveCheckpoints,
            'zones' => $zones,
            'company' => $companyData,
            'filters' => $request->only(['search', 'area_zone']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:security_checkpoints,code',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'area_zone' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius' => 'required|integer|min:10|max:500',
            'sop_items' => 'nullable|array',
        ]);

        $validated['qr_token'] = 'CP-TOKEN-' . Str::upper(Str::random(12)) . '-' . Str::slug($validated['code']);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = true;

        SecurityCheckpoint::create($validated);

        return redirect()->back()->with('success', 'Titik checkpoint pos berhasil ditambahkan.');
    }

    public function update(Request $request, SecurityCheckpoint $checkpoint)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:security_checkpoints,code,' . $checkpoint->id,
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'area_zone' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius' => 'required|integer|min:10|max:500',
            'sop_items' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $checkpoint->update($validated);

        return redirect()->back()->with('success', 'Titik checkpoint pos berhasil diperbarui.');
    }

    public function destroy(SecurityCheckpoint $checkpoint)
    {
        $checkpoint->delete();

        return redirect()->back()->with('success', 'Titik checkpoint pos berhasil dihapus.');
    }

    public function regenerateToken(SecurityCheckpoint $checkpoint)
    {
        $checkpoint->update([
            'qr_token' => 'CP-TOKEN-' . Str::upper(Str::random(12)) . '-' . Str::slug($checkpoint->code),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'QR Token berhasil diperbarui/di-rotasi.');
    }
}
