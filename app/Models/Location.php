<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'parent_id',
        'code',
        'name',
        'type',
        'level',
        'path',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get full location path
     */
    public function getFullPathAttribute(): string
    {
        $path = $this->warehouse->name . ' / ' . $this->code;
        
        if ($this->parent) {
            $parent = $this->parent;
            $parentPath = [];
            
            while ($parent) {
                array_unshift($parentPath, $parent->code);
                $parent = $parent->parent;
            }
            
            if (!empty($parentPath)) {
                $path = $this->warehouse->name . ' / ' . implode(' / ', $parentPath) . ' / ' . $this->code;
            }
        }
        
        return $path;
    }
}
