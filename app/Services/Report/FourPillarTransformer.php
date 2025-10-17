<?php

namespace App\Services\Report;

/**
 * Transformer: NOWY MODEL - 4 FILARY
 * 
 * Filary:
 * 1. Pozycja (Position Score)
 * 2. Zaufanie (opinions 70% + owner_replies 30%)
 * 3. Aktywność (posts_recency 40% + photos_recency 40% + owner_replies 20%)
 * 4. Prezentacja (photos_count 20% + description_length 15% + description_ai 20% + hours_url 25% + category_fit 20%)
 */
class FourPillarTransformer
{
    /**
     * Transformuj dane raportu na 4 filary
     * 
     * @param array $data Dane z Report model
     * @return array Format dla widoku
     */
    public static function transform(array $data): array
    {
        $placesData = $data['places_data'] ?? [];
        $positionScore = $data['position_score'] ?? 0;
        $query = $data['search_query'] ?? '';
        
        // Oblicz składowe za pomocą nowych scorerów
        $components = self::calculateComponents($placesData, $positionScore);
        
        // Oblicz 4 filary
        $pillars = [
            self::calculatePozycja($components),
            self::calculateZaufanie($components),
            self::calculateAktywnosc($components),
            self::calculatePrezentacja($components),
        ];
        
        // Oblicz średnią globalną
        $scores = array_column($pillars, 'score');
        $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
        
        // Określ badge
        $badge = self::determineBadge($pillars);
        
        return [
            'header' => [
                'title' => 'Analiza profilu Google Business - Nowy model 4-filarowy',
                'subtitle' => "Zapytanie: «{$query}»",
                'badge' => $badge,
            ],
            'pillars' => $pillars,
            'global_score' => $avgScore,
            'components' => $components, // Szczegóły dla debugowania
        ];
    }
    
    /**
     * Oblicz wszystkie składowe używając scorerów
     */
    private static function calculateComponents(array $placesData, float $positionScore): array
    {
        return [
            'position' => ['score' => $positionScore],
            'opinions' => OpinionsScorer::calculate(
                $placesData['rating'] ?? null,
                $placesData['user_ratings_total'] ?? null
            ),
            'owner_replies' => OwnerRepliesScorer::calculate(
                $placesData['owner_reply_rate_pct'] ?? null
            ),
            'posts_recency' => PostsRecencyScorer::calculate(
                $placesData['posts_count_last_30d'] ?? 'unknown'
            ),
            'photos_recency' => PhotosRecencyScorer::calculate(
                $placesData['last_photo_epoch_days'] ?? null
            ),
            'photos_count' => PhotosCountScorer::calculate(
                $placesData['photos_count'] ?? null
            ),
            'description_length' => DescriptionLengthScorer::calculate(
                $placesData['description'] ?? null
            ),
            'description_ai' => DescriptionAIScorer::calculate(
                $placesData['description'] ?? null,
                $placesData['types'] ?? []
            ),
            'category_fit' => CategoryFitScorer::calculate(
                $placesData['types'] ?? []
            ),
            'hours_url' => HoursUrlScorer::calculate(
                $placesData['opening_hours_present'] ?? false,
                $placesData['website'] ?? null
            ),
        ];
    }
    
    /**
     * 1️⃣ Pozycja = Position Score (100%)
     */
    private static function calculatePozycja(array $components): array
    {
        $score = $components['position']['score'] ?? 0;
        
        return [
            'name' => 'Pozycja',
            'description' => 'Widoczność Twojej firmy w wynikach wyszukiwania Google Maps',
            'score' => round($score, 1),
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getPozycjaInsight($score),
        ];
    }
    
    /**
     * 2️⃣ Zaufanie = opinions 70% + owner_replies 30%
     */
    private static function calculateZaufanie(array $components): array
    {
        $opinionsScore = $components['opinions']['score'] ?? 0;
        $repliesScore = $components['owner_replies']['score'] ?? 0;
        
        $score = ($opinionsScore * 0.7) + ($repliesScore * 0.3);
        
        return [
            'name' => 'Zaufanie',
            'description' => 'Czy klienci ufają Twojej firmie na podstawie opinii i reakcji właściciela',
            'score' => round($score, 1),
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getZaufanieInsight($score),
            'breakdown' => [
                'opinions' => round($opinionsScore, 1) . ' (waga 70%)',
                'owner_replies' => round($repliesScore, 1) . ' (waga 30%)',
            ],
        ];
    }
    
    /**
     * 3️⃣ Aktywność = posts_recency 40% + photos_recency 40% + owner_replies 20%
     */
    private static function calculateAktywnosc(array $components): array
    {
        $postsScore = 0;
        $postsWeight = 0;
        
        // Posts - jeśli unknown, pomijamy w obliczeniach
        if (!($components['posts_recency']['unknown'] ?? false)) {
            $postsScore = $components['posts_recency']['score'];
            $postsWeight = 0.4;
        }
        
        $photosScore = $components['photos_recency']['score'] ?? 0;
        $repliesScore = $components['owner_replies']['score'] ?? 0;
        
        // Jeśli posty są unknown, przelicz wagi: photos 67%, replies 33%
        if ($postsWeight === 0) {
            $score = ($photosScore * 0.67) + ($repliesScore * 0.33);
            $breakdown = [
                'photos_recency' => round($photosScore, 1) . ' (waga 67%)',
                'owner_replies' => round($repliesScore, 1) . ' (waga 33%)',
                'posts_recency' => 'brak danych',
            ];
        } else {
            $score = ($postsScore * 0.4) + ($photosScore * 0.4) + ($repliesScore * 0.2);
            $breakdown = [
                'posts_recency' => round($postsScore, 1) . ' (waga 40%)',
                'photos_recency' => round($photosScore, 1) . ' (waga 40%)',
                'owner_replies' => round($repliesScore, 1) . ' (waga 20%)',
            ];
        }
        
        return [
            'name' => 'Aktywność',
            'description' => 'Czy profil wygląda na aktualny, żywy i regularnie aktualizowany',
            'score' => round($score, 1),
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getAktywnoscInsight($score),
            'breakdown' => $breakdown,
        ];
    }
    
