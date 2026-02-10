<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date_plan',
        'end_date_plan',
        'start_date_actual',
        'end_date_actual',
        'progress',
        'status',
        'priority',
        'parent_id',
    ];

    protected $casts = [
        'start_date_plan' => 'date',
        'end_date_plan' => 'date',
        'start_date_actual' => 'date',
        'end_date_actual' => 'date',
        'progress' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_members')
                    ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectTaskAttachment::class);
    }
}
