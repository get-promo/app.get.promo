<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phrase extends Model
{
    protected $fillable = [
        'lead_id',
        'phrase',
    ];

    /**
     * Relacja do leada
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
