<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentNumberService
{
    /**
     * Generate next document number
     * 
     * @param string $code Unique code for document type (e.g. 'sales_order')
     * @param array $params Optional parameters for dynamic placeholders (e.g. ['CUST_CODE' => 'ABC'])
     * @return string Generated number (e.g. 'SO/2026/01/0001')
     */
    public function generate(string $code, array $params = [], $date = null): string
    {
        return DB::transaction(function () use ($code, $params, $date) {
            $config = DocumentNumbering::where('code', $code)->lockForUpdate()->first();

            if (!$config) {
                // Fallback or throw error. For now, throw error to enforce configuration.
                throw new \Exception("Document numbering for '{$code}' not found.");
            }

            // Check reset period
            $this->checkResetPeriod($config, $date);

            // Increment number
            $config->current_number++;
            $config->save();

            // Format number
            return $this->format($config, null, $params, $date);
        });
    }

    /**
     * Preview next number without incrementing
     */
    public function preview(string $code, array $params = [], $date = null, ?int $overrideNumber = null): string
    {
        $config = DocumentNumbering::where('code', $code)->first();
        
        if (!$config) {
            return "NOT-CONFIGURED";
        }

        if ($overrideNumber !== null) {
            $nextNumber = $overrideNumber;
        } else {
            // Simulate next number
            $nextNumber = $config->current_number + 1;
            
            // Simulate checking reset (if reset needed, start from 1)
            if ($this->shouldReset($config, $date)) {
                $nextNumber = 1;
            }
        }

        return $this->format($config, $nextNumber, $params, $date);
    }

    protected function checkResetPeriod(DocumentNumbering $config, $date = null): void
    {
        $targetDate = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();

        if (!$config->last_reset_date) {
            $config->last_reset_date = $targetDate;
        }

        if ($this->shouldReset($config, $targetDate)) {
            $config->current_number = 0;
            $config->last_reset_date = $targetDate;
            // Note: We don't save here, saving happens after increment
        }
    }

    protected function shouldReset(DocumentNumbering $config, $date = null): bool
    {
        if ($config->reset_period === 'never' || !$config->last_reset_date) {
            return false;
        }

        $targetDate = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();
        $lastReset = Carbon::parse($config->last_reset_date);

        return match ($config->reset_period) {
            'daily' => !$targetDate->isSameDay($lastReset),
            'monthly' => !$targetDate->isSameMonth($lastReset) || !$targetDate->isSameYear($lastReset),
            'yearly' => !$targetDate->isSameYear($lastReset),
            default => false,
        };
    }

    protected function format(DocumentNumbering $config, ?int $number = null, array $params = [], $date = null): string
    {
        $num = $number ?? $config->current_number;
        $paddedNumber = str_pad($num, $config->padding, '0', STR_PAD_LEFT);
        $dt = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();
        
        // Supported placeholders: {PREFIX}, {Y}, {y}, {m}, {d}, {NUMBER}
        $replacements = [
            '{PREFIX}' => $config->prefix,
            '{Y}' => $dt->format('Y'),
            '{y}' => $dt->format('y'),
            '{m}' => $dt->format('m'),
            '{d}' => $dt->format('d'),
            '{NUMBER}' => $paddedNumber,
        ];
        
        // Merge dynamic params
        foreach ($params as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $config->format);
    }

    /**
     * Sync current number based on a manual input
     *
     * @param string $code
     * @param string $manualNumber
     * @param \Carbon\Carbon|string|null $date
     */
    public function sync(string $code, string $manualNumber, $date = null): void
    {
        DB::transaction(function () use ($code, $manualNumber, $date) {
            $config = DocumentNumbering::where('code', $code)->lockForUpdate()->first();
            if (!$config) return;

            $docDate = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : null;

            // If reset_period is not 'never', verify document date matches active counter period
            if ($config->reset_period !== 'never' && $config->last_reset_date) {
                $lastReset = Carbon::parse($config->last_reset_date);

                // If date was not explicitly provided, try to extract year/month from manualNumber
                if (!$docDate) {
                    if (preg_match('/\/(\d{2,4})\/(\d{2})\//', $manualNumber, $dateMatches)) {
                        $yearPart = (int) $dateMatches[1];
                        if ($yearPart < 100) {
                            $yearPart += 2000;
                        }
                        $monthPart = (int) $dateMatches[2];
                        try {
                            $docDate = Carbon::createFromDate($yearPart, $monthPart, 1);
                        } catch (\Exception $e) {
                            $docDate = null;
                        }
                    }
                }

                // If we know the document date, check if it belongs to current active period
                if ($docDate) {
                    $isCurrentPeriod = match ($config->reset_period) {
                        'daily' => $docDate->isSameDay($lastReset),
                        'monthly' => $docDate->isSameMonth($lastReset) && $docDate->isSameYear($lastReset),
                        'yearly' => $docDate->isSameYear($lastReset),
                        default => true,
                    };

                    // Document belongs to a different period (e.g. past month). Never touch active counter!
                    if (!$isCurrentPeriod) {
                        return;
                    }
                }
            }

            // Remove revision suffix if any (e.g. -R1, -R2) before extracting sequence digits
            $cleanNumber = preg_replace('/-R\d+$/i', '', trim($manualNumber));

            // Extract the sequence digits at the end
            if (preg_match('/(\d+)$/', $cleanNumber, $matches)) {
                $num = (int) $matches[1];
                if ($num > $config->current_number) {
                    $config->current_number = $num;
                    $config->save();
                }
            }
        });
    }
}
