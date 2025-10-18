<?php 
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funkcja do logowania
function logMessage($message) {
    echo $message . "<br>";
    flush();
    ob_flush();
}

// Funkcja do czyszczenia danych
function cleanString($string) {
    if (empty($string)) {
        return '';
    }
    
    // Usuwa emoji i inne znaki specjalne
    $string = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $string); // emotikony
    $string = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $string); // symbole i piktogramy
    $string = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $string); // symbole transportu i mapy
    $string = preg_replace('/[\x{1F700}-\x{1F77F}]/u', '', $string); // symbole alchemiczne
    $string = preg_replace('/[\x{1F780}-\x{1F7FF}]/u', '', $string); // symbole geometryczne
    $string = preg_replace('/[\x{1F800}-\x{1F8FF}]/u', '', $string); // symbole uzupełniające
    $string = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $string); // symbole uzupełniające
    $string = preg_replace('/[\x{1FA00}-\x{1FA6F}]/u', '', $string); // symbole szachowe
    $string = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $string); // symbole emoji
    $string = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $string);   // symbole różne
    $string = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $string);   // symbole dekoracyjne
    
    // Bezpieczna konwersja znaków specjalnych na ich odpowiedniki ASCII
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    if ($converted !== false) {
        $string = $converted;
    }
    
    // Usuwa wszystkie znaki niebędące literami, cyframi, spacjami, myślnikami lub kropkami
    $string = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $string);
    
    // Usuwa nadmiarowe spacje
    $string = trim(preg_replace('/\s+/', ' ', $string));
    
    return $string;
}

$serperKey = 'edfeb0d3f3333cdf3b165f8046f1547c80f04f65';
$servername = "localhost";
$username = "shopium";
$password = "2ZLpcswskl3";
$dbname = "shopium";    

// Tworzymy jedno połączenie z bazą danych, które będzie używane przez cały skrypt
$conn = new mysqli($servername, $username, $password, $dbname);
// Sprawdzamy połączenie
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ustawiamy kodowanie znaków
$conn->set_charset("utf8mb4");

$city = 'Poznań';
$districts = [];
$keywords = [
    'Barber',
    'Fryzjer',
    'Kosmetyczka',
    'Paznokcie',
    'Solarium',
    'Restauracja',
    'Kawiarnia',
    'Pub'
];

$totalLeadsAdded = 0;
$totalLeadsUpdated = 0;
$totalErrors = 0;

logMessage("Rozpoczynam pobieranie dzielnic dla miasta: $city");

for($p=1; $p<=5;$p++) {
    logMessage("Pobieranie dzielnic - strona $p");
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://google.serper.dev/places',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{"q":"barber '.$city.'","gl":"pl","page":'.$p.'}',
        CURLOPT_HTTPHEADER => array(
            'X-API-KEY: '.$serperKey,
            'Content-Type: application/json'
        ),
    ));

    $responseText = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($httpCode != 200) {
        logMessage("Błąd HTTP podczas pobierania dzielnic: $httpCode");
        curl_close($curl);
        continue;
    }
    
    curl_close($curl);
    
    $response = json_decode($responseText);
    
    if (!$response || !isset($response->places) || !is_array($response->places)) {
        logMessage("Błąd podczas dekodowania odpowiedzi JSON lub brak wyników");
        continue;
    }
    
    logMessage("Znaleziono " . count($response->places) . " miejsc na stronie $p");

    foreach($response->places as $place) {
        if (!isset($place->cid) || empty($place->cid)) {
            continue;
        }
        
        $cid = $place->cid;
        
        try {
            $placeDetailsStr = @file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?key=AIzaSyBbnvD2vzNXXypDIJWXviN5ITVSe3JC8w4&cid='.$cid);
            
            if (!$placeDetailsStr) {
                continue;
            }
            
            $placeDetails = json_decode($placeDetailsStr, true);
            
            if (!isset($placeDetails['result']['address_components']) || !is_array($placeDetails['result']['address_components'])) {
                continue;
            }
            
            foreach($placeDetails['result']['address_components'] as $addressComponent) {
                if(isset($addressComponent['types'][0]) && $addressComponent['types'][0] == 'sublocality_level_1') {
                    $district = $addressComponent['long_name'];
                    if(!isset($districts[$district])) {
                        $districts[$district] = true;
                        logMessage("Znaleziono dzielnicę: $district");
                    }
                }
            }
        } catch (Exception $e) {
            logMessage("Błąd podczas pobierania szczegółów miejsca: " . $e->getMessage());
        }
    }
    
    // Jeśli znaleźliśmy już 5 dzielnic, przerywamy
    if (count($districts) >= 5) {
        logMessage("Znaleziono wystarczającą liczbę dzielnic: " . count($districts));
        break;
    }
}

// Jeśli nie znaleziono dzielnic, dodajemy samo miasto
if (empty($districts)) {
    $districts[$city] = true;
    logMessage("Nie znaleziono dzielnic, używam całego miasta: $city");
}

logMessage("Znalezione dzielnice: " . implode(", ", array_keys($districts)));
logMessage("Rozpoczynam wyszukiwanie miejsc dla każdej kombinacji słowa kluczowego i dzielnicy");

