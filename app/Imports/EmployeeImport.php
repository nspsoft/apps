<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Str;

class EmployeeImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    private $overwrite;

    public function __construct($overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function model(array $row)
    {
        // Skip if mandatory fields are missing
        if (!isset($row['nik']) || !isset($row['full_name'])) {
            return null;
        }

        // Find or create Department
        $deptName = $row['department'] ?? 'Default';
        $department = Department::firstOrCreate(
            ['name' => $deptName],
            ['code' => Str::upper(Str::slug($deptName))]
        );

        // Find or create Position
        $posName = $row['position'] ?? 'Staff';
        $position = Position::firstOrCreate(
            ['name' => $posName],
            [
                'department_id' => $department->id,
                'code' => Str::upper(Str::slug($posName))
            ]
        );

        $employeeData = [
            'full_name'         => $row['full_name'],
            'email'             => $row['email'] ?? null,
            'phone'             => $row['phone'] ?? null,
            'address'           => $row['address'] ?? null,
            'department_id'     => $department->id,
            'position_id'       => $position->id,
            'joining_date'      => $this->transformDate($row['joining_date'] ?? now()),
            'employment_status' => strtolower($row['employment_status'] ?? 'probation'),
            'basic_salary'      => $this->parseNumeric($row['basic_salary'] ?? 0),
            'is_active'         => $this->parseBoolean($row['status'] ?? true),
        ];

        if ($this->overwrite) {
            return Employee::updateOrCreate(
                ['nik' => (string)$row['nik']], // Match by NIK
                $employeeData
            );
        }

        // Default behavior: Create new
        return new Employee(array_merge(['nik' => (string)$row['nik']], $employeeData));
    }

    private function transformDate($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\ErrorException $e) {
            return date('Y-m-d', strtotime($value));
        }
    }

    private function parseNumeric($value)
    {
        if (is_numeric($value)) return $value;
        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        return is_numeric($clean) ? $clean : 0;
    }

    private function parseBoolean($value)
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return $value > 0;
        $str = strtolower((string)$value);
        return in_array($str, ['yes', 'true', '1', 'y', 'active']);
    }
}
