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
            'description' => 'Jak klienci postrzegają profil — czy wygląda wiarygodnie i profesjonalnie.',
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
            'description' => 'Czy opis i oferta jasno pokazują, czym się zajmujesz i co klient zyskuje.',
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
            'description' => 'Czy profil wygląda na aktualny i pokazuje, że firma faktycznie działa.',
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
            'description' => 'Czy dane są kompletne, aktualne i spójne między wszystkimi elementami.',
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
            return 'Wymaga poprawy';
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
            return 'Twój profil nie wygląda wiarygodnie. Brakuje elementów, które budują zaufanie — przez to klienci mogą nie być pewni, czy warto się skontaktować. To poważny sygnał ostrzegawczy.';
        } elseif ($score < 4.0) {
            return 'Profil sprawia przeciętne wrażenie i nie budzi pełnego zaufania. Klienci mogą czuć niepewność lub zrezygnować przed kontaktem. Ten obszar zdecydowanie wymaga poprawy.';
        } else {
            return 'Twój profil wygląda wiarygodnie i budzi zaufanie. Klienci czują, że mają do czynienia z solidną, sprawdzoną firmą.';
        }
    }
    
    /**
     * Insight dla Dopasowania
     */
    private static function getDopasowanieInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil nie pokazuje jasno, czym zajmuje się Twoja firma. Klienci mogą mieć wątpliwości, czy oferta jest dla nich i szybko przejść do konkurencji.';
        } elseif ($score < 4.0) {
            return 'Opis oferty jest niepełny lub zbyt ogólny. Klient nie zawsze rozumie, czym dokładnie się zajmujesz. Ten obszar wymaga dopracowania, by uniknąć utraty klientów.';
        } else {
            return 'Oferta jest opisana jasno i konkretnie. Klienci od razu rozumieją, czym się zajmujesz i co możesz im zaoferować.';
        }
    }
    
    /**
     * Insight dla Aktywności
     */
    private static function getAktywnoscInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Twój profil sprawia wrażenie nieaktualnego. Brakuje oznak, że firma działa. To może powodować utratę zaufania i kontaktów.';
        } elseif ($score < 4.0) {
            return 'Profil wygląda na rzadko aktualizowany. Klienci mogą mieć wątpliwości, czy firma jest aktywna. To sygnał, że warto przywrócić profilowi życie.';
        } else {
            return 'Twój profil wygląda świeżo i aktywnie. Klienci widzą, że firma funkcjonuje na bieżąco.';
        }
    }
    
    /**
     * Insight dla Prezentacji
     */
    private static function getPrezentacjaInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'Profil wygląda nieatrakcyjnie i nie zachęca do kontaktu. Brakuje wrażenia profesjonalizmu, przez co klienci mogą go pominąć.';
        } elseif ($score < 4.0) {
            return 'Profil wygląda przeciętnie i nie przyciąga uwagi. To może powodować, że klienci szybciej wybierają inne firmy. Warto go odświeżyć.';
        } else {
            return 'Profil prezentuje się estetycznie i profesjonalnie. Klient ma dobre pierwsze wrażenie.';
        }
    }
    
    /**
     * Insight dla Spójności
     */
    private static function getSpojnoscInsight(float $score): string
    {
        if ($score < 3.0) {
            return 'W profilu brakuje ważnych informacji o firmie lub dane są niespójne. Klienci mogą mieć trudność z kontaktem lub nie mieć pewności, czy firma działa.';
        } elseif ($score < 4.0) {
            return 'Część informacji w profilu może być niepełna lub nieaktualna. Klienci mogą się wahać, zanim podejmą kontakt. To obszar, który warto uporządkować.';
        } else {
            return 'Dane Twojej firmy są kompletne i spójne. Klienci łatwo znajdują wszystkie potrzebne informacje.';
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
            } elseif ($pillar['status'] === 'Wymaga poprawy') {
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