// Przygotowujemy zapytanie SQL z klauzulą INSERT IGNORE
$insertStmt = $conn->prepare("INSERT IGNORE INTO `lead` (
    `cid`,
    `name`,
    `address`,
    `lat`,
    `lng`,
    `rating`,
    `rating_count`,
    `category`,
    `phone`,
    `website`
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Przygotowujemy zapytanie SQL do aktualizacji istniejących rekordów
$updateStmt = $conn->prepare("UPDATE `lead` SET 
    `name` = ?, 
    `address` = ?, 
    `lat` = ?, 
    `lng` = ?, 
    `rating` = ?, 
    `rating_count` = ?, 
    `category` = ?, 
    `phone` = ?, 
    `website` = ? 
    WHERE `cid` = ?");

// Przygotowujemy zapytanie SQL do sprawdzania, czy rekord już istnieje
$checkStmt = $conn->prepare("SELECT `id` FROM `lead` WHERE `cid` = ?");

foreach($keywords as $keyword) {
    logMessage("Przetwarzanie słowa kluczowego: $keyword");
    
    foreach($districts as $district => $dummy) {
        logMessage("Przetwarzanie dzielnicy: $district");
        
        $searchString = $keyword . ' ' . $city . ' ' . $district;
        logMessage("Wyszukiwanie: $searchString");
        
        $p = 1;
        $totalResults = 0;
        
        do {
            logMessage("Pobieranie strony $p dla: $searchString");
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://google.serper.dev/places',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{"q":"'.$searchString.'","gl":"pl","page":'.$p.'}',
                CURLOPT_HTTPHEADER => array(
                    'X-API-KEY: '.$serperKey,
                    'Content-Type: application/json'
                ),
            ));
        
            $responseText = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if ($httpCode != 200) {
                logMessage("Błąd HTTP podczas pobierania wyników: $httpCode");
                curl_close($curl);
                break;
            }
            
            curl_close($curl);
            
            $response = json_decode($responseText);
            
            if (!$response || !isset($response->places) || !is_array($response->places)) {
                logMessage("Błąd podczas dekodowania odpowiedzi JSON lub brak wyników");
                break;
            }
            
            $resultsCount = count($response->places);
            $totalResults += $resultsCount;
            
            logMessage("Znaleziono $resultsCount miejsc na stronie $p");
            
            foreach($response->places as $place) {
                try {
                    // Sprawdzamy, czy miejsce nie jest zamknięte
                    if (isset($place->closedTemporarily) && $place->closedTemporarily === true) {
                        continue;
                    }
                    
                    if (isset($place->closedPermanently) && $place->closedPermanently === true) {
                        continue;
                    }
                    
                    // Sprawdzamy, czy mamy wszystkie potrzebne dane
                    if (!isset($place->cid) || empty($place->cid)) {
                        continue;
                    }
                    
                    // Czyścimy dane
                    $cid = cleanString($place->cid);
                    $title = cleanString($place->title ?? '');
                    $address = cleanString($place->address ?? '');
                    $latitude = $place->latitude ?? 0;
                    $longitude = $place->longitude ?? 0;
                    $rating = $place->rating ?? 0;
                    $ratingCount = $place->ratingCount ?? 0;
                    $category = cleanString($place->category ?? '');
                    $phoneNumber = cleanString($place->phoneNumber ?? '');
                    $website = cleanString($place->website ?? '');
                    
                    // Sprawdzamy, czy rekord już istnieje
                    $checkStmt->bind_param('s', $cid);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // Aktualizujemy istniejący rekord
                        $updateStmt->bind_param('ssdddissss',
                            $title,
                            $address,
                            $latitude,
                            $longitude,
                            $rating,
                            $ratingCount,
                            $category,
                            $phoneNumber,
                            $website,
                            $cid
                        );
                        
                        if ($updateStmt->execute()) {
                            $totalLeadsUpdated++;
                            logMessage("Zaktualizowano lead: $title");
                        } else {
                            $totalErrors++;
                            logMessage("Błąd podczas aktualizacji lead: " . $conn->error);
                        }
                    } else {
                        // Dodajemy nowy rekord
                        $insertStmt->bind_param('sssdddisss',
                            $cid,
                            $title,
                            $address,
                            $latitude,
                            $longitude,
                            $rating,
                            $ratingCount,
                            $category,
                            $phoneNumber,
                            $website
                        );
                        
                        if ($insertStmt->execute()) {
                            $totalLeadsAdded++;
                            logMessage("Dodano nowy lead: $title");
                        } else {
                            $totalErrors++;
                            logMessage("Błąd podczas dodawania lead: " . $conn->error);
                        }
                    }
                } catch (Exception $e) {
                    $totalErrors++;
                    logMessage("Błąd podczas przetwarzania miejsca: " . $e->getMessage());
                }
            }
            
            $p++;
        } while($resultsCount == 10 && $p <= 3); // Ograniczamy do 3 stron wyników
        
        logMessage("Zakończono przetwarzanie dla: $searchString. Znaleziono łącznie: $totalResults miejsc");
    }
}

// Zamykamy przygotowane zapytania
$insertStmt->close();
$updateStmt->close();
$checkStmt->close();

// Zamykamy połączenie z bazą danych
$conn->close();

logMessage("Zakończono generowanie leadów");
logMessage("Dodano nowych leadów: $totalLeadsAdded");
logMessage("Zaktualizowano istniejących leadów: $totalLeadsUpdated");
logMessage("Liczba błędów: $totalErrors");
?>