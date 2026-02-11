<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcMasterPoint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'standard_min' => 'decimal:2',
        'standard_max' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
