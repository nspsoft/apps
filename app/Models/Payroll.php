<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payroll extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'hr_payrolls';
    protected $fillable = [
        'employee_id', 'period_month', 'period_year', 
        'cutoff_start', 'cutoff_end',
        'total_working_hours', 'total_overtime_hours',
        'total_working_days', 'total_overtime_days',
        'basic_salary', 'hourly_rate',
        'total_allowances', 'total_deductions', 
        'net_salary', 'rounded_net_salary',
        'status', 'payment_date', 'note'
    ];

    protected $casts = [
        'cutoff_start' => 'date',
        'cutoff_end' => 'date',
        'payment_date' => 'datetime',
        'total_working_hours' => 'double',
        'total_overtime_hours' => 'double',
        'total_working_days' => 'integer',
        'total_overtime_days' => 'integer',
        'basic_salary' => 'double',
        'hourly_rate' => 'double',
        'total_allowances' => 'double',
        'total_deductions' => 'double',
        'net_salary' => 'double',
        'rounded_net_salary' => 'double',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
