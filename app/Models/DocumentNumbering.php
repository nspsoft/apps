<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNumbering extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'code',
        'name',
        'prefix',
        'format',
        'padding',
        'current_number',
        'reset_period',
        'last_reset_date',
        'separator',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'padding' => 'integer',
        'last_reset_date' => 'date',
    ];

    /**
     * Helper to get full module name
     */
    public function getModuleNameAttribute(): string
    {
        return ucfirst($this->module);
    }
}
