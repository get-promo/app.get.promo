<?php

namespace App\Services\Report;

/**
 * Transformer: INTERNAL → PUBLIC
 * 
 * Przekształca techniczne dane raportu (score, metryki, progi)
 * w miękką, percepcyjną prezentację dla klienta.
 * 
 * NIE ZMIENIA algorytmu obliczeniowego - tylko sposób prezentacji.
 */
class ReportPublicTransformer
{
    /**
     * Transformuj techniczny raport na format PUBLIC
     * 
     * @param array $internalData Dane techniczne z Report model
     * @return array Format PUBLIC JSON
     */
    public static function transform(array $internalData): array
    {
        $components = $internalData['components'] ?? [];
        $query = $internalData['query'] ?? '';
        
        // Oceń status każdego filaru
        $pillars = [
            self::evaluateZaufanie($components),
            self::evaluateDopasowanie($components),
            self::evaluateAktywnosc($components),
            self::evaluatePrezentacja($components),
            self::evaluateSpojosc($components),
        ];
        
        // Określ badge na podstawie statusów
        $badge = self::determineBadge($pillars);
        
        // Dobierz moduły na podstawie słabych punktów
        $modules = self::recommendModules($pillars);
        
        return [
            'header' => [
                'title' => 'Przegląd profilu Google Business',
                'subtitle' => "Zapytanie: «{$query}»",
                'badge' => $badge,
            ],
            'pillars' => $pillars,
            'recommended_modules' => $modules,
            'cta' => 'Zaproponujemy układ modułów i harmonogram działań na krótkim callu (15 min).',
        ];
    }
    
    /**
     * Oceń filar: Zaufanie
     * Mapuje: opinions + owner_replies + hours_url
     */
    private static function evaluateZaufanie(array $components): array
    {
        $scores = [];
        
        if (isset($components['opinions']) && !($components['opinions']['unknown'] ?? false)) {
            $scores[] = $components['opinions']['score'];
        }
        
        if (isset($components['owner_replies']) && !($components['owner_replies']['unknown'] ?? false)) {
            $scores[] = $components['owner_replies']['score'];
        }
        
        if (isset($components['hours_url']) && !($components['hours_url']['unknown'] ?? false)) {
            $scores[] = $components['hours_url']['score'];
        }
        
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
        
        return [
            'name' => 'Zaufanie',
            'description' => 'Jak klienci odbierają Twój profil — czy wygląda wiarygodnie i profesjonalnie.',
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getZaufanieInsight($avgScore),
        ];
    }
    
    /**
     * Oceń filar: Dopasowanie
     * Mapuje: categories
     */
    private static function evaluateDopasowanie(array $components): array
    {
        $score = 0;
        
        if (isset($components['categories']) && !($components['categories']['unknown'] ?? false)) {
            $score = $components['categories']['score'];
        }
        
        return [
            'name' => 'Dopasowanie',
            'description' => 'Czy opis i oferta jasno pokazują, czym się zajmujesz i do kogo kierujesz swoją usługę.',
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getDopasowanieInsight($score),
        ];
    }
    
    /**
     * Oceń filar: Aktywność
     * Mapuje: posts + photos (freshness)
     */
    private static function evaluateAktywnosc(array $components): array
    {
        $scores = [];
        
        if (isset($components['posts']) && !($components['posts']['unknown'] ?? false)) {
            $scores[] = $components['posts']['score'];
        }
        
        if (isset($components['photos']) && !($components['photos']['unknown'] ?? false)) {
            $scores[] = $components['photos']['score'];
        }
        
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
        
        return [
            'name' => 'Aktywność',
            'description' => 'Czy profil wygląda na aktualny i aktywny.',
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getAktywnoscInsight($avgScore),
        ];
    }
    
    /**
     * Oceń filar: Prezentacja
     * Mapuje: photos + description
     */
    private static function evaluatePrezentacja(array $components): array
    {
        $scores = [];
        
        if (isset($components['photos']) && !($components['photos']['unknown'] ?? false)) {
            $scores[] = $components['photos']['score'];
        }
        
        if (isset($components['description']) && !($components['description']['unknown'] ?? false)) {
            $scores[] = $components['description']['score'];
        }
        
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
        
        return [
            'name' => 'Prezentacja',
            'description' => 'Jak Twój profil wygląda wizualnie i czy zachęca do kontaktu.',
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getPrezentacjaInsight($avgScore),
        ];
    }
    
    /**
     * Oceń filar: Spójność
     * Mapuje: hours_url + nap
     */
    private static function evaluateSpojosc(array $components): array
    {
        $scores = [];
        
        if (isset($components['hours_url']) && !($components['hours_url']['unknown'] ?? false)) {
            $scores[] = $components['hours_url']['score'];
        }
        
        if (isset($components['nap']) && !($components['nap']['unknown'] ?? false)) {
            $scores[] = $components['nap']['score'];
        }
        
        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;
        
        return [
            'name' => 'Spójność',
            'description' => 'Czy wszystkie dane są kompletne, aktualne i łatwe do znalezienia.',
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getSpojnoscInsight($avgScore),
        ];
    }
    
    /**
     * Określ status na podstawie średniego score
     */
    private static function getStatus(float $score): string
    {
        if ($score < 3.0) {
            return 'Słabo';
        } elseif ($score < 4.0) {
            return 'Wymaga uwagi';
        } else {
            return 'Dobra kondycja';
        }
    }
    
