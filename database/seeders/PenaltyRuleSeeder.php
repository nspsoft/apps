<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PenaltyRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Rule Keterlambatan (Late)
        \App\Models\PenaltyRule::updateOrCreate(
            ['type' => 'late'],
            [
                'name' => 'Penalti Keterlambatan',
                'rounding_minutes' => 30,
                'deduction_basis' => 'hourly_rate',
                'fixed_amount' => 0,
                'grace_period_minutes' => 0,
                'description' => 'Dibulatkan per 30 menit (e.g. 1-30m = potong 30m, 31-60m = potong 60m). Dipotong berdasarkan tarif per jam (hourly rate).',
                'is_active' => true,
            ]
        );

        // 2. Rule Pulang Cepat (Early Leave)
        \App\Models\PenaltyRule::updateOrCreate(
            ['type' => 'early_leave'],
            [
                'name' => 'Penalti Pulang Cepat',
                'rounding_minutes' => 30,
                'deduction_basis' => 'hourly_rate',
                'fixed_amount' => 0,
                'grace_period_minutes' => 0,
                'description' => 'Dibulatkan per 30 menit (e.g. 1-30m = potong 30m, 31-60m = potong 60m). Dipotong berdasarkan tarif per jam (hourly rate).',
                'is_active' => true,
            ]
        );
    }
}
