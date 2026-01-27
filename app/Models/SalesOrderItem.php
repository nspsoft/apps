<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesOrderItem extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['qty', 'unit_price', 'discount_percent', 'subtotal'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'qty',
        'unit_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'qty_delivered',
        'qty_returned',
        'qty_invoiced',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'discount_amount' => 'double',
        'subtotal' => 'double',
        'qty_delivered' => 'decimal:4',
        'qty_returned' => 'decimal:4',
        'qty_invoiced' => 'decimal:4',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getRemainingQtyAttribute(): float
    {
        return $this->qty - ($this->qty_delivered - $this->qty_returned);
    }

    public function isFullyDelivered(): bool
    {
        return $this->qty_delivered >= $this->qty;
    }

    protected static function booted(): void
    {
        static::saving(function (SalesOrderItem $item) {
            $gross = $item->qty * $item->unit_price;
            $discountAmount = $gross * ($item->discount_percent / 100);
            $item->discount_amount = $discountAmount;
            $item->subtotal = $gross - $discountAmount;
        });

        static::saved(function (SalesOrderItem $item) {
            $item->salesOrder->calculateTotals();
        });

        static::deleted(function (SalesOrderItem $item) {
            $item->salesOrder->calculateTotals();
        });
    }
}
