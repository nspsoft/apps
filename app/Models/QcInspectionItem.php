<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcInspectionItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'actual_value' => 'decimal:2',
        'is_pass' => 'boolean',
    ];

    public function inspection()
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }

    public function masterPoint()
    {
        return $this->belongsTo(QcMasterPoint::class, 'qc_master_point_id');
    }
}
