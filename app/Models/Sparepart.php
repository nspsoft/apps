<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'part_number',
        'location',
        'stock',
        'min_stock',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
