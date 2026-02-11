<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesForecast extends Model
{
    //
    protected $fillable = [
        'customer_id',
        'product_id',
        'period',
        'qty_forecast',
        'notes',
        'created_by',
        'sales_name',
    ];

    protected $casts = [
        'period' => 'date',
        'qty_forecast' => 'decimal:2',
    ];

    protected $appends = ['accuracy'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAccuracyAttribute()
    {
        if (!$this->qty_forecast || $this->qty_forecast <= 0) {
            return 0;
        }

        // qty_actual is calculated in the controller and added to the model
        $actual = (float) ($this->qty_actual ?? 0);
        $forecast = (float) $this->qty_forecast;

        $diff = abs($forecast - $actual);
        $accuracy = (1 - ($diff / $forecast)) * 100;

        return round(max(0, min(100, $accuracy)), 2);
    }
}
