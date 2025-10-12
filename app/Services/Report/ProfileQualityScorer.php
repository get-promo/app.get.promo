<?php

namespace App\Services\Report;

class ProfileQualityScorer
{
    /**
     * Oblicz Profile Quality Score na podstawie danych z Places
     * 
     * @param array $placesData Dane z Google Places
     * @param array $weights Wagi składowych
     * @return array ['score' => float, 'breakdown' => array]
     */
    public static function calculate(array $placesData, array $weights): array
    {
        $components = [];

        // 1. Opinie
        $components['opinions'] = self::opinionsComponent(
            $placesData['rating'] ?? null,
            $placesData['user_ratings_total'] ?? null
        );

        // 2. Zdjęcia
        $components['photos'] = self::photosComponent(
            $placesData['photos_count'] ?? null,
            $placesData['last_photo_epoch_days'] ?? null
        );

        // 3. Kategorie
        $components['categories'] = self::categoriesComponent(
            $placesData['types'] ?? []
        );

        // 4. Opis
        $components['description'] = self::descriptionComponent(
            $placesData['description'] ?? null
        );

        // 5. Produkty/Usługi - WYŁĄCZONE (nie wszystkie biznesy mają tę sekcję w Google Maps)
        $components['products_services'] = [
            'score' => 0,
            'note' => 'Produkty/Usługi nie są brane pod uwagę w ocenie',
            'unknown' => true
        ];

        // 6. Posty/Aktualizacje
        $components['posts'] = self::postsComponent(
            $placesData['posts_count_last_30d'] ?? 'unknown'
        );

        // 7. NAP - na razie wyłączone (waga 0)
        $components['nap'] = [
            'score' => 0,
            'note' => 'NAP nie jest jeszcze zaimplementowane',
            'unknown' => true
        ];

        // 8. Godziny + URL
        $components['hours_url'] = self::hoursUrlComponent(
            $placesData['opening_hours_present'] ?? false,
            $placesData['website'] ?? null
        );

        // 9. Odpowiedzi właściciela
        $components['owner_replies'] = self::repliesComponent(
            $placesData['owner_reply_rate_pct'] ?? null
        );

        // Filtruj komponenty z unknown
        $usableComponents = array_filter($components, function($component) {
            return !($component['unknown'] ?? false);
        });

        // Normalizuj wagi
        $normalizedWeights = self::normalizeWeights($usableComponents, $weights);

        // Oblicz finalny score
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($usableComponents as $key => $component) {
            $weight = $normalizedWeights[$key] ?? 0;
            $totalScore += $component['score'] * $weight;
            $totalWeight += $weight;
        }

        $finalScore = $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0.0;

        // Clamp do 0-5
        $finalScore = max(0.0, min(5.0, $finalScore));

        return [
            'score' => $finalScore,
            'breakdown' => $components,
        ];
    }

