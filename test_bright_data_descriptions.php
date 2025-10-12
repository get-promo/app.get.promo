<?php

/**
 * Testowy skrypt do sprawdzania ekstrakcji opisów z Bright Data
 * 
 * Uruchom: php test_bright_data_descriptions.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Dane testowe
$testCases = [
    'Gramofon' => [
        'place_id' => 'ChIJqaQtszhbBEcRxQqaWjpezNM',
        'expected_description' => 'Naleśnikarnia Gramofon to poznańska restauracja, która od 2014 roku serwuje wyśmienite francuskie naleśniki i puszyste pankejki. Miejsce przyciąga gości nie tylko smakiem popularnych dań, ale również wyjątkowym, klimatycznym wystrojem.'
    ],
    'OSK Bravo' => [
        'place_id' => 'ChIJocwS2S1bBEcRmYKpe2mRxGg',
        'expected_description' => 'Zapraszamy do OSK BRAVO - firmy specjalizującej się w szkoleniach i kursach na prawo jazdy kat. B w Poznaniu. Naszym priorytetem jest dokładne przygotowanie kursantów do egzaminu praktycznego oraz zapewnienie bezpiecznej jazdy po drogach. Nasz kierownik, Sławek, to instruktor z wieloletnim doświadczeniem, którego wspiera świetny instruktor Maciej. Wspólnie prowadzimy ośrodek szkolenia kierowców, gwarantując cierpliwe tłumaczenie manewrów i bogatą wiedzę.'
    ],
    'Deja Vu' => [
        'place_id' => 'ChIJY2p_oEBbBEcRFu66H_4auAw',
        'expected_description' => 'Pub DejaVu, który gości swoich klientów już od parunastu lat! Przyciąga klimatem, niskimi cenami jak i załogą. Zapraszamy!'
    ],
];

echo "=== TEST EKSTRAKCJI OPISÓW Z BRIGHT DATA ===\n\n";

foreach ($testCases as $businessName => $testCase) {
    echo "📍 Testowanie: {$businessName}\n";
    echo str_repeat("-", 80) . "\n";
    
    $placeId = $testCase['place_id'];
    $expectedDesc = $testCase['expected_description'];
    
    // Pobierz dane z Bright Data
    echo "Pobieranie danych z Bright Data...\n";
    $data = fetchBrightData($placeId);
    
    if (!$data) {
        echo "❌ BŁĄD: Nie udało się pobrać danych\n\n";
        continue;
    }
    
    // Szukaj opisu w surowych danych
    echo "Szukanie oczekiwanego opisu w surowych danych...\n";
    $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    // Sprawdź czy oczekiwany opis jest w danych
    $expectedFragment = mb_substr($expectedDesc, 0, 50); // Pierwsze 50 znaków
    if (strpos($jsonString, $expectedFragment) !== false) {
        echo "✅ Oczekiwany opis JEST w surowych danych!\n";
        
        // Znajdź kontekst (100 znaków przed i po)
        $pos = strpos($jsonString, $expectedFragment);
        $context_start = max(0, $pos - 100);
        $context_length = min(strlen($jsonString) - $context_start, 500);
        $context = substr($jsonString, $context_start, $context_length);
        
        echo "Kontekst w JSON:\n";
        echo substr($context, 0, 300) . "...\n\n";
    } else {
        echo "❌ Oczekiwany opis NIE ZOSTAŁ znaleziony w surowych danych\n";
        echo "To oznacza, że Bright Data nie zwraca tego opisu, lub jest w innym formacie.\n\n";
    }
    
    // Test ekstrakcji (użyj klasy BrightDataService)
    $brightDataService = new \App\Services\Report\BrightDataService();
    
    // Użyj reflection aby wywołać prywatną metodę
    $reflection = new ReflectionClass($brightDataService);
    $method = $reflection->getMethod('extractDescription');
    $method->setAccessible(true);
    
    echo "Uruchamianie extractDescription()...\n";
    $extractedDesc = $method->invoke($brightDataService, $data);
    
    if ($extractedDesc) {
        echo "✅ Znaleziono opis: " . mb_substr($extractedDesc, 0, 100) . "...\n";
        echo "Długość: " . mb_strlen($extractedDesc) . " znaków\n";
        
        // Porównaj z oczekiwanym
        $similarity = similar_text($extractedDesc, $expectedDesc, $percent);
        echo "Podobieństwo do oczekiwanego: " . round($percent, 1) . "%\n";
        
        if ($percent >= 80) {
            echo "✅ SUKCES! Opis jest poprawny!\n";
        } else {
            echo "⚠️  Opis różni się od oczekiwanego:\n";
            echo "Oczekiwany: {$expectedDesc}\n";
            echo "Znaleziony: {$extractedDesc}\n";
        }
    } else {
        echo "❌ BŁĄD: extractDescription() zwróciło NULL\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

echo "✅ Test zakończony!\n";

// Funkcja pomocnicza do pobierania danych z Bright Data
function fetchBrightData(string $placeId): ?array
{
    $apiKey = config('services.brightdata.api_key');
    $zone = config('services.brightdata.zone', 'get_promo_web_unlocker1');
    
    if (!$apiKey) {
        echo "❌ BŁĄD: Brak API key dla Bright Data\n";
        return null;
    }
    
    try {
        $googleMapsUrl = "https://www.google.com/maps/place/?q=place_id:{$placeId}&hl=pl";
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.brightdata.com/request', [
            'zone' => $zone,
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
                    echo "✅ Dane pobrane, rozmiar JSON: " . strlen($jsonString) . " bajtów\n";
                    return $data;
                }
            } else {
                echo "❌ Nie znaleziono APP_INITIALIZATION_STATE w HTML\n";
            }
        } else {
            echo "❌ Błąd HTTP: " . $response->status() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Wyjątek: " . $e->getMessage() . "\n";
    }
    
    return null;
}

