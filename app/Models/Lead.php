<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'address',
        'latitude',
        'longitude',
        'rating',
        'rating_count',
        'price_level',
        'category',
        'phone_number',
        'website',
        'cid',
        'serper_response',
        'contact_first_name',
        'contact_last_name',
        'contact_position',
        'contact_phone',
        'contact_email',
        'status',
        'notes',
    ];

    protected $casts = [
        'serper_response' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating' => 'decimal:1',
        'rating_count' => 'integer',
    ];

    /**
     * Relacja do fraz
     */
    public function phrases(): HasMany
    {
        return $this->hasMany(Phrase::class);
    }

    /**
     * Relacja do raportów
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the full contact name.
     */
    public function getContactFullNameAttribute(): string
    {
        return trim("{$this->contact_first_name} {$this->contact_last_name}");
    }

    /**
     * Scope a query to only include leads with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search leads by title or address.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%")
              ->orWhere('contact_first_name', 'like', "%{$search}%")
              ->orWhere('contact_last_name', 'like', "%{$search}%")
              ->orWhere('contact_email', 'like', "%{$search}%");
        });
    }
}

