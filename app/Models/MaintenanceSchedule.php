<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'task_name',
        'description',
        'frequency_days',
        'last_performed_at',
        'next_due_date',
        'status',
    ];

    protected $casts = [
        'last_performed_at' => 'date',
        'next_due_date' => 'date',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
