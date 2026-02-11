<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonConformanceReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function inspection()
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
