<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $table = 'hr_work_schedules';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkScheduleDetail::class, 'work_schedule_id')->orderBy('day_of_week');
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'work_schedule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the schedule detail for a specific day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
     */
    public function getScheduleForDay(int $dayOfWeek): ?WorkScheduleDetail
    {
        return $this->details->firstWhere('day_of_week', $dayOfWeek);
    }

    /**
     * Get the schedule detail for a specific date
     */
    public function getScheduleForDate($date): ?WorkScheduleDetail
    {
        $carbon = \Carbon\Carbon::parse($date);
        return $this->getScheduleForDay($carbon->dayOfWeek);
    }
}
