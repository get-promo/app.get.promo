<?php

namespace App\Services\Report;

/**
 * Scorer dla dopasowania kategorii
 * Używany w filarze: Prezentacja (20%)
 */
class CategoryFitScorer
{
    /**
     * Oblicz score dla dopasowania kategorii (0.0-5.0)
     * 
     * @param array $types Kategorie z Google Places
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(array $types): array
    {
        if (empty($types)) {
            return [
                'score' => 0.5,
                'note' => 'Brak kategorii'
            ];
        }

        $count = count($types);

        // Sprawdź czy są "śmieciowe" typy
        $junkTypes = ['establishment', 'point_of_interest', 'premise'];
        $hasOnlyJunk = count(array_intersect($types, $junkTypes)) === count($types);

        if ($hasOnlyJunk) {
            return [
                'score' => 0.5,
                'note' => 'Tylko ogólne kategorie (bez specjalizacji)'
            ];
        }

        // Oceń na podstawie liczby kategorii (bez junk types)
        $meaningfulTypes = array_diff($types, $junkTypes);
        $meaningfulCount = count($meaningfulTypes);

        if ($meaningfulCount === 0) {
            $score = 0.5;
            $note = 'Brak sensownych kategorii';
        } elseif ($meaningfulCount === 1) {
            $score = 2.5;
            $note = 'Tylko główna kategoria';
        } elseif ($meaningfulCount <= 3) {
            $score = 4.0;
            $note = 'Główna + 1-2 dodatkowe kategorie';
        } elseif ($meaningfulCount <= 5) {
            $score = 4.5;
            $note = 'Pełny zestaw kategorii (3-5)';
        } else {
            $score = 5.0;
            $note = 'Bardzo szczegółowy zestaw kategorii (6+)';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

