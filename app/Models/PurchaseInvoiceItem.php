<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'goods_receipt_item_id',
        'product_id',
        'description',
        'qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'discount_amount' => 'double',
        'subtotal' => 'double',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
