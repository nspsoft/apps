<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkScheduleDetail extends Model
{
    protected $table = 'hr_work_schedule_details';

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'is_workday',
        'start_time',
        'end_time',
        'break_minutes',
    ];

    protected $casts = [
        'is_workday' => 'boolean',
        'day_of_week' => 'integer',
        'break_minutes' => 'integer',
    ];

    public function workSchedule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    public static function getDayNames(): array
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
    }

    public function getDayNameAttribute(): string
    {
        return self::getDayNames()[$this->day_of_week] ?? 'Unknown';
    }
}
