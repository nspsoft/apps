<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockReclassification extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'inv_stock_reclassifications';

    protected $fillable = [
        'reclass_number',
        'warehouse_id',
        'target_warehouse_id',
        'reclass_date',
        'status',
        'reason',
        'notes',
        'total_qty',
        'total_value',
        'total_sell_value',
        'total_profit_nominal',
        'total_profit_percentage',
        'reference_type',
        'reference_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'reclass_date' => 'date',
        'total_qty' => 'float',
        'total_value' => 'double',
        'total_sell_value' => 'double',
        'total_profit_nominal' => 'double',
        'total_profit_percentage' => 'double',
        'posted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReclassificationItem::class, 'stock_reclassification_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public static function generateUniqueNumber($date = null): string
    {
        $dt = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();
        $prefix = 'RCL/' . $dt->format('y/m') . '/';

        // Find the maximum sequence number for this prefix from database
        $existingNumbers = static::withTrashed()
            ->where('reclass_number', 'like', $prefix . '%')
            ->pluck('reclass_number')
            ->map(function ($num) use ($prefix) {
                $seq = str_replace($prefix, '', $num);
                return is_numeric($seq) ? (int) $seq : 0;
            })
            ->filter()
            ->values();

        $maxExisting = $existingNumbers->isNotEmpty() ? $existingNumbers->max() : 0;

        // Also check document_numberings table
        $docConfig = \App\Models\DocumentNumbering::where('code', 'stock_reclassification')->first();
        $currentConfigNum = $docConfig ? (int) $docConfig->current_number : 0;

        $nextNum = max($maxExisting, $currentConfigNum) + 1;
        $candidateNumber = $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

        while (static::withTrashed()->where('reclass_number', $candidateNumber)->exists()) {
            $nextNum++;
            $candidateNumber = $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        }

        if ($docConfig) {
            $docConfig->update([
                'current_number' => $nextNum,
                'last_reset_date' => $dt->toDateString(),
            ]);
        }

        return $candidateNumber;
    }

    public static function generateNumber($date = null): string
    {
        $dt = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();
        $prefix = 'RCL/' . $dt->format('y/m') . '/';

        $existingNumbers = static::withTrashed()
            ->where('reclass_number', 'like', $prefix . '%')
            ->pluck('reclass_number')
            ->map(function ($num) use ($prefix) {
                $seq = str_replace($prefix, '', $num);
                return is_numeric($seq) ? (int) $seq : 0;
            })
            ->filter()
            ->values();

        $maxExisting = $existingNumbers->isNotEmpty() ? $existingNumbers->max() : 0;

        $docConfig = \App\Models\DocumentNumbering::where('code', 'stock_reclassification')->first();
        $currentConfigNum = $docConfig ? (int) $docConfig->current_number : 0;

        $nextNum = max($maxExisting, $currentConfigNum) + 1;
        $candidateNumber = $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

        while (static::withTrashed()->where('reclass_number', $candidateNumber)->exists()) {
            $nextNum++;
            $candidateNumber = $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        }

        return $candidateNumber;
    }
}
