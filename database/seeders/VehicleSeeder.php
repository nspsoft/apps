<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\Company;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::first()->id ?? 1;

        $vehicles = [
            [
                'company_id' => $companyId,
                'license_plate' => 'B 9123 SFX',
                'vehicle_type' => 'Wingbox',
                'brand' => 'Mitsubishi Fuso',
                'capacity_weight' => 20000,
                'capacity_volume' => 45,
                'driver_name' => 'Ahmad Sudarto',
                'status' => 'available',
                'stnk_number' => 'STNK-001234',
                'stnk_expiry' => '2026-05-20',
                'kir_number' => 'KIR-99123',
                'kir_expiry' => '2026-02-15',
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'license_plate' => 'L 8847 ABC',
                'vehicle_type' => 'Tronton',
                'brand' => 'Hino Ranger',
                'capacity_weight' => 15000,
                'capacity_volume' => 35,
                'driver_name' => 'Budi Santoso',
                'status' => 'available',
                'stnk_number' => 'STNK-005678',
                'stnk_expiry' => '2026-11-10',
                'kir_number' => 'KIR-88456',
                'kir_expiry' => '2026-06-01',
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'license_plate' => 'B 9442 KKK',
                'vehicle_type' => 'Engkel (CDE)',
                'brand' => 'Isuzu Elf',
                'capacity_weight' => 3000,
                'capacity_volume' => 8,
                'driver_name' => 'Joko Widodo',
                'status' => 'available',
                'stnk_number' => 'STNK-009900',
                'stnk_expiry' => '2027-01-05',
                'kir_number' => 'KIR-77665',
                'kir_expiry' => '2026-04-12',
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'license_plate' => 'W 1234 XY',
                'vehicle_type' => 'Double (CDD)',
                'brand' => 'Mitsubishi Canter',
                'capacity_weight' => 6000,
                'capacity_volume' => 12,
                'driver_name' => 'Slamet Rahardjo',
                'status' => 'available',
                'stnk_number' => 'STNK-001122',
                'stnk_expiry' => '2026-08-15',
                'kir_number' => 'KIR-55443',
                'kir_expiry' => '2026-03-22',
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'license_plate' => 'B 3604 XJ',
                'vehicle_type' => 'Trailer 40ft',
                'brand' => 'Hino 700',
                'capacity_weight' => 32000,
                'capacity_volume' => 60,
                'driver_name' => 'Andi Wijaya',
                'status' => 'available',
                'stnk_number' => 'STNK-002233',
                'stnk_expiry' => '2026-12-25',
                'kir_number' => 'KIR-22334',
                'kir_expiry' => '2026-07-30',
                'is_active' => true,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::updateOrCreate(
                ['license_plate' => $vehicle['license_plate']],
                $vehicle
            );
        }
    }
}
