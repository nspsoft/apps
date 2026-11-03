<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sBuilding extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_buildings';

    protected $fillable = [
        'name',
        'code',
        'floor_plan_image',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function zones()
    {
        return $this->hasMany(Audit5sZone::class, 'building_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
