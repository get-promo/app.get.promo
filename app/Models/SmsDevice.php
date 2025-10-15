<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SmsDevice extends Model
{
    protected $fillable = [
        'device_id',
        'api_key',
        'phone_number',
        'device_name',
        'is_active',
        'last_seen_at',
        'device_info',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'device_info' => 'array',
    ];

    /**
     * Relacja z wiadomościami SMS
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'device_id', 'device_id');
    }

    /**
     * Generuj unikalny device_id
     */
    public static function generateDeviceId(): string
    {
        return 'dev_' . Str::random(16);
    }

    /**
     * Generuj unikalny API key
     */
    public static function generateApiKey(): string
    {
        return 'sms_' . Str::random(32);
    }

    /**
     * Scope dla aktywnych urządzeń
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Aktualizuj czas ostatniego kontaktu
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
