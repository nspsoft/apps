<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcInspection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inspection_date' => 'datetime',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items()
    {
        return $this->hasMany(QcInspectionItem::class);
    }

    public function ncr()
    {
        return $this->hasOne(NonConformanceReport::class);
    }
}
