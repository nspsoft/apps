<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UatScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'feature',
        'code',
        'title',
        'description',
        'acceptance_criteria',
        'status',
        'tested_by',
        'tested_at',
        'notes',
        'custom_order',
    ];

    protected $casts = [
        'tested_at' => 'datetime',
    ];

    public function tester()
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
