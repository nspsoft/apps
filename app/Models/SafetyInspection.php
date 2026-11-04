<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyInspection extends Model
{
    use HasFactory;

    protected $table = 'safety_inspections';

    protected $fillable = [
        'inspection_number',
        'building_id',
        'zone_id',
        'inspector_user_id',
        'inspection_date',
        'status',
        'overall_score',
        'risk_status',
        'findings_count',
        'notes',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'overall_score' => 'float',
        'findings_count' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Audit5sBuilding::class, 'building_id');
    }

    public function zone()
    {
        return $this->belongsTo(Audit5sZone::class, 'zone_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function items()
    {
        return $this->hasMany(SafetyInspectionItem::class, 'inspection_id');
    }

    public function findings()
    {
        return $this->hasMany(Audit5sFinding::class, 'safety_inspection_id');
    }
}
