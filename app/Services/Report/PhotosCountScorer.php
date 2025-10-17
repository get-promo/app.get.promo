<?php

namespace App\Services\Report;

/**
 * Scorer dla liczby zdjęć
 * Używany w filarze: Prezentacja (20%)
 */
class PhotosCountScorer
{
    /**
     * Oblicz score dla liczby zdjęć (0.0-5.0)
     * 
     * @param int|null $photosCount Liczba zdjęć
     * @return array ['score' => float, 'note' => string]
     */
    public static function calculate(?int $photosCount): array
    {
        if ($photosCount === null) {
            $photosCount = 0;
        }

        if ($photosCount === 0) {
            $score = 0.5;
            $note = 'Brak zdjęć';
        } elseif ($photosCount <= 3) {
            $score = 2.0;
            $note = '1-3 zdjęcia (minimalna galeria)';
        } elseif ($photosCount <= 10) {
            $score = 3.5;
            $note = '4-10 zdjęć (przyzwoita galeria)';
        } elseif ($photosCount <= 20) {
            $score = 4.2;
            $note = '11-20 zdjęć (dobra galeria)';
        } elseif ($photosCount <= 50) {
            $score = 4.7;
            $note = '21-50 zdjęć (bogata galeria)';
        } else {
            $score = 5.0;
            $note = '50+ zdjęć (bardzo bogata galeria)';
        }

        return [
            'score' => $score,
            'note' => $note
        ];
    }
}

