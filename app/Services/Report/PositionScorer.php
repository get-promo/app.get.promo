<?php

namespace App\Services\Report;

class PositionScorer
{
    /**
     * Oblicz Position Score (0.0-5.0) na podstawie pozycji
     */
    public static function calculate(?int $position): float
    {
        if ($position === null || $position > 100) {
            return 1.0;
        }

        if ($position >= 1 && $position <= 3) {
            return 5.0;
        }

        if ($position >= 4 && $position <= 10) {
            return round(4.9 - ($position - 4) * (0.9 / 6), 1);
        }

        if ($position >= 11 && $position <= 30) {
            return round(3.9 - ($position - 11) * (0.7 / 19), 1);
        }

        if ($position >= 31 && $position <= 60) {
            return round(3.1 - ($position - 31) * (0.9 / 29), 1);
        }

        if ($position >= 61 && $position <= 100) {
            return round(2.1 - ($position - 61) * (0.7 / 39), 1);
        }

        return 1.0;
    }
}

