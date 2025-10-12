<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSetting extends Model
{
    protected $fillable = [
        'weight_opinions',
        'weight_photos',
        'weight_description',
        'weight_categories',
        'weight_products_services',
        'weight_posts',
        'weight_nap',
        'weight_hours_url',
        'weight_owner_replies',
        'google_places_api_key',
        'enable_cache',
        'cache_ttl_hours',
    ];

    protected $casts = [
        'enable_cache' => 'boolean',
    ];

    /**
     * Pobierz singleton ustawień
     */
    public static function get(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'weight_opinions' => 25,
                'weight_photos' => 15,
                'weight_description' => 10,
                'weight_categories' => 10,
                'weight_products_services' => 10,
                'weight_posts' => 10,
                'weight_nap' => 0,
                'weight_hours_url' => 15,
                'weight_owner_replies' => 5,
                'enable_cache' => true,
                'cache_ttl_hours' => 24,
            ]
        );
    }

    /**
     * Pobierz wagi jako array
     */
    public function getWeightsArray(): array
    {
        return [
            'opinions' => $this->weight_opinions,
            'photos' => $this->weight_photos,
            'description' => $this->weight_description,
            'categories' => $this->weight_categories,
            'products_services' => $this->weight_products_services,
            'posts' => $this->weight_posts,
            'nap' => $this->weight_nap,
            'hours_url' => $this->weight_hours_url,
            'owner_replies' => $this->weight_owner_replies,
        ];
    }
}
