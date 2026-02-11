<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\QcInspection;
use App\Models\QcInspectionItem;
use App\Models\NonConformanceReport;
use App\Models\QcMasterPoint;
use App\Models\GoodsReceipt;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class QcDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // 1. Ensure we have products with QC Master Points
        $products = Product::inRandomOrder()->take(5)->get();
        
        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping QC Seeding.');
            return;
        }

        foreach ($products as $product) {
            // Create Master Points if not exist
            if ($product->qcMasterPoints()->count() == 0) {
                $points = [
                    ['parameter_name' => 'Visual Appearance', 'standard_min' => 0, 'standard_max' => 0, 'unit' => 'N/A', 'method' => 'Visual'],
                    ['parameter_name' => 'Diameter', 'standard_min' => 10.0, 'standard_max' => 10.5, 'unit' => 'mm', 'method' => 'Caliper'],
                    ['parameter_name' => 'Thickness', 'standard_min' => 2.0, 'standard_max' => 2.2, 'unit' => 'mm', 'method' => 'Micrometer'],
                    ['parameter_name' => 'Hardness', 'standard_min' => 50, 'standard_max' => 60, 'unit' => 'HRC', 'method' => 'Tester'],
                ];

                foreach ($points as $point) {
                    QcMasterPoint::create([
                        'product_id' => $product->id,
                        ...$point
                    ]);
                }
            }
        }

        $inspector = User::first(); // Just pick first user
        if (!$inspector) {
             $this->command->info('No users found. Skipping QC Seeding.');
             return;
        }

        // 2. Generate Inspections (Past 30 Days)
        for ($i = 0; $i < 50; $i++) {
            $date = Carbon::now()->subDays(rand(0, 30));
            $product = $products->random();
            
            // Randomly decide if Incoming or In-Process (simulated by reference)
            // Ideally we link to actual GRN or WO, but for statistics, we might mock the morph relation or leave it nullable if allowed
            // The migration said nullableMorphs.
            
            $status = $faker->randomElement(['pass', 'pass', 'pass', 'fail', 'conditional_pass']);
            
            $inspection = QcInspection::create([
                'inspector_id' => $inspector->id,
                'inspection_date' => $date,
                'status' => $status,
                'sample_size' => rand(1, 10),
                'notes' => $faker->sentence,
                'created_at' => $date,
                'updated_at' => $date,
                // 'reference_type' => 'App\Models\GoodsReceipt', // Mock reference
                // 'reference_id' => rand(1, 100),
            ]);

            // Create Inspection Items
            foreach ($product->qcMasterPoints as $mp) {
                $isPass = true;
                $actual = ($mp->standard_min + $mp->standard_max) / 2;
                
                if ($status === 'fail' && rand(0, 1)) {
                    $isPass = false;
                    $actual = $mp->standard_max + 0.5; // Out of spec
                }

                QcInspectionItem::create([
                    'qc_inspection_id' => $inspection->id,
                    'qc_master_point_id' => $mp->id,
                    'actual_value' => $actual,
                    'is_pass' => $isPass,
                    'remark' => $isPass ? 'OK' : 'Out of Spec',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            // Create NCR if fail
            if ($status === 'fail') {
                NonConformanceReport::create([
                    'qc_inspection_id' => $inspection->id,
                    'defect_type' => $faker->randomElement(['Surface Crack', 'Dimension Error', 'Material Hardness', 'Rust/Corrosion', 'Peeling']),
                    'defect_description' => $faker->sentence,
                    'root_cause' => $faker->sentence,
                    'action_plan' => $faker->paragraph,
                    'disposition' => $faker->randomElement(['Scrap', 'Rework', 'Return to Vendor']),
                    'status' => $faker->randomElement(['open', 'closed']),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
