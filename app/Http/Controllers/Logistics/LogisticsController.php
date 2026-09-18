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
            ->whereIn('usage_type', ['logistics', 'both'])
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
            'rit_number' => 'nullable|integer|min:1|max:5',
            'loading_dock' => 'nullable|string|max:30',
            'estimated_departure_time' => 'nullable|string',
            'travel_allowance' => 'nullable|numeric|min:0',
            'travel_allowance_notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);
        
        $driverName = $request->driver_name ?? $vehicle->driver_name;
        $driverUser = \App\Models\User::where('name', $driverName)->first();
        $driverUserId = $driverUser ? $driverUser->id : null;

        // Generate shipment number: SHP-YYMMDD-XXX
        $todayStr = date('ymd');
        $todayCount = DeliveryOrder::where('shipment_number', 'like', "SHP-{$todayStr}-%")->distinct()->count('shipment_number');
        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $shipmentNumber = "SHP-{$todayStr}-{$sequence}";

        $deliveryOrderIds = $request->delivery_order_ids;
        $primaryDoId = $deliveryOrderIds[0];
        
        $travelAllowance = $request->travel_allowance ?? 0;
        $travelAllowanceStatus = $travelAllowance > 0 ? 'requested' : 'none';
        $ritNumber = $request->rit_number ? intval($request->rit_number) : 1;
        $loadingDock = $request->loading_dock ?: 'Dock #1';
        $departureTime = $request->estimated_departure_time ?: null;

        foreach ($deliveryOrderIds as $id) {
            $isPrimary = ($id == $primaryDoId);
            DeliveryOrder::where('id', $id)->update([
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->license_plate,
                'driver_name' => $driverName,
                'driver_user_id' => $driverUserId,
                'shipment_number' => $shipmentNumber,
                'rit_number' => $ritNumber,
                'loading_dock' => $loadingDock,
                'estimated_departure_time' => $departureTime,
                'travel_allowance' => $isPrimary ? $travelAllowance : 0,
                'travel_allowance_notes' => $isPrimary ? $request->travel_allowance_notes : null,
                'travel_allowance_status' => $isPrimary ? $travelAllowanceStatus : 'none',
                'status' => 'packed',
            ]);
        }

        return redirect()->back()->with('success', 'Armada dan Rit ' . $ritNumber . ' berhasil di-assign untuk Shipment ' . $shipmentNumber . '.');
    }
}
