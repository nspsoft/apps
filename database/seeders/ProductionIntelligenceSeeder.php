<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionIntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $user = User::first();
        $products = Product::where('is_manufactured', true)->get();

        if ($products->isEmpty()) {
            $this->command->error('No manufactured products found. Please run DemoDataSeeder.');
            return;
        }

        // 1. Cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductionEntry::truncate();
        Machine::truncate();
        Shift::truncate();
        WorkOrder::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Create Shifts
        $shifts = [
            ['name' => 'Shift 1', 'start_time' => '07:00', 'end_time' => '15:00'],
            ['name' => 'Shift 2', 'start_time' => '15:00', 'end_time' => '23:00'],
            ['name' => 'Shift 3', 'start_time' => '23:00', 'end_time' => '07:00'],
        ];

        foreach ($shifts as $s) {
            Shift::create(array_merge($s, ['is_active' => true]));
        }
        $shiftModels = Shift::all();

        // 3. Create Machines
        $machines = [
            ['name' => 'MILL-X1', 'code' => 'M001'],
            ['name' => 'MILL-X2', 'code' => 'M002'],
            ['name' => 'CUT-V7', 'code' => 'M003'],
            ['name' => 'SLIT-S5', 'code' => 'M004'],
            ['name' => 'PACK-P1', 'code' => 'M005'],
        ];

        foreach ($machines as $m) {
            Machine::create(array_merge($m, ['is_active' => true]));
        }
        $machineModels = Machine::all();

        // 4. Create Work Orders
        $activeBoms = \App\Models\Bom::active()->get();
        if ($activeBoms->isEmpty()) {
            $activeBoms = \App\Models\Bom::all(); // Fallback if no active ones
        }

        if ($activeBoms->isEmpty()) {
            $this->command->error('No BOMs found. Please create BOMs first.');
            return;
        }

        $warehouse = \App\Models\Warehouse::where('is_default', true)->first() ?? \App\Models\Warehouse::first();

        for ($i = 0; $i < 20; $i++) {
            $bom = $activeBoms->random();
            $date = Carbon::now()->subDays(rand(1, 15));
            WorkOrder::create([
                'company_id' => $company->id ?? 1,
                'wo_number' => 'WO-' . date('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'product_id' => $bom->product_id,
                'bom_id' => $bom->id,
                'warehouse_id' => $warehouse->id,
                'qty_planned' => rand(2000, 10000),
                'qty_produced' => 0,
                'planned_start' => $date,
                'planned_end' => $date->copy()->addDays(rand(2, 5)),
                'status' => rand(0, 5) > 1 ? 'in_progress' : 'confirmed',
                'priority' => 'normal',
                'created_by' => $user->id,
            ]);
        }
        $woModels = WorkOrder::all();

        // 5. Generate Production Entries (Last 10 Days including Today)
        for ($day = 0; $day <= 10; $day++) {
            $date = Carbon::today()->subDays($day);
            
            foreach ($shiftModels as $shift) {
                foreach ($machineModels as $machine) {
                    // Random productivity variance
                    $isLateShiftToday = $day === 0 && $shift->id == 3 && Carbon::now()->hour < 23;
                    if ($isLateShiftToday) continue;

                    $qty = rand(450, 750);
                    $rejects = rand(0, 15);
                    $downtime = rand(0, 10) > 8 ? rand(20, 60) : 0;

                    ProductionEntry::create([
                        'work_order_id' => $woModels->random()->id,
                        'production_date' => $date,
                        'shift' => $shift->id,
                        'qty_produced' => $qty,
                        'qty_rejected' => $rejects,
                        'downtime_minutes' => $downtime,
                        'machine_line' => $machine->name,
                        'produced_by' => $user->id,
                    ]);
                }
            }
        }

        $this->command->info('Production Intelligence data seeded successfully.');
    }
}
