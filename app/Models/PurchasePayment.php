<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'bank_name',
        'account_number',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Payment method options
    const METHOD_TRANSFER = 'transfer';
    const METHOD_CASH = 'cash';
    const METHOD_GIRO = 'giro';
    const METHOD_CHEQUE = 'cheque';

    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_TRANSFER => 'Transfer Bank',
            self::METHOD_CASH => 'Tunai',
            self::METHOD_GIRO => 'Giro',
            self::METHOD_CHEQUE => 'Cek',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate payment number: PAY-YYYYMM-XXXX
     */
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PAY-{$year}{$month}-";

        $lastPayment = static::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Get payment method label
     */
    public function getMethodLabelAttribute(): string
    {
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }
}