    /**
     * Określ kolor na podstawie score
     */
    private static function getColor(float $score): string
    {
        if ($score < 3.0) {
            return '#f35023';
        } elseif ($score < 4.0) {
            return '#ffb900';
        } else {
            return '#7eba01';
        }
    }
    
    /**
     * Insight dla Zaufania
     */
    private static function getZaufanieInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Twój profil nie wygląda jeszcze w pełni wiarygodnie. Część informacji może zniechęcać klientów lub wzbudzać wątpliwości. Warto się temu przyjrzeć, bo to wpływa na decyzję o kontakcie.';
        } elseif ($score < 4.0) {
            return 'Profil jest w porządku, ale można poprawić kilka elementów, które pomogą klientom zaufać szybciej. Drobne rzeczy potrafią zrobić duże wrażenie.';
        } else {
            return 'Twój profil wygląda wiarygodnie i budzi zaufanie. Klienci zyskują wrażenie, że mają do czynienia z rzetelną firmą.';
        }
    }
    
    /**
     * Insight dla Dopasowania
     */
    private static function getDopasowanieInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil nie do końca pokazuje, czym zajmuje się Twoja firma. Klient może mieć trudność z rozpoznaniem, czy oferta jest dla niego.';
        } elseif ($score < 4.0) {
            return 'Część informacji może być nie do końca jasna. Warto doprecyzować przekaz, by klient szybciej zrozumiał, czym się zajmujesz.';
        } else {
            return 'Twoja oferta jest dobrze pokazana. Klient od razu wie, czym się zajmujesz i łatwo ocenia, że oferta pasuje do jego potrzeb.';
        }
    }
    
    /**
     * Insight dla Aktywności
     */
    private static function getAktywnoscInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil sprawia wrażenie nieaktualnego. Klienci mogą odnieść wrażenie, że firma nie działa lub trudno się z nią skontaktować.';
        } elseif ($score < 4.0) {
            return 'Twój profil wygląda poprawnie, ale widać, że był aktualizowany jakiś czas temu. Dla klientów to może być sygnał, że firma działa rzadziej.';
        } else {
            return 'Profil wygląda świeżo i aktywnie. Klienci widzą, że firma działa i można na nią liczyć.';
        }
    }
    
    /**
     * Insight dla Prezentacji
     */
    private static function getPrezentacjaInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil wygląda surowo i może nie zachęcać klientów. Brakuje mu spójnego stylu i charakteru, który przyciąga uwagę.';
        } elseif ($score < 4.0) {
            return 'Profil jest poprawny, ale wygląda przeciętnie. Warto go dopracować, by był bardziej atrakcyjny wizualnie.';
        } else {
            return 'Profil prezentuje się estetycznie i profesjonalnie. Klient ma pozytywne pierwsze wrażenie.';
        }
    }
    
    /**
     * Insight dla Spójności
     */
    private static function getSpojnoscInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Niektóre dane o firmie są niepełne lub nieaktualne. Klient może mieć trudność z kontaktem lub sprawdzeniem szczegółów.';
        } elseif ($score < 4.0) {
            return 'Wszystkie najważniejsze informacje są obecne, ale warto sprawdzić, czy są aktualne i jednolite. Spójność ułatwia zaufanie.';
        } else {
            return 'Dane Twojej firmy są kompletne i spójne. Klient bez problemu znajdzie to, czego potrzebuje.';
        }
    }
    
    /**
     * Określ badge na podstawie statusów filarów
     */
    private static function determineBadge(array $pillars): string
    {
        $slabCount = 0;
        $wymagaCount = 0;
        $dobraCount = 0;
        
        foreach ($pillars as $pillar) {
            if ($pillar['status'] === 'Słabo') {
                $slabCount++;
            } elseif ($pillar['status'] === 'Wymaga uwagi') {
                $wymagaCount++;
            } else {
                $dobraCount++;
            }
        }
        
        if ($slabCount >= 2) {
            return 'Priorytet stabilizacji';
        } elseif ($wymagaCount >= 3 && $slabCount === 0) {
            return 'Profil do wzmocnienia w kluczowych obszarach';
        } elseif ($dobraCount >= 3) {
            return 'Profil stabilny — potencjał do skalowania';
        } else {
            return 'Profil do wzmocnienia w kluczowych obszarach';
        }
    }
    
    /**
     * Rekomenduj moduły na podstawie słabych filarów
     */
    private static function recommendModules(array $pillars): array
    {
        $modules = [];
        $modulePool = [
            'Zaufanie' => ['Reputacja+', 'Spójność'],
            'Dopasowanie' => ['Semantyka'],
            'Aktywność' => ['Puls Marki'],
            'Prezentacja' => ['Visual Story'],
            'Spójność' => ['Spójność'],
        ];
        
        foreach ($pillars as $pillar) {
            // Jeśli filar nie ma statusu "Dobra kondycja"
            if ($pillar['status'] !== 'Dobra kondycja') {
                $pillarName = $pillar['name'];
                if (isset($modulePool[$pillarName])) {
                    foreach ($modulePool[$pillarName] as $module) {
                        if (!in_array($module, $modules)) {
                            $modules[] = $module;
                        }
                    }
                }
            }
        }
        
        // Ogranicz do 2-3 modułów
        return array_slice(array_unique($modules), 0, 3);
    }
}

