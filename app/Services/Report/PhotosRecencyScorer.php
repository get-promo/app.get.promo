<?php

namespace App\Services\Report;

/**
 * Scorer dla świeżości zdjęć
 * Używany w filarze: Aktywność (40%)
 */
class PhotosRecencyScorer
{
    /**
     * Oblicz score dla świeżości zdjęć (0.0-5.0)
     * 
     * @param int|null $lastPhotoEpochDays Liczba dni od ostatniego zdjęcia
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?int $lastPhotoEpochDays): array
    {
        if ($lastPhotoEpochDays === null) {
            return [
                'score' => 1.0,
                'note' => 'Brak danych o świeżości zdjęć'
            ];
        }

        if ($lastPhotoEpochDays <= 7) {
            $score = 5.0;
            $note = 'Bardzo świeże zdjęcia (≤7 dni)';
        } elseif ($lastPhotoEpochDays <= 14) {
            $score = 4.8;
            $note = 'Świeże zdjęcia (8-14 dni)';
        } elseif ($lastPhotoEpochDays <= 30) {
            $score = 4.5;
            $note = 'Aktualne zdjęcia (15-30 dni)';
        } elseif ($lastPhotoEpochDays <= 60) {
            $score = 3.5;
            $note = 'Średnio świeże zdjęcia (31-60 dni)';
        } elseif ($lastPhotoEpochDays <= 90) {
            $score = 3.0;
            $note = 'Starsze zdjęcia (61-90 dni)';
        } elseif ($lastPhotoEpochDays <= 180) {
            $score = 2.0;
            $note = 'Przestarzałe zdjęcia (91-180 dni)';
        } else {
            $score = 1.0;
            $note = 'Bardzo stare zdjęcia (>180 dni)';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

