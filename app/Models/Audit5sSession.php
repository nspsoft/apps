<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sSession extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_sessions';

    protected $fillable = [
        'session_code',
        'schedule_id',
        'zone_id',
        'auditor_id',
        'audit_date',
        'score_sort',
        'score_set_in_order',
        'score_shine',
        'score_standardize',
        'score_sustain',
        'total_score',
        'grade',
        'status',
        'summary_notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'score_sort' => 'float',
        'score_set_in_order' => 'float',
        'score_shine' => 'float',
        'score_standardize' => 'float',
        'score_sustain' => 'float',
        'total_score' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function schedule()
    {
        return $this->belongsTo(Audit5sSchedule::class, 'schedule_id');
    }

    public function zone()
    {
        return $this->belongsTo(Audit5sZone::class, 'zone_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function ratings()
    {
        return $this->hasMany(Audit5sRating::class, 'session_id');
    }

    public function findings()
    {
        return $this->hasMany(Audit5sFinding::class, 'session_id');
    }
}
