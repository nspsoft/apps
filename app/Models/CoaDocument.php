<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'issued_date' => 'date',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
