<?php

namespace App\Services\Report;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrightDataService
{
    private string $apiKey;
    private string $zone;
    private bool $enableCache;
    private int $cacheTtl; // hours

    public function __construct()
    {
        $this->apiKey = config('services.brightdata.api_key');
        $this->zone = config('services.brightdata.zone', 'get_promo_web_unlocker1');
        $this->enableCache = true;
        $this->cacheTtl = 24; // 24 godziny cache
    }

    /**
     * Pobierz usługi i opis biznesu dla danego Place ID z Google Maps
     */
    public function getBusinessData(string $placeId): array
    {
        $cacheKey = "brightdata_business_{$placeId}";

        if ($this->enableCache && Cache::has($cacheKey)) {
            Log::info("BrightData cache hit for place_id={$placeId}");
            return Cache::get($cacheKey);
        }

        try {
            $googleMapsUrl = "https://www.google.com/maps/place/?q=place_id:{$placeId}&hl=pl";

            Log::info("BrightData: Fetching data for place_id={$placeId}");

            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.brightdata.com/request', [
                'zone' => $this->zone,
                'url' => $googleMapsUrl,
                'format' => 'raw'
            ]);

            if ($response->successful()) {
                $htmlContent = $response->body();

                // Wyciągnij APP_INITIALIZATION_STATE JSON
                if (preg_match('/APP_INITIALIZATION_STATE\s*=\s*(\[.*?\]);/', $htmlContent, $matches)) {
                    $jsonString = $matches[1];
                    $data = json_decode($jsonString, true);

                    if ($data) {
                        // Debug: Zapisz fragment surowych danych do logów
                        $jsonPreview = mb_substr($jsonString, 0, 5000);
                        Log::debug("BrightData RAW JSON preview for place_id={$placeId}", [
                            'json_length' => strlen($jsonString),
                            'preview' => $jsonPreview
                        ]);
                        
                        // Parsuj usługi i opis
                        $result = [
                            'services' => $this->extractServices($data),
                            'description' => $this->extractDescription($data),
                        ];

                        if ($this->enableCache) {
                            Cache::put($cacheKey, $result, now()->addHours($this->cacheTtl));
                        }

                        Log::info("BrightData: Successfully fetched data for place_id={$placeId}", [
                            'services_count' => count($result['services']),
                            'has_description' => !empty($result['description'])
                        ]);

                        return $result;
                    } else {
                        Log::error("BrightData: Failed to decode JSON for place_id={$placeId}");
                    }
                } else {
                    Log::error("BrightData: APP_INITIALIZATION_STATE not found for place_id={$placeId}");
                }
            } else {
                Log::error("BrightData API failed for place_id={$placeId}: HTTP " . $response->status());
            }

        } catch (\Exception $e) {
            Log::error("BrightData exception for place_id={$placeId}: " . $e->getMessage());
        }

        return [
            'services' => [],
            'description' => null,
        ];
    }

    /**
     * Wyciągnij usługi z JSON - uniwersalna metoda
     * Szuka wzorców charakterystycznych dla sekcji usług/produktów w Google Maps
     */
    private function extractServices(array $data): array
    {
        $services = [];
        
        // Konwertuj do JSON string
        $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // METODA 1: Szukaj wzorca par [["Nazwa usługi","Opis"]] w całym JSON
        // Ten wzorzec pojawia się w sekcjach usług/produktów/menu w Google Maps
        // Używamy różnych wariantów escapowania
        $patterns = [
            // Pattern 1: Podwójnie escapowane (najczęstsze w APP_INITIALIZATION_STATE)
            '/\[+\\\\"([^"]{3,100})\\\\",\\\\"([^"]{0,500})\\\\"\]+/u',
            // Pattern 2: Pojedynczo escapowane
            '/\[+\\"([^"]{3,100})\\",\\"([^"]{0,500})\\"\]+/u',
            // Pattern 3: Bez escapowania (dla pewności)
            '/\[+\"([^"]{3,100})\",\"([^"]{0,500})\"\]+/u',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $jsonStr, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $name = $match[1];
                    $description = $match[2] ?? '';
                    
                    // Filtruj wyniki
                    if ($this->isValidServiceEntry($name, $description)) {
                        $services[] = [
                            'name' => $name,
                            'description' => $description
                        ];
                    }
                }
                
                // Jeśli znaleźliśmy wyniki, nie szukaj dalej
                if (count($services) > 0) {
                    break;
                }
            }
        }
        
        // METODA 2: Jeśli nie znaleziono, spróbuj szukać w konkretnych sekcjach
        if (empty($services)) {
            $services = $this->extractServicesFromKnownSections($data);
        }
        
        // Deduplikacja
        $uniqueServices = [];
        $seen = [];
        foreach ($services as $service) {
            $key = $service['name'];
            if (!isset($seen[$key])) {
                $uniqueServices[] = $service;
                $seen[$key] = true;
            }
        }
        
        Log::info("BrightData: Extracted services", [
            'total_found' => count($services),
            'unique_count' => count($uniqueServices),
            'sample' => array_slice($uniqueServices, 0, 3)
        ]);
        
        return $uniqueServices;
    }
    
    /**
     * Sprawdź czy wpis jest prawdopodobnie usługą/produktem
     */
    private function isValidServiceEntry(string $name, string $description): bool
    {
        // Zbyt krótkie nazwy
        if (mb_strlen($name) < 3) {
            return false;
        }
        
        // Zbyt długie nazwy (prawdopodobnie nie są usługami)
        if (mb_strlen($name) > 100) {
            return false;
        }
        
        // Filtruj wpisy gdzie opis to URL (to są linki, nie usługi)
        if (!empty($description) && preg_match('/^https?:\/\//i', $description)) {
            return false;
        }
        
        // Filtruj oczywiste śmieci
        $junkPatterns = [
            '/^http/i',
            '/^www\./i',
            '/^https/i',
            '/^\d+$/',  // Same cyfry
            '/^[a-z]$/',  // Pojedyncze litery
            '/\.(jpg|png|gif|jpeg|webp)$/i',  // Pliki graficzne
            '/^(true|false|null)$/i',  // Boolean values
            '/^[\d\.,]+$/',  // Same cyfry i przecinki
        ];
        
        foreach ($junkPatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return false;
            }
        }
        
        // Filtruj typowe śmieciowe frazy z Google Maps (interfejs, nie usługi)
        $junkKeywords = [
            'APP_INITIALIZATION_STATE',
            'data:image',
            'function(',
            'window.',
            'document.',
            'undefined',
            'streetViewControl',
            'mapTypeControl',
            'fullscreenControl',
            'zoomControl',
            'scaleControl',
            'Zgłoś problem',
            'właścicielem tej firmy',
            'Jesteś właścicielem',
            'Report a problem',
            'Are you the owner',
            'Claim this business',
        ];
        
        foreach ($junkKeywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return false;
            }
        }
        
        // Jeśli nazwa to dokładnie nazwa biznesu (bez dodatkowych szczegółów), to nie jest usługa
        // np. "Nalesnikarnia Gramofon Poznań" - to nie usługa, to nazwa firmy
        if (preg_match('/^[A-ZĄĆĘŁŃÓŚŹŻ]/u', $name) && mb_strlen($name) < 50 && 
            (stripos($name, 'Poznań') !== false || stripos($name, 'Warszawa') !== false || 
             stripos($name, 'Kraków') !== false || stripos($name, 'Wrocław') !== false)) {
            // Jeśli nazwa zawiera miasto i jest krótka, prawdopodobnie to nazwa firmy
            return false;
        }
        
        // Teraz sprawdzamy pozytywne cechy
        
        // Jeśli ma opis (nie-URL) i nazwa nie wygląda na śmieć, prawdopodobnie jest usługą
        if (!empty($description) && mb_strlen($description) > 5 && 
            !preg_match('/^https?:\/\//i', $description)) {
            return true;
        }
        
        // Jeśli nazwa zawiera typowe słowa związane z usługami/produktami
        $serviceKeywords = [
            'kurs', 'szkolenie', 'lekcj', 'prawo jazdy', 'kategori',
            'naleśnik', 'deser', 'danie', 'menu', 'śniadanie', 'lunch',
            'pizza', 'burger', 'sałatka', 'zupa', 'makaron',
            'piwo', 'wino', 'drink', 'koktajl',
            'strzyżenie', 'fryzura', 'manicure', 'pedicure',
            'masaż', 'zabieg', 'terapia',
        ];
        
        foreach ($serviceKeywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Próbuj znaleźć usługi w znanych sekcjach (fallback)
     */
    private function extractServicesFromKnownSections(array $data): array
    {
        $services = [];
        
        // Rekursywnie szukaj struktur przypominających usługi
        $this->findServicesRecursive($data, $services, 0, 15);
        
        return $services;
    }
    
    /**
     * Rekursywnie przeszukuj strukturę danych
     */
    private function findServicesRecursive($data, array &$services, int $depth = 0, int $maxDepth = 15): void
    {
        if ($depth > $maxDepth || count($services) > 100) {
            return;
        }
        
        if (is_array($data)) {
            // Sprawdź czy to para [nazwa, opis]
            if (count($data) === 2 && 
                isset($data[0]) && is_string($data[0]) && 
                isset($data[1]) && is_string($data[1])) {
                
                $name = $data[0];
                $description = $data[1];
                
                if ($this->isValidServiceEntry($name, $description)) {
                    $services[] = [
                        'name' => $name,
                        'description' => $description
                    ];
                }
            }
            
            // Rekurencja w głąb
            foreach ($data as $item) {
                if (is_array($item)) {
                    $this->findServicesRecursive($item, $services, $depth + 1, $maxDepth);
                }
            }
        }
    }

    /**
     * Wyciągnij opis biznesu z JSON - uniwersalna metoda
     * Szuka tekstów o odpowiedniej długości i charakterystyce w całej strukturze
     */
    private function extractDescription(array $data): ?string
    {
        $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // METODA 1: Szukaj wzorca tekstu w pojedynczych cudzysłowach/tablicach
        // Opisy w Google Maps są często w strukturze: [["Tekst opisu"],...]
        // Używamy różnych wariantów escapowania
        $patterns = [
            // Pattern 1: Podwójnie escapowane [[\" w JSON string (najczęstsze)
            // Używamy negative lookahead aby zatrzymać się przed zamykającym \"
            '/\[\[\\\\\\"(.+?)(?=\\\\\\")\\\\\\"/us',
            // Pattern 2: Pojedynczo escapowane  
            '/\[\\\\"(.+?)(?=\\\\")\\\\"/us',
            // Pattern 3: W prostej tablicy
            '/\[\"(.+?)(?=\")\"/us',
        ];
        
        $candidates = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $jsonString, $matches)) {
                foreach ($matches[1] as $match) {
                    if ($this->isValidDescription($match)) {
                        $candidates[] = $match;
                    }
                }
            }
        }
        
        // METODA 2: Jeśli nie znaleziono, przeszukaj rekursywnie strukturę
        if (empty($candidates)) {
            $this->findDescriptionRecursive($data, $candidates);
        }
        
        // Wybierz najlepszy kandydat (najdłuższy, który zawiera zdania)
        if (!empty($candidates)) {
            usort($candidates, function($a, $b) {
                $scoreA = $this->scoreDescription($a);
                $scoreB = $this->scoreDescription($b);
                return $scoreB <=> $scoreA; // Sortuj malejąco
            });
            
            $description = $candidates[0];
            $description = $this->cleanDescription($description);
            
            Log::info("BrightData: Description extracted", [
                'length' => mb_strlen($description),
                'preview' => mb_substr($description, 0, 100)
            ]);
            
            return $description;
        }
        
        return null;
    }
    
    /**
     * Sprawdź czy tekst jest prawdopodobnie opisem biznesu
     */
    private function isValidDescription(string $text): bool
    {
        // Zbyt krótkie lub za długie
        $length = mb_strlen($text);
        if ($length < 50 || $length > 1000) {
            return false;
        }
        
        // Filtruj śmieci
        $junkPatterns = [
            '/^http/i',
            '/^www\./i',
            '/data:image/i',
            '/function\(/i',
            '/window\./i',
            '/document\./i',
            '/\.js$/i',
            '/\.css$/i',
            '/^\d+$/',
        ];
        
        foreach ($junkPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }
        
        // Musi zawierać przynajmniej jedną spację (wielosłowny)
        if (strpos($text, ' ') === false) {
            return false;
        }
        
        // Filtruj nagłówki typu "Nazwa Firmy · Adres, Kod, Miasto, Polska"
        // To nie są opisy, tylko dane kontaktowe
        if (preg_match('/·.*\d{2}-\d{3}.*Polska$/u', $text)) {
            return false;
        }
        
        // Jeśli tekst zawiera głównie nazwę firmy + adres (bez zdań), odrzuć
        $citiesPattern = '/(Poznań|Warszawa|Kraków|Wrocław|Gdańsk|Łódź|Katowice|Szczecin)/i';
        $addressPattern = '/\d{2}-\d{3}/'; // Kod pocztowy
        if (preg_match($citiesPattern, $text) && preg_match($addressPattern, $text) && 
            !preg_match('/[.!?]/', $text)) {
            // Ma miasto i kod pocztowy, ale nie ma zdań = prawdopodobnie sam adres
            return false;
        }
        
        // Filtruj odpowiedzi właściciela, opinie klientów i artykuły/posty (to nie są opisy biznesu!)
        $junkContentPatterns = [
            // Odpowiedzi właściciela
            '/^(Bardzo|Serdecznie|Ogromnie)\s+(dziękujemy|dziękuję)/ui',
            '/^Pani[e]?\s+[A-ZĄĆĘŁŃÓŚŹŻ]/u', // "Panie Stanisławie", "Pani Aniu"
            '/Cieszymy się,?\s+że/ui',
            '/Gratulujemy/ui',
            '/Zapraszamy ponownie/ui',
            '/Dziękujemy za opinię/ui',
            // Opinie klientów
            '/^Świetne miejsce/ui',
            '/^Polecam/ui',
            '/^Rewelacja/ui',
            '/wracaliśmy/ui',
            '/życzymy/ui',
            '/^Najczęściej odwiedzany/ui',
            '/w moich czasach/ui',
            '/choć surowy/ui',
            // Artykuły i posty edukacyjne
            '/^Znak [A-Z][‑-]\d+/u', // "Znak C-10", "Znak A-1"
            '/Widząc znak/ui',
            '/Nakazuje/ui',
        ];
        
        foreach ($junkContentPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }
        
        // Musi mieć przynajmniej 5 słów
        $words = str_word_count($text);
        if ($words < 5) {
            return false;
        }
        
        // Bonus: zawiera polskie znaki lub zdania z kropkami/wykrzyknikami
        if (preg_match('/[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', $text) || 
            preg_match('/[.!?]/', $text)) {
            return true;
        }
        
        // Zawiera słowa typowe dla opisów biznesów (na początku tekstu - pierwszych 100 znaków)
        $textStart = mb_substr($text, 0, 100);
        $descriptionStartKeywords = [
            'zapraszamy do', 'oferujemy', 'specjalizuj się', 'zapewnia',
            'to poznańsk', 'to warszaw', 'to firma', 'to restauracj', 'to pub',
            'to lokal', 'to ośrodek', 'to szkoła',
        ];
        
        foreach ($descriptionStartKeywords as $keyword) {
            if (stripos($textStart, $keyword) !== false) {
                return true;
            }
        }
        
        // Zawiera słowa typowe dla opisów (gdziekolwiek w tekście)
        $descriptionKeywords = [
            'serwuje', 'przyciąga gości', 'przygotowanie kursantów',
            'wieloletnim doświadczeniem', 'od 2014 roku', 'od lat',
            'naszym priorytetem', 'gwarantując',
        ];
        
        foreach ($descriptionKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Oceń jakość opisu (wyższy = lepszy)
     */
    private function scoreDescription(string $text): int
    {
        $score = 0;
        $textStart = mb_substr($text, 0, 100);
        
        // SUPER WAŻNE: Sprawdź czy zaczyna się jak prawdziwy opis biznesu
        // "Zapraszamy do [NAZWA]" lub "[NAZWA] to [typ]"
        if (preg_match('/^(🚗|🍻|🎓|🍕|☕|🏠|🏢|🏨)?\s*Zapraszamy do/ui', $textStart)) {
            $score += 100; // Mega bonus!
        }
        if (preg_match('/to\s+(poznańska|warszawska|krakowska|firma|restauracja|pub|lokal|ośrodek|szkoła)/ui', $textStart)) {
            $score += 100; // Mega bonus!
        }
        if (preg_match('/który\s+(gości|serwuje|oferuje|zapewnia)/ui', $text)) {
            $score += 80;
        }
        
        // Długość (50-300 znaków = idealne)
        $length = mb_strlen($text);
        if ($length >= 100 && $length <= 300) {
            $score += 20;
        } elseif ($length >= 50 && $length <= 500) {
            $score += 10;
        }
        
        // Zawiera polskie znaki
        if (preg_match('/[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', $text)) {
            $score += 15;
        }
        
        // Zawiera znaki interpunkcyjne (pełne zdania)
        if (preg_match_all('/[.!?]/', $text) > 0) {
            $score += 10;
        }
        
        // Zawiera typowe słowa z opisów biznesów
        $keywords = ['od 2014 roku', 'od lat', 'wieloletnim doświadczeniem', 'naszym priorytetem', 'przyciąga gości', 'serwuje', 'gwarantując'];
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $score += 15;
                break;
            }
        }
        
        // Liczba słów (więcej = lepiej, do pewnego punktu)
        $words = str_word_count($text);
        $score += min($words, 50);
        
        return $score;
    }
    
    /**
     * Rekursywnie szukaj potencjalnych opisów w strukturze
     */
    private function findDescriptionRecursive($data, array &$candidates, int $depth = 0, int $maxDepth = 15): void
    {
        if ($depth > $maxDepth || count($candidates) >= 10) {
            return;
        }
        
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_string($item) && $this->isValidDescription($item)) {
                    $candidates[] = $item;
                } elseif (is_array($item)) {
                    $this->findDescriptionRecursive($item, $candidates, $depth + 1, $maxDepth);
                }
            }
        }
    }
    
    /**
     * Wyczyść opis z niepotrzebnych znaków
     */
    private function cleanDescription(string $description): string
    {
        // Usuń escapowanie
        $description = str_replace('\\n', ' ', $description);
        $description = str_replace('\\r', ' ', $description);
        $description = str_replace('\\t', ' ', $description);
        $description = str_replace('\\', '', $description);
        $description = trim($description);
        
        // Usuń emoji (powodują błąd MySQL cache)
        // Emoji są często na początku opisu (🚗, 🍻, itp.)
        $description = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $description);
        $description = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $description);
        $description = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $description);
        
        // Usuń znaki specjalne na początku (emoji, spacje, itp.)
        $description = preg_replace('/^[^a-zA-ZĄ-żÀ-ÿ0-9]+/', '', $description);
        
        // Usuń wielokrotne spacje
        $description = preg_replace('/\s+/', ' ', $description);
        
        return trim($description);
    }
}

