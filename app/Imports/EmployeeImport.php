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
            $rowArray = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array)$row);
            
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
            $row = is_array($rows[$i]) ? $rows[$i] : (method_exists($rows[$i], 'toArray') ? $rows[$i]->toArray() : (array)$rows[$i]);

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

            $existing = Employee::where('nik', $nik)->first();

            // Department resolution
            $deptNameRaw = isset($headerMap['department']) ? ($row[$headerMap['department']] ?? null) : null;
            $deptName = trim((string)$deptNameRaw);

            // Position resolution
            $posNameRaw = isset($headerMap['position']) ? ($row[$headerMap['position']] ?? null) : null;
            $posName = trim((string)$posNameRaw);

            $departmentId = null;
            if ($deptName !== '') {
                $department = Department::firstOrCreate(
                    ['name' => $deptName],
                    [
                        'code' => Str::upper(Str::slug($deptName)),
                        'is_active' => true,
                    ]
                );
                $departmentId = $department->id;
            } elseif ($existing) {
                $departmentId = $existing->department_id;
            } else {
                $department = Department::firstOrCreate(
                    ['name' => 'General'],
                    ['code' => 'GENERAL', 'is_active' => true]
                );
                $departmentId = $department->id;
            }

            $positionId = null;
            if ($posName !== '') {
                $position = Position::firstOrCreate(
                    [
                        'name' => $posName,
                        'department_id' => $departmentId,
                    ],
                    ['is_active' => true]
                );
                $positionId = $position->id;
            } elseif ($existing) {
                $positionId = $existing->position_id;
            } else {
                $position = Position::firstOrCreate(
                    [
                        'name' => 'Staff',
                        'department_id' => $departmentId,
                    ],
                    ['is_active' => true]
                );
                $positionId = $position->id;
            }

            // String helper: empty string or '-' returns null
            $cleanStr = function($val) {
                if (is_null($val)) return null;
                $t = trim((string)$val);
                return ($t === '' || $t === '-') ? null : $t;
            };

            // Build data payload
            $employeeData = [
                'full_name'     => $fullName,
                'department_id' => $departmentId,
                'position_id'   => $positionId,
            ];

            if (isset($headerMap['email'])) {
                $employeeData['email'] = $cleanStr($row[$headerMap['email']] ?? null);
            } elseif (!$existing) {
                $employeeData['email'] = null;
            }

            if (isset($headerMap['phone'])) {
                $employeeData['phone'] = $cleanStr($row[$headerMap['phone']] ?? null);
            } elseif (!$existing) {
                $employeeData['phone'] = null;
            }

            if (isset($headerMap['address'])) {
                $employeeData['address'] = $cleanStr($row[$headerMap['address']] ?? null);
            } elseif (!$existing) {
                $employeeData['address'] = null;
            }

            if (isset($headerMap['section'])) {
                $employeeData['section'] = $cleanStr($row[$headerMap['section']] ?? null);
            } elseif (!$existing) {
                $employeeData['section'] = null;
            }

            if (isset($headerMap['golongan'])) {
                $employeeData['golongan'] = $cleanStr($row[$headerMap['golongan']] ?? null);
            } elseif (!$existing) {
                $employeeData['golongan'] = null;
            }

            if (isset($headerMap['tax_status'])) {
                $employeeData['tax_status'] = $cleanStr($row[$headerMap['tax_status']] ?? null);
            } elseif (!$existing) {
                $employeeData['tax_status'] = null;
            }

            if (isset($headerMap['salary_type'])) {
                $salaryTypeRaw = strtolower(trim((string)($row[$headerMap['salary_type']] ?? '')));
                if ($salaryTypeRaw !== '') {
                    $employeeData['salary_type'] = in_array($salaryTypeRaw, ['hourly', 'per jam', 'perjam', 'jam', 'harian']) ? 'hourly' : 'monthly';
                } elseif (!$existing) {
                    $employeeData['salary_type'] = 'monthly';
                }
            } elseif (!$existing) {
                $employeeData['salary_type'] = 'monthly';
            }

            if (isset($headerMap['hourly_rate'])) {
                $val = $row[$headerMap['hourly_rate']] ?? null;
                if (!is_null($val) && trim((string)$val) !== '' && trim((string)$val) !== '-') {
                    $hr = $this->parseNumeric($val);
                    $employeeData['hourly_rate'] = $hr > 0 ? $hr : null;
                } elseif (!$existing) {
                    $employeeData['hourly_rate'] = null;
                }
            } elseif (!$existing) {
                $employeeData['hourly_rate'] = null;
            }

            if (isset($headerMap['basic_salary'])) {
                $val = $row[$headerMap['basic_salary']] ?? null;
                if (!is_null($val) && trim((string)$val) !== '' && trim((string)$val) !== '-') {
                    $employeeData['basic_salary'] = $this->parseNumeric($val);
                } elseif (!$existing) {
                    $employeeData['basic_salary'] = 0;
                }
            } elseif (!$existing) {
                $employeeData['basic_salary'] = 0;
            }

            if (isset($headerMap['joining_date'])) {
                $employeeData['joining_date'] = $this->transformDate($row[$headerMap['joining_date']] ?? null);
            } elseif (!$existing) {
                $employeeData['joining_date'] = Carbon::today()->toDateString();
            }

            if (isset($headerMap['employment_status'])) {
                $employeeData['employment_status'] = $this->transformEmploymentStatus($row[$headerMap['employment_status']] ?? null);
            } elseif (!$existing) {
                $employeeData['employment_status'] = 'probation';
            }

            if (isset($headerMap['is_active'])) {
                $employeeData['is_active'] = $this->parseBoolean($row[$headerMap['is_active']] ?? true);
            } elseif (!$existing) {
                $employeeData['is_active'] = true;
            }

            if (isset($headerMap['has_bpjstk'])) {
                $employeeData['has_bpjstk'] = $this->parseBoolean($row[$headerMap['has_bpjstk']] ?? false);
            } elseif (!$existing) {
                $employeeData['has_bpjstk'] = false;
            }

            if (isset($headerMap['has_bpjskes'])) {
                $employeeData['has_bpjskes'] = $this->parseBoolean($row[$headerMap['has_bpjskes']] ?? false);
            } elseif (!$existing) {
                $employeeData['has_bpjskes'] = false;
            }

            if (isset($headerMap['bpjstk_number'])) {
                $employeeData['bpjstk_number'] = $cleanStr($row[$headerMap['bpjstk_number']] ?? null);
            } elseif (!$existing) {
                $employeeData['bpjstk_number'] = null;
            }

            if (isset($headerMap['bpjskes_number'])) {
                $employeeData['bpjskes_number'] = $cleanStr($row[$headerMap['bpjskes_number']] ?? null);
            } elseif (!$existing) {
                $employeeData['bpjskes_number'] = null;
            }

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
        // Remove symbols, spaces, parentheses, underscores, dashes
        $clean = preg_replace('/[^a-z0-9]/', '', strtolower($cell));

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
        if (in_array($clean, ['department', 'departemen', 'divisi', 'dept'])) {
            return 'department';
        }
        if (in_array($clean, ['section', 'bagian', 'unit', 'kelompok', 'group'])) {
            return 'section';
        }
        if (in_array($clean, ['position', 'jabatan', 'posisi', 'role'])) {
            return 'position';
        }
        if (in_array($clean, ['golongan', 'grade', 'gol', 'level'])) {
            return 'golongan';
        }
        if (in_array($clean, ['taxstatus', 'statuspajak', 'ptkp', 'tanggungan'])) {
            return 'tax_status';
        }
        if (in_array($clean, ['joiningdate', 'joindate', 'tanggalbergabung', 'tglmasuk', 'tanggalmasuk', 'tglbergabung'])) {
            return 'joining_date';
        }
        if (in_array($clean, ['employmentstatus', 'statuskaryawan', 'statuskerja', 'statuskepegawaian', 'tipekaryawan'])) {
            return 'employment_status';
        }
        if (in_array($clean, ['salarytype', 'tipegaji', 'sistemgaji', 'jenissalary', 'skemapenggajian', 'tipepenggajian', 'skemagaji', 'jenispenggajian'])) {
            return 'salary_type';
        }
        if (in_array($clean, ['hourlyrate', 'priceperhour', 'pricehour', 'tarifperjam', 'upahperjam', 'tarifjam', 'ratejam', 'rateperjam'])) {
            return 'hourly_rate';
        }
        if (in_array($clean, ['basicsalary', 'gaji', 'gajipokok', 'salary', 'gajidasar', 'upah'])) {
            return 'basic_salary';
        }
        if (in_array($clean, ['status', 'accountstatus', 'statusakun', 'statusaktif', 'active', 'isactive'])) {
            return 'is_active';
        }
        if (in_array($clean, ['bpjstk', 'bpjsketenagakerjaan', 'hasbpjstk', 'ikutbpjstk', 'bpjstkaktif', 'isbpjstk', 'statusbpjstk', 'pesertabpjstk'])) {
            return 'has_bpjstk';
        }
        if (in_array($clean, ['bpjskes', 'bpjskesehatan', 'hasbpjskes', 'ikutbpjskes', 'bpjskesaktif', 'isbpjskes', 'statusbpjskes', 'pesertabpjskes'])) {
            return 'has_bpjskes';
        }
        if (in_array($clean, ['nobpjstk', 'nomorbpjstk', 'bpjstknumber', 'kartubpjstk', 'nokartubpjstk', 'nomorkartubpjstk', 'nobpjsketenagakerjaan', 'nomorbpjsketenagakerjaan'])) {
            return 'bpjstk_number';
        }
        if (in_array($clean, ['nobpjskes', 'nomorbpjskes', 'bpjskesnumber', 'kartubpjskes', 'nokartubpjskes', 'nomorkartubpjskes', 'nobpjskesehatan', 'nomorbpjskesehatan'])) {
            return 'bpjskes_number';
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

            $date = Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return Carbon::today()->toDateString();
        }
    }

    private function transformEmploymentStatus(?string $status): string
    {
        if (empty($status)) {
            return 'probation';
        }

        $clean = strtolower(trim($status));

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
        return in_array($str, ['yes', 'true', '1', 'y', 'ya', 'active', 'aktif', 'ikut', 'ada', 'dapat', 'on']);
    }
}
