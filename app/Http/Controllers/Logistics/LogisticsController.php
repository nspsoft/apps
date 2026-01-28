<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogisticsController extends Controller
{
    public function index(Request $request)
    {
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'items.product'])
            ->whereIn('status', ['draft', 'picking', 'packed']) // Only show DOs that haven't been shipped
            ->when($request->search, function ($q, $search) {
                $q->where('do_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            })
            ->orderBy('delivery_date')
            ->get();

        $vehicles = Vehicle::where('is_active', true)
            ->where('status', 'available')
            ->get();

        return Inertia::render('Logistics/Planning/Index', [
            'deliveryOrders' => $deliveryOrders,
            'vehicles' => $vehicles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function assignVehicle(Request $request)
    {
        $request->validate([
            'delivery_order_ids' => 'required|array',
            'delivery_order_ids.*' => 'exists:delivery_orders,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_name' => 'nullable|string',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);
        
        DeliveryOrder::whereIn('id', $request->delivery_order_ids)
            ->update([
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->license_plate,
                'driver_name' => $request->driver_name ?? $vehicle->driver_name,
                'status' => 'packed', // Update status to packed when scheduled
            ]);

        return redirect()->back()->with('success', 'Vehicles assigned to delivery orders successfully.');
    }
}
