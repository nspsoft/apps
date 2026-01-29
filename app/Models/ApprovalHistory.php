<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'step_order',
        'action',
        'acted_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    /**
     * Get parent approval request
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Get user who acted
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    /**
     * Get action badge color
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'approved' => 'green',
            'rejected' => 'red',
            'escalated' => 'yellow',
            default => 'gray',
        };
    }
}
