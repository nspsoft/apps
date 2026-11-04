<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyDevicePoint extends Model
{
    use HasFactory;

    protected $table = 'safety_device_points';

    protected $fillable = [
        'building_id',
        'zone_id',
        'device_type',
        'code',
        'name',
        'map_x_percent',
        'map_y_percent',
        'inspection_status',
        'last_checked_at',
        'expiration_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'map_x_percent' => 'float',
        'map_y_percent' => 'float',
        'last_checked_at' => 'date',
        'expiration_date' => 'date',
    ];

    public function building()
    {
        return $this->belongsTo(Audit5sBuilding::class, 'building_id');
    }

    public function zone()
    {
        return $this->belongsTo(Audit5sZone::class, 'zone_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