    /**
     * 4️⃣ Prezentacja = photos_count 20% + description_length 15% + description_ai 20% + hours_url 25% + category_fit 20%
     */
    private static function calculatePrezentacja(array $components): array
    {
        $photosCountScore = $components['photos_count']['score'] ?? 0;
        $descLengthScore = $components['description_length']['score'] ?? 0;
        $descAiScore = $components['description_ai']['score'] ?? 0;
        $hoursUrlScore = $components['hours_url']['score'] ?? 0;
        $categoryFitScore = $components['category_fit']['score'] ?? 0;
        
        $score = ($photosCountScore * 0.20) +
                 ($descLengthScore * 0.15) +
                 ($descAiScore * 0.20) +
                 ($hoursUrlScore * 0.25) +
                 ($categoryFitScore * 0.20);
        
        return [
            'name' => 'Prezentacja',
            'description' => 'Jak atrakcyjnie, spójnie i profesjonalnie prezentuje się Twój profil',
            'score' => round($score, 1),
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getPrezentacjaInsight($score),
            'breakdown' => [
                'photos_count' => round($photosCountScore, 1) . ' (waga 20%)',
                'description_length' => round($descLengthScore, 1) . ' (waga 15%)',
                'description_ai' => round($descAiScore, 1) . ' (waga 20%)',
                'hours_url' => round($hoursUrlScore, 1) . ' (waga 25%)',
                'category_fit' => round($categoryFitScore, 1) . ' (waga 20%)',
            ],
        ];
    }
    
    /**
     * Określ status na podstawie score
     */
    private static function getStatus(float $score): string
    {
        if ($score < 3.0) {
            return '❌ Wymaga pilnej poprawy';
        } elseif ($score < 4.0) {
            return '⚠️ Częściowo zoptymalizowany';
        } else {
            return '✅ Bardzo dobra kondycja';
        }
    }
    
    /**
     * Określ kolor na podstawie score
     */
    private static function getColor(float $score): string
    {
        if ($score < 3.0) {
            return '#f35023'; // czerwony
        } elseif ($score < 4.0) {
            return '#ffb900'; // pomarańczowy
        } else {
            return '#7eba01'; // zielony
        }
    }
    
    /**
     * Insight dla Pozycji
     */
    private static function getPozycjaInsight(float $score): string
    {
        if ($score >= 4.5) {
            return 'Twoja firma jest w TOP 3! Doskonała widoczność w Google Maps.';
        } elseif ($score >= 4.0) {
            return 'Bardzo dobra pozycja w TOP 10. Klienci łatwo Cię znajdą.';
        } elseif ($score >= 3.0) {
            return 'Pozycja w pierwszej 20-30. Jest potencjał do poprawy widoczności.';
        } else {
            return 'Niska pozycja. Większość klientów nie zobaczy Twojej firmy w wynikach wyszukiwania.';
        }
    }
    
    /**
     * Insight dla Zaufania
     */
    private static function getZaufanieInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Klienci nie ufają profilowi. Niskie oceny lub brak reakcji właściciela to poważny problem.';
        } elseif ($score < 4.0) {
            return 'Profil budzi umiarkowane zaufanie. Warto popracować nad opiniami i reagowaniem na nie.';
        } else {
            return 'Twój profil budzi zaufanie! Klienci widzą dobre oceny i aktywną komunikację.';
        }
    }
    
    /**
     * Insight dla Aktywności
     */
    private static function getAktywnoscInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil wygląda na martwy. Brak świeżych zdjęć i postów odstrasza klientów.';
        } elseif ($score < 4.0) {
            return 'Profil wygląda na rzadko aktualizowany. Klienci mogą mieć wątpliwości czy firma jest aktywna.';
        } else {
            return 'Profil wygląda na żywy i aktualny! Klienci widzą że firma działa na bieżąco.';
        }
    }
    
    /**
     * Insight dla Prezentacji
     */
    private static function getPrezentacjaInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil prezentuje się słabo. Brakuje kluczowych elementów które przyciągają klientów.';
        } elseif ($score < 4.0) {
            return 'Profil wygląda przeciętnie. Jest miejsce na poprawę wizualną i opisową.';
        } else {
            return 'Profil prezentuje się profesjonalnie i atrakcyjnie! Klienci mają dobre pierwsze wrażenie.';
        }
    }
    
    /**
     * Określ badge na podstawie filarów
     */
    private static function determineBadge(array $pillars): string
    {
        $criticalCount = 0;
        $warningCount = 0;
        $goodCount = 0;
        
        foreach ($pillars as $pillar) {
            if ($pillar['score'] < 3.0) {
                $criticalCount++;
            } elseif ($pillar['score'] < 4.0) {
                $warningCount++;
            } else {
                $goodCount++;
            }
        }
        
        if ($criticalCount >= 2) {
            return '🔴 Profil wymaga pilnej interwencji';
        } elseif ($warningCount >= 3) {
            return '🟠 Profil do wzmocnienia w kluczowych obszarach';
        } elseif ($goodCount >= 3) {
            return '🟢 Profil w bardzo dobrej kondycji - gotowy do skalowania';
        } else {
            return '🟡 Profil częściowo zoptymalizowany';
        }
    }
}

