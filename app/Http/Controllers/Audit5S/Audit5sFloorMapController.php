<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use App\Models\SafetyDevicePoint;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class Audit5sFloorMapController extends Controller
{
    public function index(Request $request)
    {
        $buildings = Audit5sBuilding::where('is_active', true)
            ->withCount('zones')
            ->orderBy('id')
            ->get();

        $selectedBuildingId = $request->input('building_id', $buildings->first()?->id);
        $currentBuilding = $buildings->firstWhere('id', $selectedBuildingId) ?? $buildings->first();

        $zones = [];
        $safetyDevices = [];
        if ($currentBuilding) {
            $zones = Audit5sZone::where('building_id', $currentBuilding->id)
                ->with(['department:id,name', 'picEmployee:id,nik,full_name,profile_picture,phone,section'])
                ->orderBy('name')
                ->get();

            $safetyDevices = SafetyDevicePoint::where('building_id', $currentBuilding->id)
                ->with(['zone:id,name,code'])
                ->orderBy('code')
                ->get();
        }

        $employees = Employee::where('is_active', true)
            ->select('id', 'nik', 'full_name', 'department_id', 'profile_picture', 'phone')
            ->orderBy('full_name')
            ->get();

        $departments = Department::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Audit5S/FloorMap/Index', [
            'buildings' => $buildings,
            'currentBuilding' => $currentBuilding,
            'zones' => $zones,
            'safetyDevices' => $safetyDevices,
            'employees' => $employees,
            'departments' => $departments,
        ]);
    }

    public function storeZone(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:audit_5s_zones,id',
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'area_type' => 'required|string',
            'department_id' => 'nullable|exists:hr_departments,id',
            'pic_employee_id' => 'nullable|exists:hr_employees,id',
            'map_x_percent' => 'required|numeric|min:0|max:100',
            'map_y_percent' => 'required|numeric|min:0|max:100',
            'custom_pic_photo' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('custom_pic_photo')) {
            $path = $request->file('custom_pic_photo')->store('5s-pic-photos', 'public');
            $validated['custom_pic_photo'] = $path;
        }

        if (!empty($validated['id'])) {
            $zone = Audit5sZone::findOrFail($validated['id']);
            $zone->update($validated);
            $message = "Zone {$zone->name} updated successfully.";
        } else {
            $zone = Audit5sZone::create($validated);
            $message = "Zone {$zone->name} created successfully.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function updateCoordinates(Request $request, Audit5sZone $zone)
    {
        $request->validate([
            'map_x_percent' => 'required|numeric|min:0|max:100',
            'map_y_percent' => 'required|numeric|min:0|max:100',
        ]);

        $zone->update([
            'map_x_percent' => $request->map_x_percent,
            'map_y_percent' => $request->map_y_percent,
        ]);

        return redirect()->back()->with('success', "Koordinat pin {$zone->name} berhasil diperbarui.");
    }

    public function storeBuilding(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:audit_5s_buildings,code',
            'description' => 'nullable|string',
            'floor_plan_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('floor_plan_image')) {
            $path = $request->file('floor_plan_image')->store('5s-floor-plans', 'public');
            $validated['floor_plan_image'] = $path;
        }

        $validated['created_by'] = auth()->id();
        $building = Audit5sBuilding::create($validated);

        return redirect()->route('audit-5s.floor-map', ['building_id' => $building->id])
            ->with('success', "Building {$building->name} added successfully.");
    }

    public function updateBuilding(Request $request, Audit5sBuilding $building)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:audit_5s_buildings,code,' . $building->id,
            'description' => 'nullable|string',
            'floor_plan_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('floor_plan_image')) {
            $path = $request->file('floor_plan_image')->store('5s-floor-plans', 'public');
            $validated['floor_plan_image'] = $path;
        }

        $building->update($validated);

        return redirect()->back()->with('success', "Building {$building->name} updated successfully.");
    }

    public function destroyBuilding(Audit5sBuilding $building)
    {
        $name = $building->name;
        // Soft delete all zones in this building
        $building->zones()->delete();
        $building->delete();

        $firstBuilding = Audit5sBuilding::first();

        return redirect()->route('audit-5s.floor-map', $firstBuilding ? ['building_id' => $firstBuilding->id] : [])
            ->with('success', "Building {$name} and its zones removed successfully.");
    }

    public function destroyZone(Audit5sZone $zone)
    {
        $name = $zone->name;
        $zone->delete();

        return redirect()->back()->with('success', "Zone {$name} removed successfully.");
    }

    public function storeDevicePoint(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:safety_device_points,id',
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'zone_id' => 'nullable|exists:audit_5s_zones,id',
            'device_type' => 'required|string',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'map_x_percent' => 'required|numeric|min:0|max:100',
            'map_y_percent' => 'required|numeric|min:0|max:100',
            'inspection_status' => 'required|string',
            'last_checked_at' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        if (!empty($validated['id'])) {
            $device = SafetyDevicePoint::findOrFail($validated['id']);
            $device->update($validated);
            $message = "Safety Device {$device->name} updated successfully.";
        } else {
            $device = SafetyDevicePoint::create($validated);
            $message = "Safety Device {$device->name} created successfully.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function updateDeviceCoordinates(Request $request, SafetyDevicePoint $device)
    {
        $request->validate([
            'map_x_percent' => 'required|numeric|min:0|max:100',
            'map_y_percent' => 'required|numeric|min:0|max:100',
        ]);

        $device->update([
            'map_x_percent' => $request->map_x_percent,
            'map_y_percent' => $request->map_y_percent,
        ]);

        return redirect()->back()->with('success', "Posisi pin alat keselamatan {$device->name} berhasil disimpan.");
    }

    public function destroyDevicePoint(SafetyDevicePoint $device)
    {
        $name = $device->name;
        $device->delete();

        return redirect()->back()->with('success', "Safety Device {$name} removed successfully.");
    }
}
