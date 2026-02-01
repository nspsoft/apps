<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use Carbon\Carbon;

class VehicleRecoverySeeder extends Seeder
{
    public function run()
    {
        $vehicles = [
            [
                'license_plate' => 'T 8743 DS',
                'vehicle_type' => 'Van',
                'brand' => 'Suzuki',
                'model' => 'Carry',
                'year' => 2015,
                'chassis_number' => 'CN00001',
                'engine_number' => 'EN00001',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'M FIKRI HIDAYATULLOH',
                'capacity_weight' => 2000,
                'stnk_number' => 'STNK0001',
                'stnk_expiry' => '2026-02-15',
                'kir_number' => 'KIR0001',
                'kir_expiry' => '2026-10-15',
                'purchase_date' => '2020-01-01',
            ],
            [
                'license_plate' => 'T 8175 DY',
                'vehicle_type' => 'Van',
                'brand' => 'Daihatsu',
                'model' => 'Gran Max',
                'year' => 2016,
                'chassis_number' => 'CN00002',
                'engine_number' => 'EN00002',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'FAISAL SETIAWAN',
                'capacity_weight' => 2000,
                'stnk_number' => 'STNK0002',
                'stnk_expiry' => '2026-02-16',
                'kir_number' => 'KIR0002',
                'kir_expiry' => '2026-10-16',
                'purchase_date' => '2021-01-02',
            ],
            [
                'license_plate' => 'T 9652 DE',
                'vehicle_type' => 'Box',
                'brand' => 'Mitsubishi',
                'model' => 'Colt Diesel',
                'year' => 2017,
                'chassis_number' => 'CN00003',
                'engine_number' => 'EN00003',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'MOH CHANIFUDIN',
                'capacity_weight' => 5000,
                'stnk_number' => 'STNK0003',
                'stnk_expiry' => '2026-02-17',
                'kir_number' => 'KIR0003',
                'kir_expiry' => '2026-10-17',
                'purchase_date' => '2022-01-04',
            ],
            [
                'license_plate' => 'B 9621 FCK',
                'vehicle_type' => 'Box',
                'brand' => 'Isuzu',
                'model' => 'FRR 90Q Wingbox',
                'year' => 2024,
                'chassis_number' => 'CN00004',
                'engine_number' => 'EN00004',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'FARHANDIKA F. H.',
                'capacity_weight' => 10000,
                'stnk_number' => 'STNK0004',
                'stnk_expiry' => '2026-02-18',
                'kir_number' => 'KIR0004',
                'kir_expiry' => '2026-10-18',
                'purchase_date' => '2023-01-06',
            ],
            [
                'license_plate' => 'B 9703 FXW',
                'vehicle_type' => 'Box',
                'brand' => 'Isuzu',
                'model' => 'FVR 34U Wingbox',
                'year' => 2025,
                'chassis_number' => 'CN00005',
                'engine_number' => 'EN00005',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'MUHAMAD ARDIANSYAH',
                'capacity_weight' => 15000,
                'stnk_number' => 'STNK0005',
                'stnk_expiry' => '2026-02-19',
                'kir_number' => 'KIR0005',
                'kir_expiry' => '2026-10-19',
                'purchase_date' => '2024-01-08',
            ],
            [
                'license_plate' => 'B 9803 FFX',
                'vehicle_type' => 'Box',
                'brand' => 'Isuzu',
                'model' => 'FVR 34U Wingbox',
                'year' => 2025,
                'chassis_number' => 'CN00006',
                'engine_number' => 'EN00006',
                'fuel_type' => 'Solar',
                'ownership' => 'Maman',
                'driver_name' => 'RUSTAM NAWAWI',
                'capacity_weight' => 15000,
                'stnk_number' => 'STNK0006',
                'stnk_expiry' => '2026-02-20',
                'kir_number' => 'KIR0006',
                'kir_expiry' => '2026-10-20',
                'purchase_date' => '2025-01-09',
            ],
        ];

        foreach ($vehicles as $data) {
            Vehicle::updateOrCreate(
                ['license_plate' => $data['license_plate']],
                array_merge($data, [
                    'status' => 'available',
                    'capacity_volume' => 0,
                    'is_active' => true,
                    'notes' => 'Imported via Recovery Seeder',
                ])
            );
        }
    }
}
