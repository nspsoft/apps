<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'rate',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default PPN rate
     */
    public static function getDefaultPPN(): ?self
    {
        return static::where('code', 'ppn')->where('is_active', true)->first();
    }

    /**
     * Get the default tax rate for new documents
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first()
            ?? static::getDefaultPPN();
    }

    /**
     * Get tax rate by code
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Get all active tax rates
     */
    public static function getActive()
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }
}
