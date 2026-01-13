<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_type',
        'search_query',
        'source',
        'place_title',
        'place_address',
        'place_cid',
        'place_data',
        'phone_number',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    protected $casts = [
        'place_data' => 'array',
    ];

    /**
     * Scope dla konkretnego typu akcji
     */
    public function scopeActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope dla sesji
     */
    public function scopeSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}

