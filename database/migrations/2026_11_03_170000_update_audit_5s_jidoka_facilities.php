<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename Buildings to PT Jidoka Facility Standards
        $b1 = Audit5sBuilding::where('code', 'BLD-01')->first();
        if ($b1) {
            $b1->update([
                'name' => 'Jidoka Main Production & Assembly Facility',
                'description' => 'Fasilitas utama perakitan controller digital, otomasi industri, dan pengujian sistem.',
            ]);
        }

        $b2 = Audit5sBuilding::where('code', 'BLD-02')->first();
        if ($b2) {
            $b2->update([
                'name' => 'Hardware Workshop & Integration Bay',
                'description' => 'Fasilitas permesinan mekanikal, fabrikasi rangka, dan integrasi robotik.',
            ]);
        }

        $b3 = Audit5sBuilding::where('code', 'BLD-03')->first();
        if ($b3) {
            $b3->update([
                'name' => 'Central Warehouse & Distribution Hub',
                'description' => 'Gudang penyimpanan komponen mikroelektronik, modul jadi, dan logistik distribusi.',
            ]);
        }

        // 2. Rename Zones to PT Jidoka Standards
        $zonesMapping = [
            'Z-COIL-01' => [
                'name' => 'Bay 1: Electronic Components & SMT Assembly',
                'code' => 'Z-SMT-01',
                'area_type' => 'production',
            ],
            'Z-SLIT-02' => [
                'name' => 'Bay 2: Mechanical CNC & Fabrication Bay',
                'code' => 'Z-MECH-02',
                'area_type' => 'workshop',
            ],
            'Z-MILL-03' => [
                'name' => 'Bay 3: Robotics & Automation Assembly Bay',
                'code' => 'Z-ROBOT-03',
                'area_type' => 'production',
            ],
            'Z-QC-04' => [
                'name' => 'Bay 4: QA Inspection & Sensor Calibration Lab',
                'code' => 'Z-QC-04',
                'area_type' => 'quality_lab',
            ],
            'Z-MAINT-05' => [
                'name' => 'Bay 5: Tooling & Maintenance Workshop',
                'code' => 'Z-MAINT-05',
                'area_type' => 'workshop',
            ],
            'Z-FG-06' => [
                'name' => 'Bay 6: Finished Goods & Dispatch Bay',
                'code' => 'Z-FG-06',
                'area_type' => 'warehouse',
            ],
        ];

        foreach ($zonesMapping as $oldCode => $newData) {
            $zone = Audit5sZone::where('code', $oldCode)->first();
            if ($zone) {
                $zone->update($newData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
