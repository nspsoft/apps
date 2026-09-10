<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Office Regular (Default)
        $office = \App\Models\WorkSchedule::updateOrCreate(
            ['code' => 'OFFICE_REGULAR'],
            [
                'name' => 'Office Regular',
                'description' => 'Senin-Kamis 08:00-16:00, Jumat 08:00-16:30, Sabtu 08:00-12:00, Minggu Libur',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $officeDays = [
            0 => ['is_workday' => false, 'start' => null, 'end' => null, 'break' => 0],          // Minggu
            1 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '16:00:00', 'break' => 60], // Senin
            2 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '16:00:00', 'break' => 60], // Selasa
            3 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '16:00:00', 'break' => 60], // Rabu
            4 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '16:00:00', 'break' => 60], // Kamis
            5 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '16:30:00', 'break' => 90], // Jumat (istirahat 1.5 jam)
            6 => ['is_workday' => true, 'start' => '08:00:00', 'end' => '12:00:00', 'break' => 0],  // Sabtu
        ];

        foreach ($officeDays as $day => $config) {
            \App\Models\WorkScheduleDetail::updateOrCreate(
                ['work_schedule_id' => $office->id, 'day_of_week' => $day],
                [
                    'is_workday' => $config['is_workday'],
                    'start_time' => $config['start'],
                    'end_time' => $config['end'],
                    'break_minutes' => $config['break'],
                ]
            );
        }

        // 2. Shift 1 (07:00 - 15:00)
        $shift1 = \App\Models\WorkSchedule::updateOrCreate(
            ['code' => 'SHIFT_1'],
            [
                'name' => 'Shift 1 (Pagi)',
                'description' => 'Senin-Sabtu 07:00-15:00, Minggu Libur',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        for ($day = 0; $day <= 6; $day++) {
            $isWork = $day !== 0; // Libur hari Minggu
            \App\Models\WorkScheduleDetail::updateOrCreate(
                ['work_schedule_id' => $shift1->id, 'day_of_week' => $day],
                [
                    'is_workday' => $isWork,
                    'start_time' => $isWork ? '07:00:00' : null,
                    'end_time' => $isWork ? '15:00:00' : null,
                    'break_minutes' => $isWork ? 60 : 0,
                ]
            );
        }

        // 3. Shift 2 (15:00 - 23:00)
        $shift2 = \App\Models\WorkSchedule::updateOrCreate(
            ['code' => 'SHIFT_2'],
            [
                'name' => 'Shift 2 (Sore/Malam)',
                'description' => 'Senin-Sabtu 15:00-23:00, Minggu Libur',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        for ($day = 0; $day <= 6; $day++) {
            $isWork = $day !== 0; // Libur hari Minggu
            \App\Models\WorkScheduleDetail::updateOrCreate(
                ['work_schedule_id' => $shift2->id, 'day_of_week' => $day],
                [
                    'is_workday' => $isWork,
                    'start_time' => $isWork ? '15:00:00' : null,
                    'end_time' => $isWork ? '23:00:00' : null,
                    'break_minutes' => $isWork ? 60 : 0,
                ]
            );
        }

        // Set all existing employees without work_schedule_id to Office Regular
        \App\Models\Employee::whereNull('work_schedule_id')->update([
            'work_schedule_id' => $office->id
        ]);
    }
}
