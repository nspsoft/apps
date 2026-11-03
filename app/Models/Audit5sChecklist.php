<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Audit5sChecklist extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'audit_5s_checklists';

    protected $fillable = [
        'pillar',
        'title',
        'guideline_notes',
        'weight_points',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weight_points' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function ratings()
    {
        return $this->hasMany(Audit5sRating::class, 'checklist_id');
    }
}
