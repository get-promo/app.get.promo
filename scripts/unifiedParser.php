<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sprawdź czy podano ID jako parametr
$startId = isset($argv[1]) ? (int)$argv[1] : 0;

// Stałe konfiguracyjne
const BATCH_SIZE = 50;
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "2ZLpcswskl3";
const DB_NAME = "shopium_leads";

// Funkcja do tworzenia połączenia z bazą
function createDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Połączenie nieudane: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Funkcja do logowania z poprawną obsługą bufora
function logMessage($message) {
    if (ob_get_level() == 0) {
        ob_start();
    }
    echo $message . "\n";
    ob_flush();
    flush();
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
    $string = preg_replace('/[^\p{L}\p{N}\s\-.@]/u', '', $string);
    
    // Usuwa nadmiarowe spacje
    $string = trim(preg_replace('/\s+/', ' ', $string));
    
    // Jeśli po wszystkich operacjach string jest pusty, zwróć pusty ciąg
    if (empty($string)) {
        return '';
    }
    
    return $string;
}

// Funkcja do wyszukiwania adresów email na stronie
function findEmails($html) {
    // Standardowe wyszukiwanie adresów email w tekście
    $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    preg_match_all($pattern, $html, $matches);
    
    $emails = [];
    if (!empty($matches[0])) {
        foreach ($matches[0] as $email) {
            // Najpierw dekodujemy adresy URL-encoded
            $email = urldecode($email);
            
            // Filtrujemy adresy kończące się na rozszerzenia plików graficznych
            $fileExtensions = ['.png', '.jpg', '.jpeg', '.svg', '.gif', '.webp', '.bmp'];
            $isFileExtension = false;
            
            foreach ($fileExtensions as $ext) {
                if (strtolower(substr($email, -strlen($ext))) === $ext) {
                    $isFileExtension = true;
                    break;
                }
            }
            
            // Jeśli to nie jest rozszerzenie pliku graficznego i jest poprawnym adresem email
            if (!$isFileExtension && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }
    }
    
    // Dodatkowo szukamy wszystkich linków mailto: za pomocą DOMDocument
    if (!empty($html)) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = trim($link->getAttribute('href'));
            
            if (stripos($href, 'mailto:') === 0) {
                $mailtoEmail = substr($href, 7);
                // Czyszczenie parametrów
                $mailtoEmail = explode('?', $mailtoEmail)[0];
                
                if (!empty($mailtoEmail) && filter_var($mailtoEmail, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $mailtoEmail;
                }
            }
        }
    }
    
    return array_unique($emails);
}

// Funkcja do wyszukiwania linków kontaktowych na stronie
function findContactLinks($html, $baseUrl) {
    // Sprawdź czy HTML nie jest pusty
    if (empty($html)) {
        return [];
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $links = $dom->getElementsByTagName('a');
    $contactLinks = [];
    
    foreach ($links as $link) {
        $href = trim($link->getAttribute('href')); // Dodajemy trim() dla pewności
        $text = strtolower(trim($link->textContent)); // Dodajemy trim() dla pewności
        
        // Bezpośrednio dodajemy wszystkie linki mailto: niezależnie od treści
        if (stripos($href, 'mailto:') === 0) {
            $contactLinks[] = $href;
            continue;
        }
        
        // Pomijamy linki telefoniczne
        if (stripos($href, 'tel:') === 0) {
            continue;
        }
        
        // Sprawdzamy czy link lub tekst zawiera słowa kluczowe
        if (strpos(strtolower($href), 'kontakt') !== false || 
            strpos(strtolower($href), 'contact') !== false || 
            strpos($text, 'kontakt') !== false || 
            strpos($text, 'contact') !== false) {
            
            // Jeśli link zaczyna się od http/https, jest to pełny URL
            if (strpos($href, 'http') === 0) {
                $contactLinks[] = $href;
            } 
            // Jeśli link zaczyna się od // (protocol-relative URL)
            else if (strpos($href, '//') === 0) {
                $parsedBaseUrl = parse_url($baseUrl);
                $scheme = isset($parsedBaseUrl['scheme']) ? $parsedBaseUrl['scheme'] : 'http';
                $contactLinks[] = $scheme . ':' . $href;
            }
            // Obsługa linków względnych
            else {
                if (strpos($href, '/') === 0) { // Zaczyna się od /
                    $parsedUrl = parse_url($baseUrl);
                    $baseUrlRoot = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    $href = $baseUrlRoot . $href;
                } else { // Nie zaczyna się od /
                    // Usuń ewentualną nazwę pliku z $baseUrl, aby poprawnie dołączyć ścieżkę względną
                    $path = dirname(parse_url($baseUrl, PHP_URL_PATH));
                    if ($path === '/' || $path === '.') {
                        $path = '';
                    }
                    $baseUrlWithPath = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST) . $path;
                    $href = rtrim($baseUrlWithPath, '/') . '/' . ltrim($href, '/');
                }
                $contactLinks[] = $href;
            }
        }
    }
    
    return array_unique($contactLinks);
}

