<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityCheckpoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'area_zone',
        'latitude',
        'longitude',
        'geofence_radius',
        'qr_token',
        'sop_items',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius' => 'integer',
        'sop_items' => 'array',
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->belongsToMany(
            SecurityPatrolSchedule::class,
            'security_patrol_schedule_checkpoints',
            'checkpoint_id',
            'schedule_id'
        )->withPivot('sort_order');
    }

    public function logs()
    {
        return $this->hasMany(SecurityPatrolLog::class, 'checkpoint_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
