<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCompetitor extends Model
{
    protected $fillable = [
        'report_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'position',
        'cid',
        'place_id',
        'position_score',
        'profile_quality_score',
        'places_data',
        'score_breakdown',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'places_data' => 'array',
        'score_breakdown' => 'array',
        'position_score' => 'decimal:1',
        'profile_quality_score' => 'decimal:1',
    ];

    /**
     * Relacja do raportu
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
