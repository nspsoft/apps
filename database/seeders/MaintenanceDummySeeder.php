<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\Sparepart;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MaintenanceDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Machines Exist (if not, pick first few)
        $machines = Machine::all();
        if ($machines->isEmpty()) {
            return; // Cannot seed maintenance without machines
        }

        // 2. Seed Spareparts
        $parts = [
            ['name' => 'Hydraulic Filter', 'part_number' => 'HYD-001', 'stock' => 15, 'min_stock' => 5, 'location' => 'Shelf A-1'],
            ['name' => 'Cutting Blade 500mm', 'part_number' => 'BLD-500', 'stock' => 8, 'min_stock' => 3, 'location' => 'Shelf B-2'],
            ['name' => 'Servo Motor Driver', 'part_number' => 'SRV-009', 'stock' => 2, 'min_stock' => 1, 'location' => 'Secure Cabinet'],
            ['name' => 'Conveyor Belt 2m', 'part_number' => 'CNV-200', 'stock' => 4, 'min_stock' => 2, 'location' => 'Shelf C-1'],
        ];

        foreach ($parts as $part) {
            Sparepart::firstOrCreate(
                ['part_number' => $part['part_number']],
                $part
            );
        }

        $allParts = Sparepart::all();

        // 3. Create Schedules for each machine
        foreach ($machines as $machine) {
            MaintenanceSchedule::create([
                'machine_id' => $machine->id,
                'task_name' => 'Monthly Lubrication Check',
                'description' => 'Check oil levels and lubricate moving parts.',
                'frequency_days' => 30,
                'last_performed_at' => Carbon::now()->subDays(rand(10, 40)),
                'next_due_date' => Carbon::now()->addDays(rand(-5, 20)), // Some overdue
                'status' => 'active',
            ]);

            MaintenanceSchedule::create([
                'machine_id' => $machine->id,
                'task_name' => 'Quarterly Calibration',
                'description' => 'Calibrate sensors and alignment.',
                'frequency_days' => 90,
                'last_performed_at' => Carbon::now()->subDays(rand(20, 100)),
                'next_due_date' => Carbon::now()->addDays(rand(10, 60)),
                'status' => 'active',
            ]);
        }

        // 4. Create Breakdown Logs
        $breakdownDescriptions = [
            'Overheating motor', 
            'Belt snapped', 
            'Sensor misalignment', 
            'Hydraulic leak'
        ];

        foreach ($machines->take(3) as $machine) {
            // Closed Log
            $log = MaintenanceLog::create([
                'machine_id' => $machine->id,
                'type' => 'breakdown',
                'description' => $breakdownDescriptions[rand(0, 3)],
                'started_at' => Carbon::now()->subDays(rand(5, 10)),
                'finished_at' => Carbon::now()->subDays(rand(1, 4)),
                'technician_name' => 'Budi Santoso',
                'status' => 'resolved',
            ]);
            
            // Attach sparepart usage
            $log->spareparts()->attach($allParts->random()->id, ['qty_used' => rand(1, 2)]);

            // Open/In Progress Log (Active Issue)
            MaintenanceLog::create([
                'machine_id' => $machine->id,
                'type' => 'breakdown',
                'description' => 'Abnormal vibration detected',
                'started_at' => Carbon::now()->subHours(rand(2, 48)),
                'finished_at' => null,
                'technician_name' => 'Agus Setiawan',
                'status' => 'in_progress',
            ]);
        }
    }
}
