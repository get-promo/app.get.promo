<?php

echo "=== ANALIZA DANYCH GOOGLE MAPS JSON ===\n\n";

$jsonFile = 'brightdata_json_2025-10-12_07-17-12.json';
$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (!$data) {
    die("❌ Nie udało się zdekodować JSON\n");
}

echo "✅ JSON załadowany! Rozmiar danych: " . count($data) . " elementów głównych\n\n";

// Funkcja rekurencyjna do szukania w zagnieżdżonych tablicach
function searchInArray($array, $depth = 0, $maxDepth = 30, $path = []) {
    static $results = [];
    
    if ($depth > $maxDepth) {
        return;
    }
    
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $currentPath = array_merge($path, [$key]);
            
            if (is_string($value)) {
                // Szukaj interesujących fraz
                $lowerValue = mb_strtolower($value);
                
                $keywords = [
                    'usług' => 'services',
                    'produkt' => 'products',
                    'kurs' => 'course',
                    'category' => 'category',
                    'categories' => 'categories',
                    'amenities' => 'amenities',
                    'features' => 'features',
                    'options' => 'options',
                ];
                
                foreach ($keywords as $keyword => $label) {
                    if (strpos($lowerValue, $keyword) !== false && strlen($value) < 200) {
                        $results[$label][] = [
                            'value' => $value,
                            'path' => implode(' -> ', $currentPath),
                            'depth' => $depth
                        ];
                    }
                }
            } elseif (is_array($value)) {
                searchInArray($value, $depth + 1, $maxDepth, $currentPath);
            }
        }
    }
    
    return $results;
}

echo "=== SZUKAM STRUKTUR DANYCH... ===\n";
$findings = searchInArray($data);

if (empty($findings)) {
    echo "❌ Nie znaleziono struktur z produktami/usługami\n\n";
} else {
    foreach ($findings as $category => $items) {
        echo "\n=== $category (znaleziono: " . count($items) . ") ===\n";
        
        // Pokaż tylko pierwsze 10 wyników dla każdej kategorii
        foreach (array_slice($items, 0, 10) as $idx => $item) {
            echo ($idx + 1) . ". {$item['value']}\n";
            echo "   Ścieżka: {$item['path']}\n";
            echo "   Głębokość: {$item['depth']}\n\n";
        }
    }
}

// Szukaj struktury "service_options" lub podobnych
echo "\n=== SZUKAM SEKCJI SERVICE_OPTIONS / AMENITIES ===\n";
function findServiceOptions($array, $depth = 0, $maxDepth = 30) {
    if ($depth > $maxDepth || !is_array($array)) {
        return null;
    }
    
    foreach ($array as $key => $value) {
        if ((is_string($key) && (
            strpos($key, 'service') !== false || 
            strpos($key, 'amenities') !== false ||
            strpos($key, 'options') !== false
        )) && is_array($value)) {
            return $value;
        }
        
        if (is_array($value)) {
            $result = findServiceOptions($value, $depth + 1, $maxDepth);
            if ($result !== null) {
                return $result;
            }
        }
    }
    
    return null;
}

$serviceOptions = findServiceOptions($data);
if ($serviceOptions) {
    echo "✅ Znaleziono sekcję z opcjami usług!\n";
    echo "Struktura:\n";
    print_r(array_slice($serviceOptions, 0, 5)); // Pokaż pierwsze 5 elementów
} else {
    echo "❌ Nie znaleziono dedykowanej sekcji\n";
}

// Sprawdź czy są wzmianki o konkretnych usługach OSK Bravo
echo "\n=== SZUKAM KONKRETNYCH USŁUG OSK BRAVO ===\n";
function findOskServices($array, $depth = 0, $maxDepth = 30) {
    static $services = [];
    
    if ($depth > $maxDepth) {
        return;
    }
    
    if (is_array($array)) {
        foreach ($array as $value) {
            if (is_string($value)) {
                // Szukaj fraz związanych z usługami OSK
                if (preg_match('/(kat\.|kategori|kurs|prawo jazdy|egzamin|szkolenie|doszkalaj)/i', $value)) {
                    if (strlen($value) < 300 && !in_array($value, $services)) {
                        $services[] = $value;
                    }
                }
            } elseif (is_array($value)) {
                findOskServices($value, $depth + 1, $maxDepth);
            }
        }
    }
    
    return $services;
}

$oskServices = findOskServices($data);
if (!empty($oskServices)) {
    echo "✅ Znaleziono " . count($oskServices) . " wzmianek o usługach:\n";
    foreach (array_slice($oskServices, 0, 15) as $idx => $service) {
        echo ($idx + 1) . ". " . substr($service, 0, 150) . "\n";
    }
} else {
    echo "❌ Nie znaleziono wzmianek\n";
}

echo "\n=== PODSUMOWANIE ===\n";
echo "1. Google Maps (APP_INITIALIZATION_STATE) zawiera głównie dane strukturalne i meta\n";
echo "2. Produkty/Usługi NIE są wylistowane w dedykowanej sekcji API\n";
echo "3. Bright Data pobiera HTML, ale dane są w zakodowanej strukturze (protobuf-like)\n";
echo "4. Dla pobrania listy usług/produktów potrzebujemy:\n";
echo "   - Albo scrapować wizualny HTML (trudne - dynamiczne ładowanie)\n";
echo "   - Albo użyć innego endpoint API\n";
echo "   - Albo SerpApi Google Maps Place (sprawdzić czy ma Products/Services)\n\n";

echo "💡 REKOMENDACJA: Sprawdź czy SerpApi zwraca pole 'products' lub 'services' dla OSK Bravo\n";

