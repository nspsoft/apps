<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'invoice_number',
        'purchase_order_id',
        'supplier_id',
        'invoice_date',
        'due_date',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_total',
        'total_amount',
        'paid_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'double',
        'discount_amount' => 'double',
        'tax_amount' => 'double',
        'total_amount' => 'double',
        'paid_amount' => 'double',
    ];

    protected $appends = ['amount_due'];

    // Status constants
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get amount due (remaining unpaid amount)
     */
    public function getAmountDueAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    /**
     * Update payment status based on paid amount
     */
    public function updatePaymentStatus(): void
    {
        $totalAmount = (float) $this->total_amount;
        $paidAmount = (float) $this->paid_amount;

        if ($paidAmount <= 0) {
            $this->status = self::STATUS_UNPAID;
        } elseif ($paidAmount >= $totalAmount) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
    }

    /**
     * Recalculate paid amount from payments
     */
    public function recalculatePaidAmount(): void
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->save();
        $this->updatePaymentStatus();
    }

    /**
     * Generate Invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PINV-{$year}{$month}-";
        
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_UNPAID => 'red',
            self::STATUS_PARTIAL => 'amber',
            self::STATUS_PAID => 'emerald',
            self::STATUS_CANCELLED => 'slate',
            default => 'slate',
        };
    }
}
