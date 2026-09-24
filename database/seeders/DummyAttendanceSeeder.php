<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;

class DummyAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::where('is_active', true)->get();
        if ($employees->isEmpty()) {
            return;
        }

        $today = Carbon::today();

        // Seed 7 days back up to today
        for ($i = 7; $i >= 0; $i--) {
            $currentDate = $today->copy()->subDays($i);
            $dateStr = $currentDate->toDateString();

            // Skip Sundays
            if ($currentDate->isSunday()) {
                continue;
            }

            foreach ($employees as $emp) {
                // If it's today and attendance already exists (e.g. real clock-in from Nata Surya Permana), skip
                if ($i === 0) {
                    $existingToday = Attendance::where('employee_id', $emp->id)
                        ->where('date', $dateStr)
                        ->first();
                    if ($existingToday) {
                        continue;
                    }
                }

                // Check if already seeded for this date
                $alreadyExists = Attendance::where('employee_id', $emp->id)
                    ->where('date', $dateStr)
                    ->first();
                if ($alreadyExists) {
                    continue;
                }

                // Deterministic pseudo-random based on emp_id and date
                $seedVal = ($emp->id * 31 + $currentDate->dayOfYear) % 100;

                if ($seedVal < 72) {
                    // 72% Present on time (between 07:15 and 07:55)
                    $inMinute = ($emp->id * 7 + $i * 11) % 40 + 15; // 15..54
                    $clockIn = Carbon::parse("{$dateStr} 07:{$inMinute}:00");

                    // Clock out: if today, some have clocked out, some still working
                    $clockOut = null;
                    if ($i > 0) {
                        $outMinute = ($emp->id * 13 + $i * 5) % 45 + 15;
                        $clockOut = Carbon::parse("{$dateStr} 17:{$outMinute}:00");
                    } else {
                        // Today: 40% already clocked out
                        if ($seedVal % 10 < 4) {
                            $outMinute = ($emp->id * 9) % 30 + 10;
                            $clockOut = Carbon::parse("{$dateStr} 17:{$outMinute}:00");
                        }
                    }

                    Attendance::create([
                        'employee_id' => $emp->id,
                        'date' => $dateStr,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => 'present',
                        'late_minutes' => 0,
                        'early_leave_minutes' => 0,
                        'overtime_minutes' => 0,
                    ]);
                } elseif ($seedVal < 86) {
                    // 14% Late (between 08:05 and 08:45)
                    $inMinute = ($emp->id * 5 + $i * 7) % 40 + 5; // 05..44
                    $clockIn = Carbon::parse("{$dateStr} 08:{$inMinute}:00");
                    $lateMinutes = $inMinute;

                    $clockOut = null;
                    if ($i > 0) {
                        $outMinute = ($emp->id * 11) % 30 + 10;
                        $clockOut = Carbon::parse("{$dateStr} 17:{$outMinute}:00");
                    }

                    Attendance::create([
                        'employee_id' => $emp->id,
                        'date' => $dateStr,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => 'late',
                        'late_minutes' => $lateMinutes,
                        'early_leave_minutes' => 0,
                        'overtime_minutes' => 0,
                    ]);
                } elseif ($seedVal < 92) {
                    // 6% Leave
                    Attendance::create([
                        'employee_id' => $emp->id,
                        'date' => $dateStr,
                        'clock_in' => null,
                        'clock_out' => null,
                        'status' => 'leave',
                        'late_minutes' => 0,
                        'early_leave_minutes' => 0,
                        'overtime_minutes' => 0,
                        'note' => 'Izin / Cuti Karyawan',
                    ]);
                }
                // remaining 8% is absent (no attendance record)
            }
        }
    }
}