// Funkcja do pobierania zawartości strony
function getWebsiteContent($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    
    try {
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            logMessage("Nie udało się pobrać zawartości strony $url");
            return '';
        }
        return $content;
    } catch (Exception $e) {
        logMessage("Błąd podczas pobierania strony $url: " . $e->getMessage());
        return '';
    }
}

// Funkcja do konwersji URL Facebooka na wersję mbasic
function convertToMbasicUrl($url) {
    $url = preg_replace('/\?.*$/', '', $url);
    $url = rtrim($url, '/');
    $url = str_replace(['www.facebook.com', 'm.facebook.com'], 'mbasic.facebook.com', $url);
    if (strpos($url, 'facebook.com') === 0 || strpos($url, 'http://facebook.com') === 0 || strpos($url, 'https://facebook.com') === 0) {
        $url = str_replace('facebook.com', 'mbasic.facebook.com', $url);
    }
    return $url;
}

// Funkcja do pobierania emaila z Facebooka
function getEmailFromFacebook($url) {
    $cookies = 'c_user=61576034056636; datr=xQAraBTJSKtJ6aNQjqdOGfAe; dpr=1; locale=pl_PL; oo=v1%7C3%3A1747648758; presence=C%7B%22t3%22%3A%5B%5D%2C%22utc3%22%3A1747648846462%2C%22v%22%3A1%7D; sb=xQAraHVOMmr19MtvbQ7vHGws; wd=225x623; xs=3%3APpJ4r6XF4HDs5w%3A2%3A1747648756%3A-1%3A-1';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1'
    ]);

    $html = curl_exec($ch);
    if (curl_errno($ch)) {
        logMessage("Błąd cURL podczas pobierania $url: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        logMessage("Nie udało się pobrać strony Facebook $url (HTTP kod: $http_code)");
        return null;
    }

    if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $matches)) {
        return $matches[0];
    }
    
    return null;
}


// Główna logika skryptu
logMessage("Rozpoczynam wyszukiwanie adresów email" . ($startId > 0 ? " od ID: $startId" : ""));

$processedRecords = 0;
$foundEmails = 0;
$errors = 0;
$offset = 0;

