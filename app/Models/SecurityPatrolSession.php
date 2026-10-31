<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityPatrolSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_code',
        'schedule_id',
        'user_id',
        'shift_name',
        'patrol_date',
        'start_time',
        'end_time',
        'duration_seconds',
        'total_checkpoints',
        'completed_checkpoints',
        'missed_checkpoints',
        'incidents_count',
        'status',
        'notes',
    ];

    protected $casts = [
        'patrol_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_seconds' => 'integer',
        'total_checkpoints' => 'integer',
        'completed_checkpoints' => 'integer',
        'missed_checkpoints' => 'integer',
        'incidents_count' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(SecurityPatrolSchedule::class, 'schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(SecurityPatrolLog::class, 'patrol_session_id');
    }
}
