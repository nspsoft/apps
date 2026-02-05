<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'company_id',
        'title',
        'category',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'expires_at',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'file_size' => 'integer',
    ];

    // Category constants
    const CATEGORY_CERTIFICATE = 'certificate';
    const CATEGORY_CATALOG = 'catalog';
    const CATEGORY_CONTRACT = 'contract';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_OTHER = 'other';

    public static function categories(): array
    {
        return [
            self::CATEGORY_CERTIFICATE => 'Certificate',
            self::CATEGORY_CATALOG => 'Catalog',
            self::CATEGORY_CONTRACT => 'Contract',
            self::CATEGORY_COMPLIANCE => 'Compliance',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if document is expiring soon (within 30 days)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at && $this->expires_at->isBetween(now(), now()->addDays(30));
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Get category badge color
     */
    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_CERTIFICATE => 'emerald',
            self::CATEGORY_CATALOG => 'blue',
            self::CATEGORY_CONTRACT => 'purple',
            self::CATEGORY_COMPLIANCE => 'amber',
            default => 'slate',
        };
    }
}