while (true) {
    try {
        $conn = createDbConnection();
        
        $sql = "SELECT id, website FROM `lead` 
                WHERE website IS NOT NULL 
                AND website != ''
                AND (email IS NULL OR email = '')";
        
        if ($startId > 0) {
            $sql .= " AND id >= " . $startId;
        }
        
        $sql .= " ORDER BY id ASC
                LIMIT " . BATCH_SIZE . " OFFSET " . $offset;
                
        $result = $conn->query($sql);
        
        if ($result->num_rows == 0) {
            $conn->close();
            break; 
        }
        
        $batchSizeResult = $result->num_rows;
        logMessage("Przetwarzam paczkę rekordów od ID " . ($offset > 0 ? $offset : $startId) . " (rozmiar paczki: $batchSizeResult)");
        
        $updateStmt = $conn->prepare("UPDATE `lead` SET email = ?, mail_checked = 1 WHERE id = ?");
        $markCheckedStmt = $conn->prepare("UPDATE `lead` SET mail_checked = 1 WHERE id = ?");
        
        while ($row = $result->fetch_assoc()) {
            $processedRecords++;
            $id = $row['id'];
            $website = $row['website'];
            
            logMessage("[$processedRecords] Przetwarzam ID: $id, Strona: $website");
            
            try {
                $email = null;
                // Dodajemy sprawdzenie, czy $website to link mailto:
                if (stripos($website, 'mailto:') === 0) {
                    $email = substr($website, 7);
                    // Dodatkowe czyszczenie, jeśli adres zawiera parametry jak ?subject=
                    $emailParts = explode('?', $email);
                    $email = $emailParts[0];
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $email = null; // Jeśli po czyszczeniu nie jest to prawidłowy email, zerujemy
                        logMessage("Nieprawidłowy email w linku mailto: $website");
                    } else {
                        logMessage("Rozpoznano email bezpośrednio z \$website (mailto:): $email");
                    }
                } else if (stripos($website, 'facebook.com') !== false) {
                    logMessage("Strona Facebook, używam dedykowanej logiki: $website");
                    $mbasicUrl = convertToMbasicUrl($website);
                    logMessage("Konwertowany URL Facebook: $mbasicUrl");
                    $email = getEmailFromFacebook($mbasicUrl);
                } else if (stripos($website, 'instagram.com') !== false) {
                    logMessage("Pomijam stronę Instagram: $website");
                    $markCheckedStmt->bind_param('i', $id);
                    $markCheckedStmt->execute();
                    continue;
                } else {
                    logMessage("Standardowa strona, używam ogólnej logiki: $website");
                    $html = getWebsiteContent($website);
                    
                    if (empty($html)) {
                        logMessage("Nie udało się pobrać strony: $website");
                        $markCheckedStmt->bind_param('i', $id);
                        $markCheckedStmt->execute();
                        $errors++;
                        continue;
                    }
                    
                    $emails = findEmails($html);
                    
                    if (empty($emails)) {
                        logMessage("Brak adresów email na stronie głównej, szukam na stronach kontaktowych dla: $website");
                        $contactLinks = findContactLinks($html, $website);
                        $tempEmails = []; // Tymczasowa tablica na emaile z linków kontaktowych

                        foreach ($contactLinks as $contactLink) {
                            if (stripos($contactLink, 'mailto:') === 0) {
                                $emailFromMailto = substr($contactLink, 7); // Usuń 'mailto:'
                                // Dodatkowe czyszczenie, jeśli adres zawiera parametry jak ?subject=
                                $emailFromMailto = explode('?', $emailFromMailto)[0];
                                if (filter_var($emailFromMailto, FILTER_VALIDATE_EMAIL)) {
                                    $tempEmails[] = $emailFromMailto;
                                    logMessage("Znaleziono email w linku mailto: $emailFromMailto");
                                }
                            } else {
                                logMessage("Sprawdzam stronę kontaktową: $contactLink");
                                $contactHtml = getWebsiteContent($contactLink);
                                
                                if (!empty($contactHtml)) {
                                    $contactEmailsFound = findEmails($contactHtml);
                                    if (!empty($contactEmailsFound)) {
                                        $tempEmails = array_merge($tempEmails, $contactEmailsFound);
                                    }
                                }
                            }
                        }
                        // Dodaj unikalne emaile z linków kontaktowych do głównej tablicy $emails
                        if (!empty($tempEmails)) {
                            $emails = array_merge($emails, array_unique($tempEmails));
                        }
                    }
                    // Na końcu, upewnij się, że wszystkie zebrane emaile (z głównej strony i podstron) są unikalne
                    $emails = array_unique($emails);
                    if (!empty($emails)) {
                        $email = $emails[0]; // Bierzemy pierwszy znaleziony
                    }
                }
                
                // Aktualizacja rekordu
                if ($email) {
                    $cleanedEmail = cleanString($email); // Oczyszczanie emaila
                    logMessage("Znaleziono adres email: $cleanedEmail dla ID: $id");
                    $updateStmt->bind_param('si', $cleanedEmail, $id);
                    if ($updateStmt->execute()) {
                        $foundEmails++;
                        logMessage("Zaktualizowano rekord ID: $id z adresem email: $cleanedEmail");
                    } else {
                        logMessage("Błąd podczas aktualizacji rekordu ID: $id : " . $conn->error);
                        $errors++;
                        // Oznacz jako sprawdzony nawet jeśli aktualizacja emaila się nie udała, aby nie próbować w nieskończoność
                        $markCheckedStmt->bind_param('i', $id);
                        $markCheckedStmt->execute();
                    }
                } else {
                    logMessage("Nie znaleziono adresu email dla ID: $id ($website)");
                    $markCheckedStmt->bind_param('i', $id);
                    $markCheckedStmt->execute();
                }
                
            } catch (Exception $e) {
                logMessage("Błąd podczas przetwarzania ID: $id ($website): " . $e->getMessage());
                $errors++;
                // Oznacz jako sprawdzony w przypadku błędu
                $markCheckedStmt->bind_param('i', $id);
                $markCheckedStmt->execute();
            }
        }
        
        $updateStmt->close();
        $markCheckedStmt->close();
        $conn->close();
        
        $offset += $batchSizeResult;
        
        // Jeśli $startId był użyty, a przetwarzanie zaczęło się od $offset = 0,
        // musimy upewnić się, że następna paczka nie pominie rekordów.
        // W tym przypadku, $startId jest już uwzględniony w klauzuli WHERE id >= $startId,
        // a $offset jest używany do paginacji wyników spełniających ten warunek.
        // Dlatego nie ma potrzeby specjalnej obsługi offsetu w kontekście startId,
        // poza tym, że zapytanie od początku filtruje po id >= $startId.

    } catch (Exception $e) {
        logMessage("Krytyczny błąd podczas przetwarzania paczki: " . $e->getMessage());
        if (isset($conn) && $conn->ping()) { // Sprawdź, czy połączenie wciąż istnieje
            $conn->close();
        }
        $errors++;
        // Poczekaj chwilę przed ponowną próbą, aby uniknąć szybkiego zapętlenia w przypadku problemów z bazą
        sleep(5); 
    }
}

logMessage("Zakończono wyszukiwanie adresów email");
logMessage("Przetworzono rekordów: $processedRecords");
logMessage("Znaleziono adresów email: $foundEmails");
logMessage("Liczba błędów: $errors");

while (ob_get_level() > 0) {
    ob_end_flush();
}
?> 