<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'qty_ordered',
        'qty_received',
        'qty_rejected',
        'unit_id',
        'unit_cost',
        'location_id',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'qty_ordered' => 'float',
        'qty_received' => 'float',
        'qty_accepted' => 'float',
        'qty_rejected' => 'float',
        'unit_price' => 'double',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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

    /**
     * Get accepted quantity
     */
    public function getQtyAcceptedAttribute(): float
    {
        return $this->qty_received - $this->qty_rejected;
    }

    /**
     * Get total value
     */
    public function getTotalValueAttribute(): float
    {
        return $this->qty_accepted * $this->unit_cost;
    }
}
