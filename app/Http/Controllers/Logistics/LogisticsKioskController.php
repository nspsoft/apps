<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogisticsKioskController extends Controller
{
    /**
     * Render the Logistics Kiosk TV view
     */
    public function index(Request $request): Response
    {
        $dateParam = $request->input('date', 'today');
        $data = $this->getKioskData($dateParam);

        return Inertia::render('Logistics/Kiosk', [
            'initialData' => $data,
            'selectedDateFilter' => $dateParam,
        ]);
    }

    /**
     * API endpoint for realtime background polling
     */
    public function fetchData(Request $request): JsonResponse
    {
        $dateParam = $request->input('date', 'today');
        $data = $this->getKioskData($dateParam);

        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Aggregate all kiosk data for the selected date
     */
    protected function getKioskData(string $dateParam): array
    {
        $targetDate = match ($dateParam) {
            'yesterday' => Carbon::yesterday(),
            'tomorrow' => Carbon::tomorrow(),
            'today' => Carbon::today(),
            default => Carbon::parse($dateParam)->isValid() ? Carbon::parse($dateParam) : Carbon::today(),
        };

        $dateString = $targetDate->toDateString();
        $dateFormatted = $targetDate->translatedFormat('d M Y');
        $dayName = $targetDate->translatedFormat('l');

        // 1. Fetch all logistics vehicles
        $vehicles = Vehicle::where('is_active', true)
            ->whereIn('usage_type', ['logistics', 'both'])
            ->orderBy('license_plate')
            ->get();

        // 2. Fetch all DOs for the target date
        $deliveryOrders = DeliveryOrder::with(['customer', 'items.product', 'items.unit', 'items.location', 'vehicle'])
            ->whereDate('delivery_date', $dateString)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('rit_number')
            ->orderBy('created_at')
            ->get();

        // Group DOs by vehicle
        $dosByVehicle = $deliveryOrders->groupBy(function ($do) {
            return $do->vehicle_id ? (string) $do->vehicle_id : ($do->vehicle_number ?: 'unassigned');
        });

        // 3. Build Multi-Rit Matrix per Truck
        $matrixRows = [];
        $totalDailyTonnageAll = 0.0;
        $totalRitsDone = 0;
        $totalRitsRunning = 0;
        $totalRitsWaiting = 0;

        foreach ($vehicles as $vehicle) {
            $vehicleDos = $dosByVehicle->get((string) $vehicle->id, collect());
            
            // Extract Rit 1, Rit 2, Rit 3
            $rit1 = $this->formatRitSlot($vehicleDos->where('rit_number', 1)->first());
            $rit2 = $this->formatRitSlot($vehicleDos->where('rit_number', 2)->first());
            $rit3 = $this->formatRitSlot($vehicleDos->where('rit_number', 3)->first());

            // If no explicit rit_number was set, assign them in sequence
            if (!$rit1 && !$rit2 && !$rit3 && $vehicleDos->isNotEmpty()) {
                $sorted = $vehicleDos->values();
                if (isset($sorted[0])) $rit1 = $this->formatRitSlot($sorted[0], 1);
                if (isset($sorted[1])) $rit2 = $this->formatRitSlot($sorted[1], 2);
                if (isset($sorted[2])) $rit3 = $this->formatRitSlot($sorted[2], 3);
            }

            $vehicleTonnage = 0.0;
            foreach ([$rit1, $rit2, $rit3] as $r) {
                if ($r) {
                    $vehicleTonnage += $r['weight_ton'];
                    if ($r['status_code'] === 'delivered') {
                        $totalRitsDone++;
                    } elseif (in_array($r['status_code'], ['shipped', 'on_road'])) {
                        $totalRitsRunning++;
                    } else {
                        $totalRitsWaiting++;
                    }
                }
            }

            $totalDailyTonnageAll += $vehicleTonnage;

            // Only show active vehicles or vehicles that have scheduled deliveries today
            $hasSchedules = $rit1 || $rit2 || $rit3;

            $matrixRows[] = [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'vehicle_type' => $vehicle->vehicle_type ?? 'Truck',
                'driver_name' => $vehicle->driver_name ?? ($vehicleDos->first()?->driver_name ?? 'Supir Reguler'),
                'max_capacity_ton' => (float) ($vehicle->capacity_ton ?? 10.0),
                'total_load_ton' => round($vehicleTonnage, 2),
                'has_schedules' => $hasSchedules,
                'rit_1' => $rit1,
                'rit_2' => $rit2,
                'rit_3' => $rit3,
            ];
        }

        // Include unassigned scheduled DOs if any
        $unassignedDos = $dosByVehicle->get('unassigned', collect());
        if ($unassignedDos->isNotEmpty()) {
            $unassignedRit1 = $this->formatRitSlot($unassignedDos->first(), 1);
            $matrixRows[] = [
                'vehicle_id' => 0,
                'license_plate' => 'PENDING ARMADA',
                'vehicle_type' => 'Belum Di-Assign',
                'driver_name' => 'TBA',
                'max_capacity_ton' => 0,
                'total_load_ton' => round($unassignedDos->sum('total_weight_kg') / 1000, 2),
                'has_schedules' => true,
                'rit_1' => $unassignedRit1,
                'rit_2' => null,
                'rit_3' => null,
            ];
        }

        // Sort: Vehicles with schedules first
        usort($matrixRows, fn($a, $b) => $b['has_schedules'] <=> $a['has_schedules']);

        // 4. Slide 2: Active Fleet Radar (Live GPS & ETA)
        $activeFleetRadar = [];
        $simulatedCorridors = [
            ['name' => 'Kawasan KIIC / Karawang', 'dist' => '18.4 km', 'speed' => '54 km/h', 'lat' => -6.3200, 'lng' => 107.2900],
            ['name' => 'Kawasan MM2100 / Cikarang', 'dist' => '8.2 km', 'speed' => '42 km/h', 'lat' => -6.2900, 'lng' => 107.1000],
            ['name' => 'Kawasan EJIP / Lippo Cikarang', 'dist' => '12.6 km', 'speed' => '48 km/h', 'lat' => -6.3300, 'lng' => 107.1400],
            ['name' => 'Sunter / Jakarta Utara', 'dist' => '24.1 km', 'speed' => '62 km/h', 'lat' => -6.1400, 'lng' => 106.8800],
            ['name' => 'Purwakarta / BIC', 'dist' => '45.0 km', 'speed' => '68 km/h', 'lat' => -6.4500, 'lng' => 107.4500],
        ];

        $idx = 0;
        foreach ($vehicles as $v) {
            $vDos = $dosByVehicle->get((string) $v->id, collect());
            $activeDo = $vDos->whereIn('status', ['shipped'])->first();
            
            if ($activeDo) {
                $corridor = $simulatedCorridors[$idx % count($simulatedCorridors)];
                $idx++;

                $activeFleetRadar[] = [
                    'vehicle_id' => $v->id,
                    'license_plate' => $v->license_plate,
                    'vehicle_type' => $v->vehicle_type ?? 'Truck',
                    'driver_name' => $activeDo->driver_name ?? $v->driver_name ?? 'Supir',
                    'customer_name' => $activeDo->customer?->name ?? $activeDo->shipping_name ?? 'Customer',
                    'do_number' => $activeDo->do_number,
                    'rit_number' => $activeDo->rit_number ?? 1,
                    'status' => 'ON WAY',
                    'destination_area' => $corridor['name'],
                    'remaining_distance' => $corridor['dist'],
                    'speed' => $corridor['speed'],
                    'eta' => now()->addMinutes(rand(15, 60))->format('H:i'),
                    'lat' => $corridor['lat'],
                    'lng' => $corridor['lng'],
                ];
            }
        }

        // 5. Slide 3: Manifest Barang per Delivery Order & Statusnya
        $doItemsManifest = $deliveryOrders->map(function ($do) {
            $totalItems = $do->items->count();
            $loadedItems = $do->items->where('is_loaded', true)->count();
            $progressPct = $totalItems > 0 
                ? round(($loadedItems / $totalItems) * 100) 
                : (in_array($do->status, ['delivered', 'shipped', 'on_road']) ? 100 : 0);

            return [
                'id' => $do->id,
                'do_number' => $do->do_number,
                'customer_name' => $do->customer?->name ?? $do->shipping_name ?? 'Customer',
                'destination' => $do->shipping_address ?? ($do->customer?->city ?? 'Area Pengiriman'),
                'truck' => $do->vehicle?->license_plate ?? $do->vehicle_number ?? 'Belum Di-Assign',
                'truck_type' => $do->vehicle?->vehicle_type ?? 'Truck',
                'driver_name' => $do->driver_name ?? $do->vehicle?->driver_name ?? 'Supir',
                'rit_number' => $do->rit_number ?? 1,
                'loading_dock' => $do->loading_dock ?? 'Dock #1',
                'departure_time' => $do->estimated_departure_time ? substr($do->estimated_departure_time, 0, 5) : '08:00',
                'weight_ton' => round(($do->total_weight_kg ?: $do->items->sum('kg_delivered') ?: 5000) / 1000, 2),
                'status_code' => $do->status,
                'status_label' => match ($do->status) {
                    'delivered' => 'Terkirim',
                    'shipped', 'on_road' => 'On The Road',
                    'packed' => 'Siap / Staging',
                    'picking' => 'Picking',
                    'draft' => 'Draft Rencana',
                    default => strtoupper($do->status),
                },
                'total_items' => $totalItems,
                'loaded_items' => $loadedItems,
                'progress_pct' => $progressPct,
                'items' => $do->items->map(function ($item, $itemIdx) use ($do) {
                    $itemStatus = 'Menunggu Muat';
                    $itemBadge = 'waiting';
                    if ($do->status === 'delivered') {
                        $itemStatus = 'Terkirim (Delivered)';
                        $itemBadge = 'delivered';
                    } elseif (in_array($do->status, ['shipped', 'on_road'])) {
                        $itemStatus = 'On Truck (In Transit)';
                        $itemBadge = 'in_transit';
                    } elseif ($item->is_loaded) {
                        $itemStatus = 'Termuat di Truk';
                        $itemBadge = 'loaded';
                    } elseif ($do->status === 'packed') {
                        $itemStatus = 'Siap di Staging Dock';
                        $itemBadge = 'staged';
                    } elseif ($do->status === 'picking') {
                        $itemStatus = 'Proses Picking';
                        $itemBadge = 'picking';
                    }

                    return [
                        'id' => $item->id,
                        'no' => $itemIdx + 1,
                        'product_code' => $item->product?->sku ?? ('SKU-' . str_pad($item->product_id ?? ($itemIdx + 1), 4, '0', STR_PAD_LEFT)),
                        'product_name' => $item->product?->name ?? 'Produk Manufaktur',
                        'qty_ordered' => (float) $item->qty_ordered,
                        'qty_delivered' => (float) ($item->qty_delivered ?: $item->qty_ordered),
                        'unit' => $item->unit?->code ?? $item->unit?->name ?? 'PCS',
                        'weight_kg' => (float) ($item->kg_delivered ?: 0),
                        'batch_number' => $item->batch_number ?? '-',
                        'location' => $item->location?->name ?? 'Staging Area',
                        'is_loaded' => (bool) $item->is_loaded,
                        'status' => $itemStatus,
                        'status_badge' => $itemBadge,
                    ];
                })->values()->all(),
            ];
        })->values();

        $manifestSummary = [
            'total_dos' => $doItemsManifest->count(),
            'total_items_count' => $doItemsManifest->sum('total_items'),
            'total_tonnage' => round($doItemsManifest->sum('weight_ton'), 2),
            'total_loaded_items' => $doItemsManifest->sum('loaded_items'),
            'overall_loading_pct' => $doItemsManifest->sum('total_items') > 0 
                ? round(($doItemsManifest->sum('loaded_items') / $doItemsManifest->sum('total_items')) * 100) 
                : 100,
        ];

        // Tomorrow's Preview Data (retained for backward compatibility)
        $tomorrowDate = Carbon::tomorrow()->toDateString();
        $tomorrowDos = DeliveryOrder::with(['customer', 'vehicle'])
            ->whereDate('delivery_date', $tomorrowDate)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $tomorrowSummary = [
            'date_formatted' => Carbon::tomorrow()->translatedFormat('l, d M Y'),
            'total_orders' => $tomorrowDos->count(),
            'total_tonnage' => round($tomorrowDos->sum('total_weight_kg') / 1000, 2),
            'allocated_trucks' => $tomorrowDos->pluck('vehicle_id')->filter()->unique()->count(),
            'unallocated_orders' => $tomorrowDos->whereNull('vehicle_id')->count(),
            'orders' => $tomorrowDos->map(fn($do) => [
                'do_number' => $do->do_number,
                'customer' => $do->customer?->name ?? $do->shipping_name ?? '-',
                'rit' => $do->rit_number ?? 1,
                'truck' => $do->vehicle?->license_plate ?? 'Belum Di-Assign',
                'dock' => $do->loading_dock ?? 'Dock #1',
                'weight_ton' => round(($do->total_weight_kg ?: 5000) / 1000, 2),
                'status' => $do->status,
            ]),
        ];

        // 6. Slide 4: Rich Analytics & Chart Data
        $totalScheduled = $deliveryOrders->count();
        $totalDispatchedTrucks = $deliveryOrders->whereIn('status', ['shipped', 'delivered'])->pluck('vehicle_id')->filter()->unique()->count();
        $onTimeDepartureRate = $totalScheduled > 0 ? round((($totalRitsDone + $totalRitsRunning) / max(1, $totalScheduled)) * 100) : 100;

        // Hourly Dispatch Trend (06:00 to 18:00 in 2-hour windows)
        $hourlyLabels = ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
        $hourlyTonnage = [0, 0, 0, 0, 0, 0, 0];
        $hourlyTrucks = [0, 0, 0, 0, 0, 0, 0];

        foreach ($deliveryOrders as $do) {
            $time = $do->estimated_departure_time ?: '08:00';
            $hour = (int) substr($time, 0, 2);
            $weightTon = round(($do->total_weight_kg ?: 5000) / 1000, 2);
            
            $slot = match (true) {
                $hour < 8 => 0,
                $hour < 10 => 1,
                $hour < 12 => 2,
                $hour < 14 => 3,
                $hour < 16 => 4,
                $hour < 18 => 5,
                default => 6,
            };
            $hourlyTonnage[$slot] += $weightTon;
            $hourlyTrucks[$slot] += 1;
        }
        $hourlyTonnage = array_map(fn($v) => round($v, 1), $hourlyTonnage);

        // Truck Capacity vs Actual Load (Top scheduled trucks)
        $truckUtilizationLabels = [];
        $truckActualLoad = [];
        $truckMaxCapacity = [];
        $truckUtilizationPcts = [];

        foreach (array_slice(array_filter($matrixRows, fn($r) => $r['has_schedules'] && $r['vehicle_id'] > 0), 0, 6) as $r) {
            $truckUtilizationLabels[] = $r['license_plate'];
            $truckActualLoad[] = $r['total_load_ton'];
            $truckMaxCapacity[] = $r['max_capacity_ton'];
            $truckUtilizationPcts[] = $r['max_capacity_ton'] > 0 ? round(($r['total_load_ton'] / $r['max_capacity_ton']) * 100) : 0;
        }

        // Status Distribution (Doughnut)
        $statusCounts = [
            'delivered' => $deliveryOrders->where('status', 'delivered')->count(),
            'on_road' => $deliveryOrders->whereIn('status', ['shipped', 'on_road'])->count(),
            'packed' => $deliveryOrders->where('status', 'packed')->count(),
            'staging_or_draft' => $deliveryOrders->whereIn('status', ['draft', 'picking'])->count(),
        ];

        // Dock Throughput Breakdown (Dock #1 to #4)
        $dockThroughput = [];
        for ($d = 1; $d <= 4; $d++) {
            $dockName = 'Dock #' . $d;
            $dockDos = $deliveryOrders->filter(fn($do) => str_contains($do->loading_dock ?? '', (string)$d));
            $dockThroughput[] = [
                'name' => $dockName,
                'tonnage' => round($dockDos->sum('total_weight_kg') / 1000, 1),
                'do_count' => $dockDos->count(),
                'active_truck' => $dockDos->first()?->vehicle?->license_plate ?? 'Standby',
                'status' => $dockDos->isNotEmpty() ? 'OPERASIONAL' : 'STANDBY',
            ];
        }

        $analyticsCharts = [
            'hourly_trend' => [
                'labels' => $hourlyLabels,
                'tonnage' => $hourlyTonnage,
                'trucks' => $hourlyTrucks,
            ],
            'truck_utilization' => [
                'labels' => $truckUtilizationLabels,
                'actual_ton' => $truckActualLoad,
                'capacity_ton' => $truckMaxCapacity,
                'percentages' => $truckUtilizationPcts,
            ],
            'status_distribution' => [
                'labels' => ['Terkirim (Delivered)', 'On The Road', 'Siap di Dock', 'Proses Picking / Draft'],
                'data' => [
                    $statusCounts['delivered'],
                    $statusCounts['on_road'],
                    $statusCounts['packed'],
                    $statusCounts['staging_or_draft'],
                ],
                'colors' => ['#10B981', '#06B6D4', '#F59E0B', '#8B5CF6'],
            ],
            'dock_throughput' => $dockThroughput,
        ];

        return [
            'date_info' => [
                'param' => $dateParam,
                'formatted' => $dateFormatted,
                'day_name' => $dayName,
                'raw' => $dateString,
                'is_today' => $targetDate->isToday(),
            ],
            'matrix_rows' => $matrixRows,
            'active_fleet_radar' => $activeFleetRadar,
            'do_items_manifest' => $doItemsManifest,
            'manifest_summary' => $manifestSummary,
            'tomorrow_summary' => $tomorrowSummary,
            'analytics_charts' => $analyticsCharts,
            'kpis' => [
                'total_active_trucks' => count(array_filter($matrixRows, fn($r) => $r['has_schedules'])),
                'total_rits_done' => $totalRitsDone,
                'total_rits_running' => $totalRitsRunning,
                'total_rits_waiting' => $totalRitsWaiting,
                'total_tonnage_today' => round($totalDailyTonnageAll, 1),
                'total_dispatched_trucks' => $totalDispatchedTrucks,
                'avg_loading_duration_min' => 32,
                'on_time_departure_rate' => $onTimeDepartureRate,
                'fleet_utilization_pct' => count($vehicles) > 0 ? round(($totalDispatchedTrucks / count($vehicles)) * 100) : 85,
            ],
            'company_info' => [
                'name' => \App\Models\Company::first()?->name ?? 'JICOS',
                'legal_name' => \App\Models\Company::first()?->legal_name ?? 'PT. JIDOKA RESULT INDONESIA',
                'logo' => \App\Models\Company::first()?->logo,
                'plant_name' => (\App\Models\Company::first()?->name ?? 'JICOS') . ' Central Logistics Plant',
                'city' => \App\Models\Company::first()?->city ?? 'Bekasi',
            ],
        ];
    }

    /**
     * Format a single DO into a Rit slot card
     */
    protected function formatRitSlot(?DeliveryOrder $do, ?int $fallbackRit = null): ?array
    {
        if (!$do) {
            return null;
        }

        $statusCode = strtolower($do->status);
        $statusLabel = match ($statusCode) {
            'delivered' => 'DELIVERED',
            'shipped' => 'ON THE ROAD',
            'packed' => ($do->loading_dock ? "LOADING {$do->loading_dock}" : 'LOADING DOCK'),
            'picking' => 'STAGING / PICKING',
            'draft' => 'SCHEDULED',
            default => strtoupper($statusCode),
        };

        $statusColor = match ($statusCode) {
            'delivered' => 'emerald',
            'shipped' => 'cyan',
            'packed' => 'amber',
            'picking' => 'blue',
            default => 'slate',
        };

        // Estimate departure or loading time
        $timeStr = $do->estimated_departure_time 
            ? Carbon::parse($do->estimated_departure_time)->format('H:i')
            : match ($do->rit_number ?? $fallbackRit ?? 1) {
                1 => '07:30',
                2 => '12:30',
                3 => '16:30',
                default => '08:00',
            };

        // Calculate weight in Ton
        $weightKg = (float) ($do->total_weight_kg ?: $do->items->sum(function ($item) {
            return (float) ($item->qty * 50); // fallback 50kg per qty
        }));
        $weightTon = max(0.5, round($weightKg / 1000, 2));

        return [
            'id' => $do->id,
            'do_number' => $do->do_number,
            'rit_number' => $do->rit_number ?? $fallbackRit ?? 1,
            'customer_name' => $do->customer?->name ?? $do->shipping_name ?? 'Customer Tujuan',
            'destination_city' => $do->customer?->city ?? 'Jawa Barat',
            'time' => $timeStr,
            'loading_dock' => $do->loading_dock ?? 'Dock #1',
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'items_count' => $do->items->count(),
            'weight_ton' => $weightTon,
            'notes' => $do->notes,
        ];
    }
}
