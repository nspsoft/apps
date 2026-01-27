<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Carbon\Carbon;

class AttendanceImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    public function model(array $row)
    {
        // Skip if mandatory fields are missing
        if (!isset($row['nik']) || !isset($row['date']) || !isset($row['clock_in'])) {
            return null;
        }

        // Find employee by NIK
        $employee = Employee::where('nik', (string)$row['nik'])->first();
        if (!$employee) {
            return null; // Or log error: Employee not found
        }

        $date = $this->transformDate($row['date']);
        $clockIn = $this->transformTime($row['clock_in']);
        $clockOut = isset($row['clock_out']) ? $this->transformTime($row['clock_out']) : null;

        // Fetch settings for calculation
        $standardStart = \App\Models\PayrollSetting::getByKey('standard_start_time', '08:00');
        $standardEnd = \App\Models\PayrollSetting::getByKey('standard_end_time', '17:00');

        $clockInTime = Carbon::parse($date . ' ' . $clockIn);
        $clockOutTime = $clockOut ? Carbon::parse($date . ' ' . $clockOut) : null;
        $standardStartTime = Carbon::parse($date . ' ' . $standardStart);
        $standardEndTime = Carbon::parse($date . ' ' . $standardEnd);

        // Calculations
        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;

        // Late
        if ($clockInTime->greaterThan($standardStartTime)) {
            $lateMinutes = $clockInTime->diffInMinutes($standardStartTime);
        }

        // Early Leave & Overtime (if clocked out)
        if ($clockOutTime) {
            if ($clockOutTime->lessThan($standardEndTime)) {
                $earlyLeaveMinutes = $standardEndTime->diffInMinutes($clockOutTime);
            } elseif ($clockOutTime->greaterThan($standardEndTime)) {
                $overtimeMinutes = $clockOutTime->diffInMinutes($standardEndTime);
            }
        }

        $status = $lateMinutes > 0 ? 'late' : 'present';

        return Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'clock_in' => $clockInTime,
                'clock_out' => $clockOutTime,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'note' => $row['note'] ?? 'Imported from Fingerprint',
            ]
        );
    }

    private function transformDate($value)
    {
        try {
            // Check if it's Excel numeric date
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return now()->toDateString();
        }
    }

    private function transformTime($value)
    {
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
            }
            return date('H:i:s', strtotime($value));
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }
}
