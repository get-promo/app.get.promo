<?php

namespace App\Services\Report;

/**
 * Scorer dla godzin otwarcia + strona WWW
 * Używany w filarze: Prezentacja (25%)
 */
class HoursUrlScorer
{
    /**
     * Oblicz score dla godzin i strony WWW (0.0-5.0)
     * 
     * @param bool $hasHours Czy ma ustawione godziny otwarcia
     * @param string|null $website URL strony internetowej
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(bool $hasHours, ?string $website): array
    {
        $hasWebsite = !empty($website);

        if (!$hasHours && !$hasWebsite) {
            $score = 1.0;
            $note = 'Brak godzin i strony www';
        } elseif ($hasHours && $hasWebsite) {
            $score = 4.5;
            $note = 'Pełne info: godziny + strona www';
        } elseif ($hasHours) {
            $score = 3.0;
            $note = 'Tylko godziny otwarcia';
        } else {
            $score = 3.0;
            $note = 'Tylko strona www';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

