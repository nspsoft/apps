<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): Response
    {
        $vehicles = Vehicle::query()
            ->when($request->search, function ($q, $search) {
                $q->where('license_plate', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('license_plate')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Logistics/Vehicle/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['search', 'status']),
            'vehicleStatuses' => [
                ['value' => 'available', 'label' => 'Available'],
                ['value' => 'maintenance', 'label' => 'Maintenance'],
                ['value' => 'busy', 'label' => 'Busy'],
            ],
        ]);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Vehicle::create($validated);

        return redirect()->back()->with('success', 'Vehicle created successfully.');
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate,' . $vehicle->id,
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $vehicle->update($validated);

        return redirect()->back()->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Check if vehicle is busy
        if ($vehicle->status === 'busy') {
            return back()->with('error', 'Cannot delete a vehicle that is currently busy.');
        }

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted successfully.');
    }
}
