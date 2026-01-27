<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        $depts = [
            ['code' => 'DEPT-HR', 'name' => 'Human Resources'],
            ['code' => 'DEPT-IT', 'name' => 'Information Technology'],
            ['code' => 'DEPT-PRD', 'name' => 'Production'],
            ['code' => 'DEPT-FIN', 'name' => 'Finance & Accounting'],
            ['code' => 'DEPT-MKT', 'name' => 'Marketing'],
        ];

        foreach ($depts as $dept) {
            $d = Department::create($dept);
            
            if ($dept['code'] === 'DEPT-PRD') {
                Position::create([
                    'department_id' => $d->id,
                    'name' => 'Production Manager',
                    'salary_range_min' => 10000000,
                    'salary_range_max' => 15000000,
                ]);
                Position::create([
                    'department_id' => $d->id,
                    'name' => 'Floor Operator',
                    'salary_range_min' => 4500000,
                    'salary_range_max' => 6000000,
                ]);
            } else {
                Position::create([
                    'department_id' => $d->id,
                    'name' => str_replace('Department', '', $dept['name']) . ' Staff',
                    'salary_range_min' => 5000000,
                    'salary_range_max' => 8000000,
                ]);
            }
        }
    }
}
