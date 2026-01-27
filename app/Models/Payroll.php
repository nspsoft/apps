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
        'basic_salary', 'total_allowances', 'total_deductions', 
        'net_salary', 'status', 'payment_date', 'note'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'basic_salary' => 'double',
        'allowances' => 'double',
        'deductions' => 'double',
        'net_salary' => 'double',
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
