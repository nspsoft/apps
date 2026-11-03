<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sZone extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_zones';

    protected $fillable = [
        'building_id',
        'name',
        'code',
        'area_type',
        'department_id',
        'pic_employee_id',
        'custom_pic_photo',
        'map_x_percent',
        'map_y_percent',
        'latest_score',
        'latest_grade',
        'latest_audit_date',
        'open_findings_count',
        'red_tags_count',
        'is_active',
    ];

    protected $casts = [
        'map_x_percent' => 'float',
        'map_y_percent' => 'float',
        'latest_score' => 'float',
        'latest_audit_date' => 'date',
        'open_findings_count' => 'integer',
        'red_tags_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['pic_photo_url', 'last_score', 'last_grade', 'last_audit_date'];

    public function getLastScoreAttribute(): ?float
    {
        return $this->latest_score !== null ? (float) $this->latest_score : null;
    }

    public function getLastGradeAttribute(): ?string
    {
        return $this->latest_grade;
    }

    public function getLastAuditDateAttribute(): ?string
    {
        return $this->latest_audit_date?->format('Y-m-d');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function building()
    {
        return $this->belongsTo(Audit5sBuilding::class, 'building_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function picEmployee()
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id');
    }

    public function sessions()
    {
        return $this->hasMany(Audit5sSession::class, 'zone_id');
    }

    public function findings()
    {
        return $this->hasMany(Audit5sFinding::class, 'zone_id');
    }

    public function getPicPhotoUrlAttribute(): ?string
    {
        if ($this->custom_pic_photo) {
            return asset('storage/' . $this->custom_pic_photo);
        }

        if ($this->picEmployee) {
            if ($this->picEmployee->profile_picture) {
                return asset('storage/' . $this->picEmployee->profile_picture);
            }
            if ($this->picEmployee->user && $this->picEmployee->user->profile_photo_url) {
                return $this->picEmployee->user->profile_photo_url;
            }
        }

        return null;
    }
}
