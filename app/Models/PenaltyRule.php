<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyRule extends Model
{
    protected $table = 'hr_penalty_rules';

    protected $fillable = [
        'name',
        'type',
        'rounding_minutes',
        'deduction_basis',
        'fixed_amount',
        'grace_period_minutes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rounding_minutes' => 'integer',
        'grace_period_minutes' => 'integer',
        'fixed_amount' => 'double',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getActiveRule(string $type): ?self
    {
        return self::where('type', $type)->where('is_active', true)->first();
    }

    /**
     * Calculate penalty minutes from actual late/early minutes.
     * e.g., 10 mins late with 30-min rounding -> 30 mins penalty
     *       42 mins late with 30-min rounding -> 60 mins penalty
     */
    public function calculatePenaltyMinutes(int $actualMinutes): int
    {
        if ($actualMinutes <= $this->grace_period_minutes || $actualMinutes <= 0) {
            return 0;
        }

        $rounding = $this->rounding_minutes > 0 ? $this->rounding_minutes : 1;
        return (int) (ceil($actualMinutes / $rounding) * $rounding);
    }

    /**
     * Calculate monetary deduction from penalty minutes and employee hourly rate.
     */
    public function calculateDeduction(int $penaltyMinutes, float $hourlyRate): float
    {
        if ($penaltyMinutes <= 0) {
            return 0.0;
        }

        if ($this->deduction_basis === 'fixed_amount' && $this->fixed_amount > 0) {
            $rounding = $this->rounding_minutes > 0 ? $this->rounding_minutes : 30;
            $units = $penaltyMinutes / $rounding;
            return round($units * $this->fixed_amount, 2);
        }

        // Default: hourly_rate basis (penalty_minutes / 60 * hourly_rate)
        return round(($penaltyMinutes / 60) * $hourlyRate, 2);
    }
}
