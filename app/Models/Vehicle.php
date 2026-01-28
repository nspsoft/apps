<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'license_plate',
        'vehicle_type',
        'brand',
        'capacity_weight',
        'capacity_volume',
        'driver_name',
        'status',
        'notes',
        'is_active'
    ];

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}
