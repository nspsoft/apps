<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyHazardReport extends Model
{
    use HasFactory;

    protected $table = 'safety_hazard_reports';

    protected $fillable = [
        'ticket_number',
        'reporter_user_id',
        'building_id',
        'zone_id',
        'hazard_type',
        'risk_level',
        'description',
        'location_details',
        'photo_before_path',
        'status',
        'assigned_pic_employee_id',
        'resolved_at',
        'action_taken_remarks',
        'photo_after_path',
        'resolved_by_user_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function building()
    {
        return $this->belongsTo(Audit5sBuilding::class, 'building_id');
    }

    public function zone()
    {
        return $this->belongsTo(Audit5sZone::class, 'zone_id');
    }

    public function assignedPic()
    {
        return $this->belongsTo(HrEmployee::class, 'assigned_pic_employee_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function finding()
    {
        return $this->hasOne(Audit5sFinding::class, 'hazard_report_id');
    }
}
