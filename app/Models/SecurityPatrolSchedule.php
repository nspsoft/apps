<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityPatrolSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_id',
        'name',
        'start_time',
        'end_time',
        'checkpoints_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'checkpoints_count' => 'integer',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function checkpoints()
    {
        return $this->belongsToMany(
            SecurityCheckpoint::class,
            'security_patrol_schedule_checkpoints',
            'schedule_id',
            'checkpoint_id'
        )->withPivot('sort_order')->orderBy('pivot_sort_order');
    }

    public function sessions()
    {
        return $this->hasMany(SecurityPatrolSession::class, 'schedule_id');
    }
}