    /**
     * Składowa: Opinie
     */
    private static function opinionsComponent(?float $rating, ?int $ratingsTotal): array
    {
        if ($rating === null || $ratingsTotal === null) {
            return [
                'score' => 0.5,
                'note' => 'Brak opinii',
                'unknown' => false
            ];
        }

        // Bazowy score
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
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Zdjęcia
     */
    private static function photosComponent(?int $photosCount, ?int $lastPhotoEpochDays): array
    {
        if ($photosCount === null) {
            $photosCount = 0;
        }

        // Bazowy score z ilości
        if ($photosCount === 0) {
            $score = 0.5;
            $note = 'Brak zdjęć';
        } elseif ($photosCount <= 3) {
            $score = 2.0;
            $note = '1-3 zdjęcia';
        } elseif ($photosCount <= 10) {
            $score = 3.5;
            $note = '4-10 zdjęć';
        } elseif ($photosCount <= 20) {
            $score = 4.2;
            $note = '11-20 zdjęć';
        } else {
            $score = 4.7;
            $note = '20+ zdjęć';
        }

        // Booster świeżości (jeśli mamy dane)
        if ($lastPhotoEpochDays !== null && $photosCount > 0) {
            if ($lastPhotoEpochDays <= 30) {
                $score += 0.3;
                $note .= ' (świeże ≤30d)';
            } elseif ($lastPhotoEpochDays <= 90) {
                $score += 0.2;
                $note .= ' (≤90d)';
            } elseif ($lastPhotoEpochDays <= 180) {
                $score += 0.1;
                $note .= ' (≤180d)';
            }
        }

        return [
            'score' => min(5.0, $score),
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Kategorie
     */
    private static function categoriesComponent(array $types): array
    {
        if (empty($types)) {
            return [
                'score' => 0.5,
                'note' => 'Brak kategorii',
                'unknown' => false
            ];
        }

        $count = count($types);

        if ($count === 1) {
            $score = 2.5;
            $note = 'Tylko główna kategoria';
        } elseif ($count <= 3) {
            $score = 4.0;
            $note = 'Główna + 1-2 dodatkowe';
        } else {
            $score = 4.5;
            $note = 'Główna + 3+ dodatkowe';
        }

        // Sprawdź czy są "śmieciowe" typy
        $junkTypes = ['establishment', 'point_of_interest', 'premise'];
        $hasOnlyJunk = count(array_intersect($types, $junkTypes)) === count($types);

        if ($hasOnlyJunk) {
            $score = max(0.5, $score - 1.0);
            $note .= ' (ogólne)';
        }

        return [
            'score' => $score,
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Opis firmy
     */
    private static function descriptionComponent(?string $description): array
    {
        if ($description === null || trim($description) === '') {
            return [
                'score' => 0.5,
                'note' => 'Brak opisu firmy',
                'unknown' => false
            ];
        }

        $length = mb_strlen($description);

        if ($length < 50) {
            $score = 2.0;
            $note = 'Krótki opis (< 50 znaków)';
        } elseif ($length < 150) {
            $score = 3.5;
            $note = 'Średni opis (50-150 znaków)';
        } elseif ($length < 300) {
            $score = 4.5;
            $note = 'Dobry opis (150-300 znaków)';
        } else {
            $score = 5.0;
            $note = 'Szczegółowy opis (300+ znaków)';
        }

        return [
            'score' => $score,
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Produkty/Usługi
     */
    private static function productsServicesComponent(?int $productsCount, ?int $servicesCount): array
    {
        $total = ($productsCount ?? 0) + ($servicesCount ?? 0);

        if ($productsCount === null && $servicesCount === null) {
            return [
                'score' => 1.0,
                'note' => 'Brak danych o produktach/usługach',
                'unknown' => true
            ];
        }

        if ($total === 0) {
            $score = 1.0;
            $note = 'Brak produktów/usług';
        } else if ($total < 5) {
            $score = 3.0;
            $note = 'Lista bez szczegółów';
        } else {
            $score = 4.5;
            $note = 'Kompletna lista produktów/usług';
        }

        return [
            'score' => $score,
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Posty/Aktualizacje
     * Idealny lokal: post co 7-10 dni (3-4 posty/miesiąc)
     */
    private static function postsComponent($postsCount): array
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

    /**
     * Składowa: Godziny + URL
     */
    private static function hoursUrlComponent(bool $hasHours, ?string $website): array
    {
        $hasWebsite = !empty($website);

        if (!$hasHours && !$hasWebsite) {
            $score = 1.0;
            $note = 'Brak godzin i strony www';
        } elseif ($hasHours && $hasWebsite) {
            $score = 4.5;
            $note = 'Pełne info: godziny + strona www';
        } else {
            $score = 3.0;
            $note = $hasHours ? 'Tylko godziny' : 'Tylko strona www';
        }

        return [
            'score' => $score,
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Składowa: Odpowiedzi właściciela
     */
    private static function repliesComponent(?float $replyRatePct): array
    {
        if ($replyRatePct === null) {
            return [
                'score' => 1.0,
                'note' => 'Brak danych o odpowiedziach',
                'unknown' => false
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
            'note' => $note,
            'unknown' => false
        ];
    }

    /**
     * Normalizuj wagi dla używanych składowych
     */
    private static function normalizeWeights(array $usableComponents, array $weights): array
    {
        $normalized = [];
        $totalWeight = 0;

        foreach ($usableComponents as $key => $component) {
            $weight = $weights[$key] ?? 0;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            foreach ($usableComponents as $key => $component) {
                $weight = $weights[$key] ?? 0;
                $normalized[$key] = ($weight / $totalWeight) * 100;
            }
        }

        return $normalized;
    }
}

