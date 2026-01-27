<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GoodsReceipt extends Model
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
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'supplier_invoice',
        'invoice_date',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'invoice_date' => 'date',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSPECTED = 'inspected';
    const STATUS_COMPLETED = 'completed';

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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Generate GRN number
     */
    public static function generateGrnNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "GRN-{$year}{$month}-";
        
        $lastGrn = static::where('grn_number', 'like', "{$prefix}%")
            ->orderBy('grn_number', 'desc')
            ->first();

        if ($lastGrn) {
            $lastNumber = (int) substr($lastGrn->grn_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Complete goods receipt and update stock
     */
    public function complete(): void
    {
        foreach ($this->items as $item) {
            // Update PO item received qty
            $poItem = $item->purchaseOrderItem;
            $poItem->qty_received += $item->qty_received;
            $poItem->save();

            // Update product stock
            $stock = ProductStock::firstOrCreate(
                [
                    'product_id' => $item->product_id,
                    'warehouse_id' => $this->warehouse_id,
                    'location_id' => $item->location_id,
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 0,
                ]
            );

            $stock->adjustStock(
                $item->qty_received,
                $item->unit_cost,
                StockMovement::TYPE_PO_RECEIVE,
                $this,
                "Goods Receipt #{$this->grn_number}"
            );
        }

        // Update PO status
        $po = $this->purchaseOrder;
        if ($po) {
            $po->refresh(); // Get latest item quantities
            $allReceived = $po->items->every(fn($item) => $item->qty_received >= $item->qty - 0.0001);
            $someReceived = $po->items->some(fn($item) => $item->qty_received > 0);
            
            if ($allReceived) {
                $po->status = PurchaseOrder::STATUS_RECEIVED;
            } elseif ($someReceived) {
                $po->status = PurchaseOrder::STATUS_PARTIAL;
            }
            $po->save();
        }

        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_RECEIVED => 'blue',
            self::STATUS_INSPECTED => 'amber',
            self::STATUS_COMPLETED => 'emerald',
            default => 'slate',
        };
    }
}
