<?php

echo "========================================\n";
echo "  INSTALACJA SYSTEMU LEADÓW\n";
echo "========================================\n\n";

$files = [
    // Migracja
    'database_migrations_create_leads_table.php' => 'database/migrations/2024_10_10_172500_create_leads_table.php',
    
    // Model
    'app_Models_Lead.php' => 'app/Models/Lead.php',
    
    // Controller
    'app_Http_Controllers_LeadController.php' => 'app/Http/Controllers/LeadController.php',
    
    // Views
    'resources_views_leads_index.blade.php' => 'resources/views/content/leads/index.blade.php',
    'resources_views_leads_create.blade.php' => 'resources/views/content/leads/create.blade.php',
    'resources_views_leads_show.blade.php' => 'resources/views/content/leads/show.blade.php',
    'resources_views_leads_edit.blade.php' => 'resources/views/content/leads/edit.blade.php',
];

$moved = 0;
$skipped = 0;

foreach ($files as $source => $dest) {
    if (!file_exists($source)) {
        echo "⏭️  Brak pliku: $source\n";
        $skipped++;
        continue;
    }
    
    // Utwórz katalog jeśli nie istnieje
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Przenieś plik
    if (rename($source, $dest)) {
        echo "✓ $source → $dest\n";
        $moved++;
    } else {
        echo "❌ Błąd: $source\n";
    }
}

echo "\n========================================\n";
echo "Przeniesiono: $moved plików\n";
echo "Pominięto: $skipped plików\n";
echo "========================================\n\n";

echo "WAŻNE! Musisz RĘCZNIE:\n\n";

// Sprawdź routes/web.php
if (file_exists('routes/web.php')) {
    $webRoutes = file_get_contents('routes/web.php');
    if (strpos($webRoutes, 'LeadController') !== false) {
        echo "✓ Routes dla leadów już są w routes/web.php\n";
    } else {
        echo "❌ DODAJ do routes/web.php (na końcu pliku):\n\n";
        echo file_get_contents('routes_web_LEADS_ROUTES.php');
        echo "\n\n";
    }
}

// Sprawdź config/services.php
if (file_exists('config/services.php')) {
    $services = file_get_contents('config/services.php');
    if (strpos($services, 'serper') !== false) {
        echo "✓ Konfiguracja Serper już jest w config/services.php\n";
    } else {
        echo "❌ DODAJ do config/services.php (przed zamknięciem tablicy):\n\n";
        echo file_get_contents('config_services_SERPER.php');
        echo "\n\n";
    }
}

// Sprawdź menu
if (file_exists('resources/menu/verticalMenu.json')) {
    $menu = file_get_contents('resources/menu/verticalMenu.json');
    if (strpos($menu, 'leads') !== false) {
        echo "✓ Pozycja Leads już jest w menu\n";
    } else {
        echo "❌ DODAJ do resources/menu/verticalMenu.json pozycję Leads\n";
        echo "   (Zobacz: resources_menu_verticalMenu_LEADS.json)\n\n";
    }
}

echo "\n========================================\n";
echo "NASTĘPNE KROKI:\n";
echo "========================================\n\n";
echo "1. Edytuj .env - dodaj:\n";
echo "   SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad\n\n";
echo "2. Uruchom migracje:\n";
echo "   php artisan migrate\n\n";
echo "3. Uruchom aplikację:\n";
echo "   npm run dev\n\n";
echo "4. Otwórz: http://app.get.promo.local/leads\n\n";



