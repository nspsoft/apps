<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SafetyAccidentTracker extends Model
{
    use HasFactory;

    protected $table = 'safety_accident_trackers';

    protected $fillable = [
        'company_name',
        'last_incident_date',
        'target_safe_days',
        'total_recordable_cases',
        'lost_time_injuries',
        'updated_by',
    ];

    protected $casts = [
        'last_incident_date' => 'date',
        'target_safe_days' => 'integer',
        'total_recordable_cases' => 'integer',
        'lost_time_injuries' => 'integer',
    ];

    protected $appends = [
        'days_safe',
    ];

    public function getDaysSafeAttribute()
    {
        if (!$this->last_incident_date) return 0;
        return max(0, Carbon::parse($this->last_incident_date)->diffInDays(Carbon::now()));
    }

    public static function getCurrent()
    {
        $tracker = static::first();
        if (!$tracker) {
            $tracker = static::create([
                'company_name' => 'PT Jidoka Result Indonesia',
                'last_incident_date' => '2026-01-01',
                'target_safe_days' => 365,
                'total_recordable_cases' => 0,
                'lost_time_injuries' => 0,
            ]);
        }
        return $tracker;
    }
}
