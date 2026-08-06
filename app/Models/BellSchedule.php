<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BellSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'time',
        'days',
        'sound_type',
        'sound_file',
        'tts_text',
        'volume',
        'is_active',
    ];

    protected $casts = [
        'days' => 'array',
        'is_active' => 'boolean',
        'volume' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'time', 'days', 'sound_type', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
