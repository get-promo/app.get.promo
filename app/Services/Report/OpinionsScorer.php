<?php

namespace App\Services\Report;

/**
 * Scorer dla opinii (rating + liczba opinii)
 * Używany w filarze: Zaufanie (70%)
 */
class OpinionsScorer
{
    /**
     * Oblicz score dla opinii (0.0-5.0)
     * 
     * @param float|null $rating Średnia ocena (1.0-5.0)
     * @param int|null $ratingsTotal Liczba opinii
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?float $rating, ?int $ratingsTotal): array
    {
        if ($rating === null || $ratingsTotal === null) {
            return [
                'score' => 0.5,
                'note' => 'Brak opinii'
            ];
        }

        // Bazowy score z ratingu
        if ($rating < 3.5) {
            $score = 1.0;
            $note = 'Niska ocena (< 3.5)';
        } elseif ($rating < 4.0) {
            $score = 2.5;
            $note = 'Średnia ocena (3.5-3.9)';
        } elseif ($rating < 4.3) {
            $score = 3.5;
            $note = 'Dobra ocena (4.0-4.2)';
        } elseif ($rating < 4.6) {
            $score = 4.2;
            $note = 'Bardzo dobra ocena (4.3-4.5)';
        } else {
            $score = 4.8;
            $note = 'Doskonała ocena (≥4.6)';
        }

        // Booster liczności
        if ($ratingsTotal >= 100) {
            $score += 0.20;
            $note .= ' + 100+ opinii';
        } elseif ($ratingsTotal >= 50) {
            $score += 0.15;
            $note .= ' + 50+ opinii';
        } elseif ($ratingsTotal >= 30) {
            $score += 0.10;
            $note .= ' + 30+ opinii';
        } elseif ($ratingsTotal >= 10) {
            $score += 0.05;
            $note .= ' + 10+ opinii';
        } else {
            $note .= ' (mało opinii)';
        }

        return [
            'score' => min(5.0, $score),
            'note' => $note
        ];
    }
}

