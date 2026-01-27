<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_adjustment_items';

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'qty_system',
        'qty_actual',
        'qty_difference',
    ];

    protected $casts = [
        'qty_before' => 'float',
        'qty_after' => 'float',
        'adjustment_qty' => 'float',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
