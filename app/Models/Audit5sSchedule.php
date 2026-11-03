<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sSchedule extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_schedules';

    protected $fillable = [
        'title',
        'frequency',
        'scheduled_date',
        'auditor_id',
        'building_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function building()
    {
        return $this->belongsTo(Audit5sBuilding::class, 'building_id');
    }

    public function sessions()
    {
        return $this->hasMany(Audit5sSession::class, 'schedule_id');
    }
}
