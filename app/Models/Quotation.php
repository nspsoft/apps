<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Quotation extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'number',
        'customer_id',
        'quotation_date',
        'valid_until',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'total',
        'created_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'double',
        'tax_amount' => 'double',
        'total_amount' => 'double',
    ];

    public static function generateNumber(): string
    {
        $prefix = 'QT-' . date('Ym');
        $last = static::where('number', 'like', $prefix . '%')->orderByDesc('number')->value('number');
        $sequence = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotal()
    {
        $this->subtotal = $this->items()->sum('total_price');
        // Default tax 11% if not explicitly set
        if ($this->tax == 0) {
            $this->tax = $this->subtotal * 0.11;
        }
        $this->total = $this->subtotal - ($this->discount ?? 0) + ($this->tax ?? 0);
        $this->save();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
