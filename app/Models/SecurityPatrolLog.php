<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityPatrolLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'patrol_session_id',
        'checkpoint_id',
        'user_id',
        'scanned_at',
        'lat_actual',
        'lng_actual',
        'distance_meters',
        'is_deviation',
        'condition_status',
        'checklist_answers',
        'photo_path',
        'notes',
        'ga_ticket_id',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'lat_actual' => 'float',
        'lng_actual' => 'float',
        'distance_meters' => 'float',
        'is_deviation' => 'boolean',
        'checklist_answers' => 'array',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? '/storage/' . $this->photo_path : null;
    }

    public function session()
    {
        return $this->belongsTo(SecurityPatrolSession::class, 'patrol_session_id');
    }

    public function checkpoint()
    {
        return $this->belongsTo(SecurityCheckpoint::class, 'checkpoint_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gaTicket()
    {
        return $this->belongsTo(GaTicket::class, 'ga_ticket_id');
    }
}
