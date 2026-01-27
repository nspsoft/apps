<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'qty_on_hand',
        'qty_reserved',
        'qty_incoming',
        'qty_outgoing',
        'avg_cost',
    ];

    protected $casts = [
        'qty_on_hand' => 'float',
        'qty_reserved' => 'float',
        'qty_incoming' => 'float',
        'qty_outgoing' => 'float',
        'avg_cost' => 'double',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get available quantity
     */
    public function getAvailableQtyAttribute(): float
    {
        return $this->qty_on_hand - $this->qty_reserved;
    }

    /**
     * Get projected quantity (including incoming/outgoing)
     */
    public function getProjectedQtyAttribute(): float
    {
        return $this->qty_on_hand + $this->qty_incoming - $this->qty_outgoing;
    }

    /**
     * Get stock value
     */
    public function getValueAttribute(): float
    {
        return $this->qty_on_hand * $this->avg_cost;
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(float $qty, float $cost = null, string $type = 'adjustment', Model $reference = null, string $notes = null, string $externalReference = null): void
    {
        $balanceBefore = $this->qty_on_hand;
        
        if ($cost !== null && $qty > 0) {
            // Update average cost on stock increase
            $totalValue = ($this->qty_on_hand * $this->avg_cost) + ($qty * $cost);
            $totalQty = $this->qty_on_hand + $qty;
            $this->avg_cost = $totalQty > 0 ? $totalValue / $totalQty : 0;
        }

        $this->qty_on_hand += $qty;
        $this->save();

        // Log movement
        StockMovement::create([
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'location_id' => $this->location_id,
            'qty' => $qty,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->qty_on_hand,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->id : null,
            'external_reference' => $externalReference,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Reserve stock
     */
    public function reserve(float $qty): bool
    {
        if ($this->available_qty >= $qty) {
            $this->qty_reserved += $qty;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Release reserved stock
     */
    public function releaseReservation(float $qty): void
    {
        $this->qty_reserved = max(0, $this->qty_reserved - $qty);
        $this->save();
    }
}
