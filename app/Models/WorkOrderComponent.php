<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'bom_component_id',
        'product_id',
        'qty_required',
        'qty_consumed',
        'unit_id',
    ];

    protected $casts = [
        'qty_required' => 'decimal:4',
        'qty_consumed' => 'decimal:4',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function bomComponent(): BelongsTo
    {
        return $this->belongsTo(BomComponent::class);
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
        return $this->qty_required - $this->qty_consumed;
    }
}
