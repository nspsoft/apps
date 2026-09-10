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
        'address', 'department_id', 'section', 'position_id', 'golongan', 'tax_status',
        'joining_date', 'employment_status', 
        'basic_salary', 'salary_type', 'hourly_rate', 'profile_picture', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
