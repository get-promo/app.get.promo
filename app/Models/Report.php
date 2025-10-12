<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = [
        'lead_id',
        'key',
        'business_name',
        'search_query',
        'position',
        'total_results',
        'position_score',
        'profile_quality_score',
        'places_data',
        'score_breakdown',
        'avg_competitor_position_score',
        'avg_competitor_quality_score',
        'competitors_count',
        'weights_snapshot',
        'generated_at',
        'views_count',
    ];

    protected $casts = [
        'places_data' => 'array',
        'score_breakdown' => 'array',
        'weights_snapshot' => 'array',
        'position_score' => 'decimal:1',
        'profile_quality_score' => 'decimal:1',
        'avg_competitor_position_score' => 'decimal:1',
        'avg_competitor_quality_score' => 'decimal:1',
        'generated_at' => 'datetime',
    ];

    /**
     * Relacja do leada
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relacja do konkurentów
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(ReportCompetitor::class);
    }

    /**
     * Generuj unikalny 32-znakowy klucz
     */
    public static function generateKey(): string
    {
        do {
            $key = '';
            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $length = 32;
            
            for ($i = 0; $i < $length; $i++) {
                $key .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while (self::where('key', $key)->exists());
        
        return $key;
    }

    /**
     * Inkrementuj licznik wyświetleń
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
