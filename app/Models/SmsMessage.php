<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'message_id',
        'device_id',
        'from_number',
        'to_number',
        'content',
        'type',
        'status',
        'sent_at',
        'delivered_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relacja z urządzeniem SMS
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(SmsDevice::class, 'device_id', 'device_id');
    }

    /**
     * Generuj unikalny message_id
     */
    public static function generateMessageId(): string
    {
        return 'msg_' . uniqid() . '_' . time();
    }

    /**
     * Scope dla wiadomości oczekujących
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope dla wiadomości wysłanych
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope dla wiadomości otrzymanych
     */
    public function scopeReceived($query)
    {
        return $query->where('type', 'received');
    }
}
