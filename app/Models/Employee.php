<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'hr_employees';
    protected $fillable = [
        'user_id', 'nik', 'full_name', 'email', 'phone', 
        'address', 'department_id', 'section', 'position_id', 'work_schedule_id', 'golongan', 'tax_status',
        'joining_date', 'employment_status', 
        'basic_salary', 'salary_type', 'hourly_rate',
        'has_bpjstk', 'has_bpjskes', 'bpjstk_number', 'bpjskes_number',
        'profile_picture', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_bpjstk' => 'boolean',
        'has_bpjskes' => 'boolean',
        'basic_salary' => 'double',
        'hourly_rate' => 'double',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function workSchedule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    /**
     * Get the effective schedule detail for a specific date
     */
    public function getScheduleForDate($date): ?WorkScheduleDetail
    {
        $schedule = $this->workSchedule;
        if (!$schedule) {
            $schedule = WorkSchedule::default()->active()->with('details')->first();
        }
        if (!$schedule) {
            $schedule = WorkSchedule::active()->with('details')->first();
        }

        return $schedule ? $schedule->getScheduleForDate($date) : null;
    }

    public function leaveBalances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\HR\LeaveBalance::class);
    }

    public function leaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\HR\Leave::class);
    }

    public function attendanceRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\HR\AttendanceRequest::class);
    }

    public function overtimeRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\HR\OvertimeRequest::class);
    }
}
