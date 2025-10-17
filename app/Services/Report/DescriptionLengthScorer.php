<?php

namespace App\Services\Report;

/**
 * Scorer dla długości opisu
 * Używany w filarze: Prezentacja (15%)
 */
class DescriptionLengthScorer
{
    /**
     * Oblicz score dla długości opisu (0.0-5.0)
     * 
     * @param string|null $description Tekst opisu
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?string $description): array
    {
        if ($description === null || trim($description) === '') {
            return [
                'score' => 0.5,
                'note' => 'Brak opisu firmy'
            ];
        }

        $length = mb_strlen($description);

        if ($length < 50) {
            $score = 2.0;
            $note = 'Krótki opis (< 50 znaków)';
        } elseif ($length < 150) {
            $score = 3.5;
            $note = 'Średni opis (50-149 znaków)';
        } elseif ($length < 300) {
            $score = 4.5;
            $note = 'Dobry opis (150-299 znaków)';
        } else {
            $score = 5.0;
            $note = 'Szczegółowy opis (300+ znaków)';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

