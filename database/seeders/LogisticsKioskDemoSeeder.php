<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LogisticsKioskDemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $warehouseId = Warehouse::first()?->id ?? 1;
        $salesOrderId = SalesOrder::first()?->id;

        $vehicles = Vehicle::where('is_active', true)
            ->whereIn('usage_type', ['logistics', 'both'])
            ->take(6)
            ->get();

        if ($vehicles->isEmpty()) {
            return;
        }

        $customers = Customer::take(10)->get();
        $products = Product::take(10)->get();

        $customerA = $customers[0]->name ?? 'PT Astra Honda Motor';
        $customerB = $customers[1]->name ?? 'PT Toyota Motor Manufacturing';
        $customerC = $customers[2]->name ?? 'PT Yamaha Indonesia Motor';
        $customerD = $customers[3]->name ?? 'PT United Tractors Tbk';
        $customerE = $customers[4]->name ?? 'PT Komatsu Indonesia';

        $drivers = ['Bambang Sutrisno', 'Joko Widodo S.', 'Ahmad Fauzi', 'Dedi Supardi', 'Rudi Heryanto', 'Yusuf Maulana'];

        $sampleRits = [
            // Truck 1: Complete 3 Rits
            [
                'v_idx' => 0,
                'rit' => 1,
                'customer' => $customerA,
                'dest' => 'Kawasan KIIC Blok C-2, Karawang',
                'time' => '07:30',
                'dock' => 'Dock #1',
                'status' => 'delivered',
                'weight' => 8450,
            ],
            [
                'v_idx' => 0,
                'rit' => 2,
                'customer' => $customerB,
                'dest' => 'Kawasan MM2100 Blok DD, Cikarang',
                'time' => '12:00',
                'dock' => 'Dock #2',
                'status' => 'shipped',
                'weight' => 7200,
            ],
            [
                'v_idx' => 0,
                'rit' => 3,
                'customer' => $customerC,
                'dest' => 'Kawasan EJIP Plot 5A, Cikarang Selatan',
                'time' => '16:30',
                'dock' => 'Dock #1',
                'status' => 'packed',
                'weight' => 6100,
            ],

            // Truck 2: 2 Rits
            [
                'v_idx' => 1,
                'rit' => 1,
                'customer' => $customerD,
                'dest' => 'Cakung, Jakarta Timur',
                'time' => '08:00',
                'dock' => 'Dock #3',
                'status' => 'delivered',
                'weight' => 9800,
            ],
            [
                'v_idx' => 1,
                'rit' => 2,
                'customer' => $customerE,
                'dest' => 'Cilincing, Jakarta Utara',
                'time' => '13:15',
                'dock' => 'Dock #3',
                'status' => 'shipped',
                'weight' => 8900,
            ],

            // Truck 3: 2 Rits
            [
                'v_idx' => 2,
                'rit' => 1,
                'customer' => $customerB,
                'dest' => 'Kawasan KIIC Karawang',
                'time' => '07:45',
                'dock' => 'Dock #4',
                'status' => 'delivered',
                'weight' => 6500,
            ],
            [
                'v_idx' => 2,
                'rit' => 2,
                'customer' => $customerA,
                'dest' => 'Kawasan Suryacipta, Karawang',
                'time' => '12:30',
                'dock' => 'Dock #4',
                'status' => 'packed',
                'weight' => 7800,
            ],
        ];

        foreach ($sampleRits as $idx => $s) {
            if (!isset($vehicles[$s['v_idx']])) continue;
            $v = $vehicles[$s['v_idx']];
            $driver = $drivers[$s['v_idx'] % count($drivers)];
            $doNum = 'DO-' . date('Ymd') . '-R' . $s['rit'] . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

            $custObj = $customers->firstWhere('name', $s['customer']) ?? $customers->first();

            $do = DeliveryOrder::updateOrCreate(
                ['do_number' => $doNum],
                [
                    'sales_order_id' => $salesOrderId,
                    'warehouse_id' => $warehouseId,
                    'customer_id' => $custObj?->id,
                    'shipping_name' => $s['customer'],
                    'shipping_address' => $s['dest'],
                    'delivery_date' => $today,
                    'vehicle_id' => $v->id,
                    'vehicle_number' => $v->license_plate,
                    'driver_name' => $driver,
                    'rit_number' => $s['rit'],
                    'loading_dock' => $s['dock'],
                    'estimated_departure_time' => $s['time'],
                    'total_weight_kg' => $s['weight'],
                    'status' => $s['status'],
                    'notes' => 'Multi-rit scheduled delivery batch',
                ]
            );
        }

        // Add 3 sample orders for tomorrow
        $tomorrowOrders = [
            ['customer' => $customerA, 'rit' => 1, 'v_idx' => 0, 'dock' => 'Dock #1', 'weight' => 9500],
            ['customer' => $customerC, 'rit' => 1, 'v_idx' => 1, 'dock' => 'Dock #2', 'weight' => 8100],
            ['customer' => $customerD, 'rit' => 1, 'v_idx' => 2, 'dock' => 'Dock #3', 'weight' => 7400],
            ['customer' => $customerB, 'rit' => 2, 'v_idx' => 0, 'dock' => 'Dock #1', 'weight' => 6800],
        ];

        foreach ($tomorrowOrders as $tIdx => $t) {
            $v = $vehicles[$t['v_idx']] ?? $vehicles->first();
            $driver = $drivers[$t['v_idx'] % count($drivers)];
            $doNum = 'DO-TMRW-' . str_pad($tIdx + 1, 3, '0', STR_PAD_LEFT);
            $custObj = $customers->firstWhere('name', $t['customer']) ?? $customers->first();

            DeliveryOrder::updateOrCreate(
                ['do_number' => $doNum],
                [
                    'sales_order_id' => $salesOrderId,
                    'warehouse_id' => $warehouseId,
                    'customer_id' => $custObj?->id,
                    'shipping_name' => $t['customer'],
                    'shipping_address' => 'Kawasan Industri Mitra Karawang',
                    'delivery_date' => $tomorrow,
                    'vehicle_id' => $v?->id,
                    'vehicle_number' => $v?->license_plate,
                    'driver_name' => $driver,
                    'rit_number' => $t['rit'],
                    'loading_dock' => $t['dock'],
                    'estimated_departure_time' => '07:30',
                    'total_weight_kg' => $t['weight'],
                    'status' => 'draft',
                    'notes' => 'Advance staging requirement',
                ]
            );
        }
    }
}
