<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'customer_id',
        'direction',
        'message',
        'intent',
        'metadata',
        'fonnte_message_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the customer that owns the message
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope for incoming messages
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    /**
     * Scope for outgoing messages
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    /**
     * Get messages by phone number
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }
}
