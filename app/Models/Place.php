<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'cid',
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
        'email',
        'email_checked',
        'email_checked_at',
        'email_source',
        'email_scan_data',
        'serper_response',
        'search_phrase',
        'city_name',
        'city_size',
    ];

    protected $casts = [
        'serper_response' => 'array',
        'email_scan_data' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating' => 'decimal:1',
        'rating_count' => 'integer',
        'email_checked' => 'boolean',
        'email_checked_at' => 'datetime',
    ];

    /**
     * Wyszukiwanie miejsc po tytule lub adresie
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('address', 'LIKE', "%{$search}%");
        });
    }
}

