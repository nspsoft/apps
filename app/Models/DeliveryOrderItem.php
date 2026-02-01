<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeliveryOrderItem extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['qty_ordered', 'qty_delivered', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'delivery_order_id',
        'sales_order_item_id',
        'product_id',
        'qty_ordered',
        'qty_delivered',
        'unit_id',
        'location_id',
        'batch_number',
        'notes',
        'qty_invoiced',
    ];

    protected $casts = [
        'qty_ordered' => 'float',
        'qty_delivered' => 'float',
        'qty_invoiced' => 'float',
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    protected $appends = [];

    public function getCurrentStockAttribute(): float
    {
        if (!$this->deliveryOrder) {
            return 0;
        }

        $stock = ProductStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->deliveryOrder->warehouse_id)
            ->first();

        return $stock ? (float) $stock->qty_on_hand : 0;
    }
}
