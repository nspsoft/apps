<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesInvoice extends Model
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
        'sales_order_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'double',
        'tax_amount' => 'double',
        'discount_amount' => 'double',
        'total' => 'double',
        'paid_amount' => 'double',
        'balance' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateInvoiceNumber($customerId): string
    {
        $customer = Customer::find($customerId);
        $custCode = $customer ? ($customer->code ?? 'GEN') : 'GEN';
        $monthRoman = static::getRomanMonth((int)date('n'));
        $yearShort = date('y');
        
        // Format: {RUN}/INV/JRI-{CUST}/{MONTH_ROMAN}/{YEAR_2DIGIT}
        $suffix = "/INV/JRI-{$custCode}/{$monthRoman}/{$yearShort}";
        
        // Find last running number for invoices using REGEXP to catch {number}/INV/JRI-
        $last = static::where('invoice_number', 'REGEXP', '^[0-9]+/INV/JRI-')
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_number, "/", 1) AS UNSIGNED) DESC')
            ->first();

        if ($last) {
            $parts = explode('/', $last->invoice_number);
            $lastNumber = is_numeric($parts[0]) ? (int)$parts[0] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $newNumber . $suffix;
    }

    private static function getRomanMonth($month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[$month] ?? 'I';
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        // If SalesOrder has tax_percent, use it, otherwise default to 11
        $taxPercent = $this->salesOrder->tax_percent ?? 11;
        $this->tax_amount = $this->subtotal * ($taxPercent / 100);
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance = $this->total - $this->paid_amount;
        $this->save();
    }

    public function addPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance = $this->total - $this->paid_amount;
        
        if ($this->balance <= 0) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
        
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_SENT => 'blue',
            self::STATUS_PARTIAL => 'amber',
            self::STATUS_PAID => 'emerald',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'slate',
            default => 'slate',
        };
    }
}
