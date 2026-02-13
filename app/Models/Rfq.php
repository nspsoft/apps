<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rfq_number',
        'title',
        'description',
        'deadline',
        'status',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    // Status Constants
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_AWARDED = 'awarded';

    public function items()
    {
        return $this->hasMany(RfqItem::class);
    }

    public function targetSuppliers()
    {
        return $this->belongsToMany(Supplier::class, 'rfq_suppliers')
                    ->using(RfqSupplier::class)
                    ->withPivot(['status', 'viewed_at', 'responded_at'])
                    ->withTimestamps();
    }

    public function quotations()
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber()
    {
        $prefix = 'RFQ-' . date('Ym') . '-';
        $last = self::where('rfq_number', 'like', $prefix . '%')->latest('id')->first();
        $num = $last ? intval(substr($last->rfq_number, -4)) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
