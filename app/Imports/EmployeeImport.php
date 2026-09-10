<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeeImport implements ToCollection, WithCalculatedFormulas
{
    public bool $overwrite;
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'File Excel kosong.';
            return;
        }

        // 1. Detect Header Row dynamically (scans first 10 rows)
        $headerRowIndex = null;
        $headerMap = [];

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            
            // Normalize cells
            $normalizedCells = array_map(function($val) {
                return strtolower(trim((string)$val));
            }, $rowArray);

            $hasNik = false;
            $hasName = false;

            foreach ($normalizedCells as $cell) {
                if (in_array($cell, [
                    'nik', 'nip', 'no_karyawan', 'no karyawan', 'id_karyawan', 
                    'id karyawan', 'employee_id', 'employee id', 'kode', 'code'
                ])) {
                    $hasNik = true;
                }
                if (in_array($cell, [
                    'full_name', 'full name', 'nama', 'nama_lengkap', 
                    'nama lengkap', 'name', 'employee_name'
                ])) {
                    $hasName = true;
                }
            }

            // Found matching header row
            if ($hasNik && $hasName) {
                $headerRowIndex = $index;
                foreach ($normalizedCells as $colIndex => $cell) {
                    $field = $this->mapColumnName($cell);
                    if ($field && !isset($headerMap[$field])) {
                        $headerMap[$field] = $colIndex;
                    }
                }
                break;
            }
        }

        if ($headerRowIndex === null || !isset($headerMap['nik']) || !isset($headerMap['full_name'])) {
            $this->errors[] = 'Format header kolom tidak dikenali. Pastikan file memiliki kolom NIK dan Full Name (atau Nama Lengkap).';
            return;
        }

        // 2. Process data rows starting after the header
        $totalRows = $rows->count();
        for ($i = $headerRowIndex + 1; $i < $totalRows; $i++) {
            $row = $rows[$i]->toArray();

            // Extract mandatory fields
            $nikRaw = $row[$headerMap['nik']] ?? null;
            $nameRaw = $row[$headerMap['full_name']] ?? null;

            if (is_null($nikRaw) || is_null($nameRaw)) {
                continue;
            }

            $nik = trim((string)$nikRaw);
            $fullName = trim((string)$nameRaw);

            // Skip empty rows
            if ($nik === '' || $fullName === '') {
                continue;
            }

            // Department resolution
            $deptNameRaw = isset($headerMap['department']) ? ($row[$headerMap['department']] ?? null) : null;
            $deptName = trim((string)$deptNameRaw);
            if ($deptName === '') {
                $deptName = 'General';
            }
            $department = Department::firstOrCreate(
                ['name' => $deptName],
                [
                    'code' => Str::upper(Str::slug($deptName)),
                    'is_active' => true,
                ]
            );

            // Position resolution
            $posNameRaw = isset($headerMap['position']) ? ($row[$headerMap['position']] ?? null) : null;
            $posName = trim((string)$posNameRaw);
            if ($posName === '') {
                $posName = 'Staff';
            }
            $position = Position::firstOrCreate(
                [
                    'name' => $posName,
                    'department_id' => $department->id,
                ],
                [
                    'is_active' => true,
                ]
            );

            // Other fields
            $email = isset($headerMap['email']) ? trim((string)($row[$headerMap['email']] ?? '')) : null;
            $phone = isset($headerMap['phone']) ? trim((string)($row[$headerMap['phone']] ?? '')) : null;
            $address = isset($headerMap['address']) ? trim((string)($row[$headerMap['address']] ?? '')) : null;
            
            $joiningDateRaw = isset($headerMap['joining_date']) ? ($row[$headerMap['joining_date']] ?? null) : null;
            $joiningDate = $this->transformDate($joiningDateRaw);

            $employmentStatusRaw = isset($headerMap['employment_status']) ? ($row[$headerMap['employment_status']] ?? null) : null;
            $employmentStatus = $this->transformEmploymentStatus($employmentStatusRaw);

            $basicSalaryRaw = isset($headerMap['basic_salary']) ? ($row[$headerMap['basic_salary']] ?? 0) : 0;
            $basicSalary = $this->parseNumeric($basicSalaryRaw);

            $statusRaw = isset($headerMap['is_active']) ? ($row[$headerMap['is_active']] ?? true) : true;
            $isActive = $this->parseBoolean($statusRaw);

            $employeeData = [
                'full_name'         => $fullName,
                'email'             => $email !== '' ? $email : null,
                'phone'             => $phone !== '' ? $phone : null,
                'address'           => $address !== '' ? $address : null,
                'department_id'     => $department->id,
                'position_id'       => $position->id,
                'joining_date'      => $joiningDate,
                'employment_status' => $employmentStatus,
                'basic_salary'      => $basicSalary,
                'is_active'         => $isActive,
            ];

            $existing = Employee::where('nik', $nik)->first();

            if ($existing) {
                if ($this->overwrite) {
                    $existing->update($employeeData);
                    $this->updatedCount++;
                } else {
                    $this->skippedCount++;
                }
            } else {
                Employee::create(array_merge(['nik' => $nik], $employeeData));
                $this->importedCount++;
            }
        }
    }

    private function mapColumnName(string $cell): ?string
    {
        // Remove underscores, dashes, spaces for flexible matching
        $clean = str_replace(['_', '-', ' '], '', strtolower($cell));

        if (in_array($clean, ['nik', 'nip', 'nokaryawan', 'idkaryawan', 'employeeid', 'kode', 'code'])) {
            return 'nik';
        }
        if (in_array($clean, ['fullname', 'nama', 'namalengkap', 'name', 'employeename'])) {
            return 'full_name';
        }
        if (in_array($clean, ['email', 'surel', 'mail'])) {
            return 'email';
        }
        if (in_array($clean, ['phone', 'telepon', 'telp', 'nohp', 'handphone', 'notelepon', 'notelp', 'mobile'])) {
            return 'phone';
        }
        if (in_array($clean, ['address', 'alamat', 'domisili'])) {
            return 'address';
        }
        if (in_array($clean, ['department', 'departemen', 'divisi', 'bagian', 'unit', 'dept'])) {
            return 'department';
        }
        if (in_array($clean, ['position', 'jabatan', 'posisi', 'role'])) {
            return 'position';
        }
        if (in_array($clean, ['joiningdate', 'joindate', 'tanggalbergabung', 'tglmasuk', 'tanggalmasuk', 'tglbergabung'])) {
            return 'joining_date';
        }
        if (in_array($clean, ['employmentstatus', 'statuskaryawan', 'statuskerja', 'statuskepegawaian', 'tipekaryawan'])) {
            return 'employment_status';
        }
        if (in_array($clean, ['basicsalary', 'gaji', 'gajipokok', 'salary', 'gajidasar', 'upah'])) {
            return 'basic_salary';
        }
        if (in_array($clean, ['status', 'accountstatus', 'statusakun', 'statusaktif', 'active', 'isactive'])) {
            return 'is_active';
        }

        return null;
    }

    private function transformDate($value): string
    {
        if (empty($value)) {
            return Carbon::today()->toDateString();
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return Carbon::today()->toDateString();
        }
    }

    private function transformEmploymentStatus($value): string
    {
        if (empty($value)) {
            return 'probation';
        }

        $clean = str_replace(['_', '-', ' '], '', strtolower((string)$value));

        if (in_array($clean, ['permanent', 'tetap', 'pkwtt'])) {
            return 'permanent';
        }
        if (in_array($clean, ['contract', 'kontrak', 'pkwt'])) {
            return 'contract';
        }
        if (in_array($clean, ['internship', 'intern', 'magang'])) {
            return 'internship';
        }
        if (in_array($clean, ['probation', 'percobaan', 'training'])) {
            return 'probation';
        }

        return 'probation';
    }

    private function parseNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        return is_numeric($clean) ? (float)$clean : 0.0;
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (int)$value > 0;
        
        $str = strtolower(trim((string)$value));
        return in_array($str, ['yes', 'true', '1', 'y', 'active', 'aktif']);
    }
}
