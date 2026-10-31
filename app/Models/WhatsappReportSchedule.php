<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'report_type',
        'frequency',
        'send_time',
        'day_of_week',
        'day_of_month',
        'formats',
        'target_type',
        'recipients',
        'whatsapp_session',
        'include_ai_summary',
        'is_active',
        'last_sent_at',
        'last_status',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'formats' => 'array',
        'recipients' => 'array',
        'include_ai_summary' => 'boolean',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
