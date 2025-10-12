<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReportGenerationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'job_id',
        'status',
        'progress_percentage',
        'current_step',
        'error_message',
        'report_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];

    /**
     * Relacja do leada
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relacja do raportu
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Generuje unikalny job_id
     */
    public static function generateJobId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Aktualizuj progress
     */
    public function updateProgress(int $percentage, string $step): void
    {
        $this->update([
            'progress_percentage' => $percentage,
            'current_step' => $step,
        ]);
    }

    /**
     * Oznacz jako rozpoczęty
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Oznacz jako zakończony
     */
    public function markAsCompleted(int $reportId): void
    {
        $this->update([
            'status' => 'completed',
            'report_id' => $reportId,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    /**
     * Oznacz jako nieudany
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
