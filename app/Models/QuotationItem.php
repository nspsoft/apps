<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'qty',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'subtotal' => 'double',
    ];

    protected $appends = ['product_name'];

    public function getProductNameAttribute()
    {
        return $this->product->name ?? 'Unknown Product';
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
