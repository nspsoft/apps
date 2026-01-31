<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'type',
        'description',
        'started_at',
        'finished_at',
        'technician_name',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function spareparts()
    {
        return $this->belongsToMany(Sparepart::class, 'maintenance_sparepart_usage')
                    ->withPivot('qty_used')
                    ->withTimestamps();
    }
}
