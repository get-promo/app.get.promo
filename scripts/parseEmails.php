<?php 
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $string = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $string);
    
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
        $href = $link->getAttribute('href');
        $text = strtolower($link->textContent);
        
        // Sprawdź, czy link zawiera "kontakt" lub "contact"
        if (strpos(strtolower($href), 'kontakt') !== false || 
            strpos(strtolower($href), 'contact') !== false || 
            strpos($text, 'kontakt') !== false || 
            strpos($text, 'contact') !== false) {
            
            // Konwertuj względny URL na absolutny
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    // Względny URL zaczynający się od /
                    $parsedUrl = parse_url($baseUrl);
                    $baseUrlRoot = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    $href = $baseUrlRoot . $href;
                } else {
                    // Względny URL bez / na początku
                    $href = rtrim($baseUrl, '/') . '/' . $href;
                }
            }
            
            $contactLinks[] = $href;
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

// Główna logika skryptu
logMessage("Rozpoczynam wyszukiwanie adresów email dla rekordów w bazie danych");

// Pobierz ostatnie przetworzone ID
$conn = createDbConnection();
$lastProcessedIdResult = $conn->query("SELECT MAX(id) as last_id FROM `lead` WHERE mail_checked = 1");
$lastProcessedId = 0; // Domyślna wartość jeśli nie ma jeszcze przetworzonych rekordów

if ($lastProcessedIdResult && $row = $lastProcessedIdResult->fetch_assoc()) {
    $lastProcessedId = (int)$row['last_id'];
}
$conn->close();

logMessage("Ostatnie przetworzone ID: " . $lastProcessedId);

$processedRecords = 0;
$foundEmails = 0;
$errors = 0;
$offset = 0;

while (true) {
    try {
        // Utworzenie nowego połączenia dla każdej paczki
        $conn = createDbConnection();
        
        // Pobierz kolejną paczkę rekordów, zaczynając od ostatniego przetworzonego ID
        $sql = "SELECT id, website FROM `lead` 
                WHERE mail_checked = 0 
                AND website IS NOT NULL 
                AND website != '' 
                AND id > " . $lastProcessedId . " 
                ORDER BY id ASC
                LIMIT " . BATCH_SIZE . " OFFSET " . $offset;
                
        $result = $conn->query($sql);
        
        // Jeśli nie ma więcej rekordów, kończymy
        if ($result->num_rows == 0) {
            $conn->close();
            break;
        }
        
        $batchSize = $result->num_rows;
        logMessage("Przetwarzam paczkę $offset - " . ($offset + $batchSize));
        
        // Przygotuj zapytania
        $updateStmt = $conn->prepare("UPDATE `lead` SET email = ?, mail_checked = 1 WHERE id = ?");
        $markCheckedStmt = $conn->prepare("UPDATE `lead` SET mail_checked = 1 WHERE id = ?");
        
        // Rozpocznij transakcję dla całej paczki
        $conn->begin_transaction();
        
        // Przetwarzanie rekordów w paczce
        while ($row = $result->fetch_assoc()) {
            $processedRecords++;
            $id = $row['id'];
            $website = $row['website'];
            
            logMessage("[$processedRecords] Przetwarzam: $website");
            
            // Sprawdź czy to nie jest strona social media
            if (stripos($website, 'facebook.com') !== false || 
                stripos($website, 'instagram.com') !== false) {
                logMessage("Pomijam stronę social media: $website");
                $markCheckedStmt->bind_param('i', $id);
                $markCheckedStmt->execute();
                continue;
            }
            
            try {
                // Pobierz zawartość strony głównej
                $html = getWebsiteContent($website);
                
                if (empty($html)) {
                    logMessage("Nie udało się pobrać strony: $website");
                    $markCheckedStmt->bind_param('i', $id);
                    $markCheckedStmt->execute();
                    $errors++;
                    continue;
                }
                
                // Wyszukaj adresy email na stronie głównej
                $emails = findEmails($html);
                
                // Jeśli nie znaleziono emaili na stronie głównej, szukaj na stronach kontaktowych
                if (empty($emails)) {
                    logMessage("Brak adresów email na stronie głównej, szukam na stronach kontaktowych");
                    
                    $contactLinks = findContactLinks($html, $website);
                    
                    foreach ($contactLinks as $contactLink) {
                        logMessage("Sprawdzam stronę kontaktową: $contactLink");
                        
                        $contactHtml = getWebsiteContent($contactLink);
                        
                        if (!empty($contactHtml)) {
                            $contactEmails = findEmails($contactHtml);
                            
                            if (!empty($contactEmails)) {
                                $emails = array_merge($emails, $contactEmails);
                                break;
                            }
                        }
                    }
                }
                
                // Usuń duplikaty i przetwórz znalezione emaile
                $emails = array_unique($emails);
                
                if (!empty($emails)) {
                    $email = $emails[0];
                    logMessage("Znaleziono adres email: $email");
                    
                    $updateStmt->bind_param('si', $email, $id);
                    
                    if ($updateStmt->execute()) {
                        $foundEmails++;
                        logMessage("Zaktualizowano rekord ID: $id z adresem email: $email");
                    } else {
                        logMessage("Błąd podczas aktualizacji rekordu: " . $conn->error);
                        $errors++;
                    }
                } else {
                    logMessage("Nie znaleziono adresu email dla: $website");
                    $markCheckedStmt->bind_param('i', $id);
                    $markCheckedStmt->execute();
                }
            } catch (Exception $e) {
                logMessage("Błąd podczas przetwarzania $website: " . $e->getMessage());
                $errors++;
                
                $markCheckedStmt->bind_param('i', $id);
                $markCheckedStmt->execute();
            }
        }
        
        // Zatwierdź transakcję
        $conn->commit();
        
        // Zamknij zapytania i połączenie po przetworzeniu paczki
        $updateStmt->close();
        $markCheckedStmt->close();
        $conn->close();
        
        // Zwiększ offset dla następnej paczki
        $offset += $batchSize;
        
    } catch (Exception $e) {
        logMessage("Krytyczny błąd podczas przetwarzania paczki: " . $e->getMessage());
        if (isset($conn)) {
            $conn->rollback();
            $conn->close();
        }
        $errors++;
    }
}

logMessage("Zakończono wyszukiwanie adresów email");
logMessage("Przetworzono rekordów: $processedRecords");
logMessage("Znaleziono adresów email: $foundEmails");
logMessage("Liczba błędów: $errors");

// Upewnij się, że wszystkie bufory zostały opróżnione na końcu
while (ob_get_level() > 0) {
    ob_end_flush();
}
?> 