<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyInspectionItem extends Model
{
    use HasFactory;

    protected $table = 'safety_inspection_items';

    protected $fillable = [
        'inspection_id',
        'category',
        'checkpoint_title',
        'status',
        'remarks',
        'photo_path',
    ];

    public function inspection()
    {
        return $this->belongsTo(SafetyInspection::class, 'inspection_id');
    }
}
