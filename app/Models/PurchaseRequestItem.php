<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'qty',
        'description',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
