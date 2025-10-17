<?php

namespace App\Services\Report;

/**
 * Scorer dla świeżości postów
 * Używany w filarze: Aktywność (40%)
 */
class PostsRecencyScorer
{
    /**
     * Oblicz score dla świeżości postów (0.0-5.0)
     * 
     * @param int|string|null $postsCount Liczba postów w ostatnich 30 dniach lub 'unknown'
     * @return array ['score' => float, 'note' => string, 'unknown' => bool]
     */
    public static function calculate($postsCount): array
    {
        if ($postsCount === 'unknown' || $postsCount === null) {
            return [
                'score' => 0,
                'note' => 'Brak danych API dla postów',
                'unknown' => true
            ];
        }

        if ($postsCount === 0) {
            $score = 1.0;
            $note = 'Brak postów w ostatnich 30 dniach';
        } elseif ($postsCount === 1) {
            $score = 2.5;
            $note = '1 post w ostatnich 30 dniach (rzadko)';
        } elseif ($postsCount === 2) {
            $score = 3.5;
            $note = '2 posty w ostatnich 30 dniach (co ~2 tygodnie)';
        } elseif ($postsCount >= 3 && $postsCount <= 4) {
            $score = 4.8;
            $note = "{$postsCount} posty w ostatnich 30 dniach (idealnie - co 7-10 dni)";
        } else {
            $score = 5.0;
            $note = "{$postsCount}+ postów w ostatnich 30 dniach (bardzo aktywnie)";
        }

        return [
            'score' => $score,
            'note' => $note,
            'unknown' => false
        ];
    }
}

