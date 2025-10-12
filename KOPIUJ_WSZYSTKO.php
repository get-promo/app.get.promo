<?php

echo "========================================\n";
echo "  KOPIOWANIE PLIKÓW ZE STARTER-KIT\n";
echo "========================================\n\n";

$source = __DIR__ . '/theme/html-laravel-version/Bootstrap5/vite/starter-kit';
$dest = __DIR__;

if (!is_dir($source)) {
    die("❌ Błąd: Katalog starter-kit nie istnieje!\n");
}

echo "Źródło: $source\n";
echo "Cel: $dest\n\n";

// Funkcja rekurencyjnego kopiowania
function copyDirectory($src, $dst, $skip = []) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    
    $count = 0;
    
    while (false !== ($file = readdir($dir))) {
        if ($file == '.' || $file == '..') continue;
        
        // Skip certain directories
        if (in_array($file, $skip)) {
            echo "⏭️  Pomijam: $file\n";
            continue;
        }
        
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        
        if (is_dir($srcPath)) {
            copyDirectory($srcPath, $dstPath, $skip);
        } else {
            // Skip if file already exists and is not empty (don't overwrite)
            if (file_exists($dstPath) && filesize($dstPath) > 0) {
                // echo "  Istnieje: $file\n";
                continue;
            }
            
            if (copy($srcPath, $dstPath)) {
                $count++;
                if ($count % 50 == 0) {
                    echo "  ... skopiowano $count plików\n";
                }
            }
        }
    }
    
    closedir($dir);
    return $count;
}

echo "Kopiowanie katalogów...\n\n";

// Lista katalogów do skopiowania
$directories = [
    'app',
    'config',
    'database',
    'lang',
    'resources',
    'storage',
    'tests',
    'docker',
];

$totalFiles = 0;

foreach ($directories as $dir) {
    echo "📁 Kopiuję: $dir/\n";
    $count = copyDirectory("$source/$dir", "$dest/$dir", ['node_modules', 'vendor']);
    $totalFiles += $count;
    echo "   ✓ Skopiowano $count plików\n\n";
}

// Kopiuj pliki z public/assets (tylko jeśli nie istnieją)
echo "📁 Kopiuję: public/assets/\n";
$count = copyDirectory("$source/public/assets", "$dest/public/assets", []);
$totalFiles += $count;
echo "   ✓ Skopiowano $count plików\n\n";

// Kopiuj pojedyncze pliki root
$rootFiles = [
    '.gitignore',
    '.env.example',
    'phpunit.xml',
    'vite.config.js',
    'docker-compose.yml',
];

echo "📄 Kopiuję pliki root...\n";
foreach ($rootFiles as $file) {
    $srcPath = "$source/$file";
    $dstPath = "$dest/$file";
    
    if (file_exists($srcPath)) {
        if (!file_exists($dstPath) || filesize($dstPath) == 0) {
            if (copy($srcPath, $dstPath)) {
                echo "   ✓ $file\n";
                $totalFiles++;
            }
        }
    }
}

echo "\n========================================\n";
echo "✅ GOTOWE!\n";
echo "========================================\n\n";
echo "Skopiowano łącznie: $totalFiles plików\n\n";
echo "NASTĘPNE KROKI:\n\n";
echo "1. Przenieś pliki systemu leadów:\n";
echo "   php PRZENIES_LEADY.php\n\n";
echo "2. Skonfiguruj .env:\n";
echo "   cp .env.example .env\n";
echo "   (Edytuj .env - ustaw bazę danych i dodaj SERPER_API_KEY)\n\n";
echo "3. Zainstaluj zależności:\n";
echo "   composer install\n";
echo "   npm install\n\n";
echo "4. Wygeneruj klucz:\n";
echo "   php artisan key:generate\n\n";
echo "5. Uruchom migracje:\n";
echo "   php artisan migrate\n\n";
echo "6. Kompiluj assets:\n";
echo "   npm run dev\n\n";
echo "7. Odśwież przeglądarkę: http://app.get.promo.local\n\n";



