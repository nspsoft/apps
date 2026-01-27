<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\Attendance;
use Carbon\Carbon;

class HrTrialSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'nik' => 'EMP2026001',
                'full_name' => 'Budi Santoso',
                'email' => 'budi.santoso@nsp.my.id',
                'phone' => '081234567890',
                'dept_code' => 'DEPT-PRD',
                'joining_date' => '2024-01-15',
                'status' => 'permanent',
                'salary' => 6500000,
            ],
            [
                'nik' => 'EMP2026002',
                'full_name' => 'Siti Aminah',
                'email' => 'siti.aminah@nsp.my.id',
                'phone' => '081234567891',
                'dept_code' => 'DEPT-HR',
                'joining_date' => '2024-03-01',
                'status' => 'permanent',
                'salary' => 7500000,
            ],
            [
                'nik' => 'EMP2026003',
                'full_name' => 'Andi Wijaya',
                'email' => 'andi.wijaya@nsp.my.id',
                'phone' => '081234567892',
                'dept_code' => 'DEPT-IT',
                'joining_date' => '2025-06-10',
                'status' => 'contract',
                'salary' => 8500000,
            ],
            [
                'nik' => 'EMP2026004',
                'full_name' => 'Rina Kartika',
                'email' => 'rina.kartika@nsp.my.id',
                'phone' => '081234567893',
                'dept_code' => 'DEPT-FIN',
                'joining_date' => '2025-11-20',
                'status' => 'probation',
                'salary' => 5500000,
            ],
            [
                'nik' => 'EMP2026005',
                'full_name' => 'Joko Widodo',
                'email' => 'joko.w@nsp.my.id',
                'phone' => '081234567894',
                'dept_code' => 'DEPT-PRD',
                'joining_date' => '2025-12-05',
                'status' => 'internship',
                'salary' => 3500000,
            ],
        ];

        foreach ($employees as $empData) {
            $dept = Department::where('code', $empData['dept_code'])->first();
            $pos = Position::where('department_id', $dept->id)->first();

            $employee = Employee::create([
                'nik' => $empData['nik'],
                'full_name' => $empData['full_name'],
                'email' => $empData['email'],
                'phone' => $empData['phone'],
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'joining_date' => $empData['joining_date'],
                'employment_status' => $empData['status'],
                'basic_salary' => $empData['salary'],
                'is_active' => true,
            ]);

            // Generate attendance for current month (Jan 2026)
            $startOfMonth = Carbon::now()->startOfMonth();
            $today = Carbon::now();

            for ($date = $startOfMonth; $date->lte($today); $date->addDay()) {
                // Skip weekends
                if ($date->isWeekend()) continue;

                // Randomly skip some days for "absent" simulation (10% chance)
                if (rand(1, 10) === 1) continue;

                $clockIn = (clone $date)->setHour(rand(7, 9))->setMinute(rand(0, 59));
                $clockOut = (clone $date)->setHour(rand(16, 18))->setMinute(rand(0, 59));

                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'status' => $clockIn->format('H:i') > '08:30' ? 'late' : 'present',
                ]);
            }
        }
    }
}
