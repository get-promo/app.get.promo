<?php

namespace App\Services\Report;

/**
 * Scorer dla odpowiedzi właściciela
 * Używany w filarach: Zaufanie (30%), Aktywność (20%)
 */
class OwnerRepliesScorer
{
    /**
     * Oblicz score dla odpowiedzi właściciela (0.0-5.0)
     * 
     * @param float|null $replyRatePct Procent opinii z odpowiedzią (0-100)
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?float $replyRatePct): array
    {
        if ($replyRatePct === null) {
            return [
                'score' => 1.0,
                'note' => 'Brak danych o odpowiedziach'
            ];
        }

        if ($replyRatePct === 0) {
            $score = 1.0;
            $note = 'Brak odpowiedzi właściciela';
        } elseif ($replyRatePct < 30) {
            $score = 2.0;
            $note = 'Rzadkie odpowiedzi (< 30%)';
        } elseif ($replyRatePct < 50) {
            $score = 3.0;
            $note = 'Częściowe odpowiedzi (30-49%)';
        } else {
            $score = 4.5;
            $note = 'Regularne odpowiedzi (≥50%)';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

