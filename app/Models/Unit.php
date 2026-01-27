<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Unit extends Model
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
        'code',
        'name',
        'symbol',
        'conversion_factor',
        'base_unit_id',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Convert quantity to base unit
     */
    public function toBaseUnit(float $qty): float
    {
        return $qty * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit
     */
    public function fromBaseUnit(float $qty): float
    {
        return $qty / $this->conversion_factor;
    }
}
