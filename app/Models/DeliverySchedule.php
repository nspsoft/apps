<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySchedule extends Model
{
    //
    protected $fillable = [
        'customer_id',
        'product_id',
        'delivery_date',
        'qty_scheduled',
        'po_number',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'qty_scheduled' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
