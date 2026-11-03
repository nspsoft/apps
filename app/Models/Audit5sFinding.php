<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sFinding extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_findings';

    protected $fillable = [
        'session_id',
        'zone_id',
        'pillar',
        'description',
        'before_photo_path',
        'is_red_tag',
        'red_tag_action',
        'due_date',
        'status',
        'after_photo_path',
        'remedy_notes',
        'remediated_by',
        'remediated_at',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'is_red_tag' => 'boolean',
        'due_date' => 'date',
        'remediated_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['before_photo_url', 'after_photo_url'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function session()
    {
        return $this->belongsTo(Audit5sSession::class, 'session_id');
    }

    public function zone()
    {
        return $this->belongsTo(Audit5sZone::class, 'zone_id');
    }

    public function remediatedBy()
    {
        return $this->belongsTo(User::class, 'remediated_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getBeforePhotoUrlAttribute(): ?string
    {
        if (!$this->before_photo_path) {
            return null;
        }

        if (str_starts_with($this->before_photo_path, 'http://') || str_starts_with($this->before_photo_path, 'https://')) {
            return $this->before_photo_path;
        }

        return asset('storage/' . $this->before_photo_path);
    }

    public function getAfterPhotoUrlAttribute(): ?string
    {
        if (!$this->after_photo_path) {
            return null;
        }

        if (str_starts_with($this->after_photo_path, 'http://') || str_starts_with($this->after_photo_path, 'https://')) {
            return $this->after_photo_path;
        }

        return asset('storage/' . $this->after_photo_path);
    }
}
