<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            ['name' => 'Shift 1 (06:00 - 14:00)', 'start_time' => '06:00:00', 'end_time' => '14:00:00'],
            ['name' => 'Shift 2 (14:00 - 22:00)', 'start_time' => '14:00:00', 'end_time' => '22:00:00'],
            ['name' => 'Shift 3 (22:00 - 06:00)', 'start_time' => '22:00:00', 'end_time' => '06:00:00'],
        ];

        foreach ($shifts as $shift) {
            \App\Models\Shift::updateOrCreate(['name' => $shift['name']], $shift);
        }
    }
}
