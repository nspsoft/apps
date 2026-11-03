<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit5sRating extends Model
{
    use HasFactory;

    protected $table = 'audit_5s_ratings';

    protected $fillable = [
        'session_id',
        'checklist_id',
        'pillar',
        'score',
        'notes',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(Audit5sSession::class, 'session_id');
    }

    public function checklist()
    {
        return $this->belongsTo(Audit5sChecklist::class, 'checklist_id');
    }
}
