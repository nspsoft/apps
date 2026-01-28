<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeliveryOrder extends Model
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
        'do_number',
        'vehicle_id',
        'vehicle_number',
        'driver_name',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'delivery_date',
        'status',
        'shipping_name',
        'shipping_address',
        'shipping_method',
        'tracking_number',
        'notes',
        'prepared_by',
        'delivered_by',
        'delivered_at',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PICKING = 'picking';
    const STATUS_PACKED = 'packed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';

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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public static function generateDoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "DO-{$year}{$month}-";
        
        $last = static::where('do_number', 'like', "{$prefix}%")
            ->orderBy('do_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->do_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Complete delivery and update stock
     */
    public function complete(): void
    {
        foreach ($this->items as $item) {
            // Update SO item delivered qty
            $soItem = $item->salesOrderItem;
            $soItem->qty_delivered += $item->qty_delivered;
            $soItem->save();

            // Reduce product stock
            $stock = ProductStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->first();

            if ($stock) {
                $stock->adjustStock(
                    -$item->qty_delivered,
                    null,
                    StockMovement::TYPE_SO_DELIVERY,
                    $this,
                    "Delivery Order #{$this->do_number}"
                );
            }
        }

        // Update SO status
        $so = $this->salesOrder;
        $allDelivered = $so->items->every(fn($item) => $item->isFullyDelivered());
        $so->status = $allDelivered ? SalesOrder::STATUS_DELIVERED : SalesOrder::STATUS_PROCESSING;
        $so->save();

        $this->status = self::STATUS_DELIVERED;
        $this->delivered_at = now();
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_PICKING => 'amber',
            self::STATUS_PACKED => 'blue',
            self::STATUS_SHIPPED => 'purple',
            self::STATUS_DELIVERED => 'emerald',
            default => 'slate',
        };
    }
}
