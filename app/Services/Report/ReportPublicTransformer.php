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
            self::evaluateRelewancja($components),
            self::evaluateObecnosc($components),
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
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getZaufanieInsight($avgScore),
        ];
    }
    
    /**
     * Oceń filar: Relewancja
     * Mapuje: categories
     */
    private static function evaluateRelewancja(array $components): array
    {
        $score = 0;
        
        if (isset($components['categories']) && !($components['categories']['unknown'] ?? false)) {
            $score = $components['categories']['score'];
        }
        
        return [
            'name' => 'Relewancja',
            'status' => self::getStatus($score),
            'color' => self::getColor($score),
            'insight' => self::getRelewancjaInsight($score),
        ];
    }
    
    /**
     * Oceń filar: Obecność
     * Mapuje: posts + photos (freshness)
     */
    private static function evaluateObecnosc(array $components): array
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
            'name' => 'Obecność',
            'status' => self::getStatus($avgScore),
            'color' => self::getColor($avgScore),
            'insight' => self::getObecnoscInsight($avgScore),
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
        if ($score < 2.5) {
            return '🔴 ryzyko';
        } elseif ($score < 4.0) {
            return '🟠 do wzmocnienia';
        } else {
            return '🟢 stabilnie';
        }
    }
    
    /**
     * Określ kolor na podstawie score
     */
    private static function getColor(float $score): string
    {
        if ($score < 2.5) {
            return '#f35023';
        } else {
            return '#ffb900';
        }
    }
    
    /**
     * Insight dla Zaufania
     */
    private static function getZaufanieInsight(float $score): string
    {
        if ($score < 2.5) {
            return 'Wybrane sygnały mogą osłabiać poczucie bezpieczeństwa wyboru.';
        } elseif ($score < 4.0) {
            return 'Percepcja opieki posprzedażowej nie jest w pełni odczuwalna.';
        } else {
            return 'Sygnały wiarygodności działają stabilnie.';
        }
    }
    
    /**
     * Insight dla Relewancji
     */
    private static function getRelewancjaInsight(float $score): string
    {
        if ($score < 2.5) {
            return 'Dopasowanie do intencji jest ograniczone — komunikat nie trafia szeroko.';
        } elseif ($score < 4.0) {
            return 'Dopasowanie tematyczne do intencji wyszukiwań jest częściowe.';
        } else {
            return 'Zakres tematyczny buduje trafne oczekiwania.';
        }
    }
    
    /**
     * Insight dla Obecności
     */
    private static function getObecnoscInsight(float $score): string
    {
        if ($score < 2.5) {
            return 'Rytm sygnałów nie buduje wrażenia bieżącej aktywności.';
        } elseif ($score < 4.0) {
            return 'Rytm sygnałów nie potwierdza bieżącej aktywności marki.';
        } else {
            return 'Odczuwalna, regularna obecność w punktach styku.';
        }
    }
    
    /**
     * Insight dla Prezentacji
     */
    private static function getPrezentacjaInsight(float $score): string
    {
        if ($score < 2.5) {
            return 'Warstwa wizualno-opisowa nie tworzy spójnej narracji o wartości oferty.';
        } elseif ($score < 4.0) {
            return 'Narracja wizualna i opis nie tworzą jeszcze kompletnej historii miejsca.';
        } else {
            return 'Warstwa prezentacji harmonijnie porządkuje oczekiwania odbiorcy.';
        }
    }
    
    /**
     * Insight dla Spójności
     */
    private static function getSpojnoscInsight(float $score): string
    {
        if ($score < 2.5) {
            return 'Nie wszystkie punkty wiarygodności składają się na pełny obraz kontaktu.';
        } elseif ($score < 4.0) {
            return 'Część elementów porządkowych wymaga ujednolicenia.';
        } else {
            return 'Kluczowe elementy porządkują proces decyzyjny bez tarć.';
        }
    }
    
    /**
     * Określ badge na podstawie statusów filarów
     */
    private static function determineBadge(array $pillars): string
    {
        $redCount = 0;
        $orangeCount = 0;
        $greenCount = 0;
        
        foreach ($pillars as $pillar) {
            if (strpos($pillar['status'], '🔴') !== false) {
                $redCount++;
            } elseif (strpos($pillar['status'], '🟠') !== false) {
                $orangeCount++;
            } else {
                $greenCount++;
            }
        }
        
        if ($redCount >= 2) {
            return 'Priorytet stabilizacji';
        } elseif ($orangeCount >= 3 && $redCount === 0) {
            return 'Profil do wzmocnienia w kluczowych obszarach';
        } elseif ($greenCount >= 3) {
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
            'Relewancja' => ['Semantyka'],
            'Obecność' => ['Puls Marki'],
            'Prezentacja' => ['Visual Story'],
            'Spójność' => ['Spójność'],
        ];
        
        foreach ($pillars as $pillar) {
            // Jeśli filar nie jest zielony
            if (strpos($pillar['status'], '🟢') === false) {
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

