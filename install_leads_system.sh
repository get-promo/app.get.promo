#!/bin/bash

echo "========================================"
echo "  Instalacja Systemu Leadów - Get Promo"
echo "========================================"
echo ""

# Kolorki
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Funkcja sprawdzająca czy plik istnieje
check_file() {
    if [ ! -f "$1" ]; then
        echo -e "${RED}✗ Błąd: Plik $1 nie istnieje!${NC}"
        return 1
    fi
    echo -e "${GREEN}✓ Plik $1 istnieje${NC}"
    return 0
}

echo "Krok 1: Przenoszenie plików do odpowiednich katalogów..."
echo ""

# Migracja
if check_file "database_migrations_create_leads_table.php"; then
    mv database_migrations_create_leads_table.php database/migrations/2024_10_10_172500_create_leads_table.php
    echo -e "${GREEN}✓ Migracja przeniesiona${NC}"
fi

# Model
if check_file "app_Models_Lead.php"; then
    mkdir -p app/Models
    mv app_Models_Lead.php app/Models/Lead.php
    echo -e "${GREEN}✓ Model przeniesiony${NC}"
fi

# Controller
if check_file "app_Http_Controllers_LeadController.php"; then
    mkdir -p app/Http/Controllers
    mv app_Http_Controllers_LeadController.php app/Http/Controllers/LeadController.php
    echo -e "${GREEN}✓ Controller przeniesiony${NC}"
fi

# Views
mkdir -p resources/views/content/leads

if check_file "resources_views_leads_index.blade.php"; then
    mv resources_views_leads_index.blade.php resources/views/content/leads/index.blade.php
    echo -e "${GREEN}✓ View index przeniesiony${NC}"
fi

if check_file "resources_views_leads_create.blade.php"; then
    mv resources_views_leads_create.blade.php resources/views/content/leads/create.blade.php
    echo -e "${GREEN}✓ View create przeniesiony${NC}"
fi

if check_file "resources_views_leads_show.blade.php"; then
    mv resources_views_leads_show.blade.php resources/views/content/leads/show.blade.php
    echo -e "${GREEN}✓ View show przeniesiony${NC}"
fi

if check_file "resources_views_leads_edit.blade.php"; then
    mv resources_views_leads_edit.blade.php resources/views/content/leads/edit.blade.php
    echo -e "${GREEN}✓ View edit przeniesiony${NC}"
fi

echo ""
echo "Krok 2: Aktualizacja plików konfiguracyjnych..."
echo ""

# Sprawdź czy routes/web.php istnieje
if [ -f "routes/web.php" ]; then
    # Sprawdź czy routes już nie zostały dodane
    if grep -q "LeadController" routes/web.php; then
        echo -e "${YELLOW}! Routes dla leadów już istnieją w web.php${NC}"
    else
        echo -e "${YELLOW}! Musisz ręcznie dodać routes do routes/web.php${NC}"
        echo "  Zobacz: routes_web_LEADS_ROUTES.php"
    fi
else
    echo -e "${RED}✗ Plik routes/web.php nie istnieje!${NC}"
    echo "  Upewnij się, że skopiowałeś pliki ze starter-kit"
fi

# Sprawdź czy config/services.php istnieje
if [ -f "config/services.php" ]; then
    if grep -q "serper" config/services.php; then
        echo -e "${YELLOW}! Konfiguracja Serper już istnieje w services.php${NC}"
    else
        echo -e "${YELLOW}! Musisz ręcznie dodać konfigurację Serper do config/services.php${NC}"
        echo "  Zobacz: config_services_SERPER.php"
    fi
else
    echo -e "${RED}✗ Plik config/services.php nie istnieje!${NC}"
fi

# Sprawdź menu
if [ -f "resources/menu/verticalMenu.json" ]; then
    if grep -q "leads" resources/menu/verticalMenu.json; then
        echo -e "${YELLOW}! Pozycja Leads już istnieje w menu${NC}"
    else
        echo -e "${YELLOW}! Musisz ręcznie dodać pozycję Leads do resources/menu/verticalMenu.json${NC}"
        echo "  Zobacz: resources_menu_verticalMenu_LEADS.json"
    fi
else
    echo -e "${RED}✗ Plik resources/menu/verticalMenu.json nie istnieje!${NC}"
fi

echo ""
echo "Krok 3: Sprawdzanie zależności..."
echo ""

# Sprawdź czy composer.json istnieje
if [ -f "composer.json" ]; then
    echo -e "${GREEN}✓ composer.json istnieje${NC}"
    
    # Sprawdź czy vendor istnieje
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}! Folder vendor nie istnieje. Uruchom: composer install${NC}"
    else
        echo -e "${GREEN}✓ Zależności Composer zainstalowane${NC}"
    fi
else
    echo -e "${RED}✗ composer.json nie istnieje!${NC}"
    echo "  Skopiuj pliki ze starter-kit!"
fi

# Sprawdź czy package.json istnieje
if [ -f "package.json" ]; then
    echo -e "${GREEN}✓ package.json istnieje${NC}"
    
    # Sprawdź czy node_modules istnieje
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}! Folder node_modules nie istnieje. Uruchom: npm install${NC}"
    else
        echo -e "${GREEN}✓ Zależności NPM zainstalowane${NC}"
    fi
else
    echo -e "${RED}✗ package.json nie istnieje!${NC}"
fi

# Sprawdź .env
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}! Plik .env nie istnieje. Skopiuj: cp .env.example .env${NC}"
else
    echo -e "${GREEN}✓ Plik .env istnieje${NC}"
    
    # Sprawdź czy SERPER_API_KEY jest w .env
    if grep -q "SERPER_API_KEY" .env; then
        echo -e "${GREEN}✓ SERPER_API_KEY jest w .env${NC}"
    else
        echo -e "${YELLOW}! SERPER_API_KEY nie znaleziony w .env. Dodaj:${NC}"
        echo "  SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad"
    fi
fi

echo ""
echo "========================================"
echo "  Podsumowanie"
echo "========================================"
echo ""
echo "Pliki zostały przeniesione do odpowiednich katalogów."
echo ""
echo -e "${YELLOW}NASTĘPNE KROKI:${NC}"
echo ""
echo "1. Dodaj routes do routes/web.php (zobacz: routes_web_LEADS_ROUTES.php)"
echo "2. Dodaj config Serper do config/services.php (zobacz: config_services_SERPER.php)"
echo "3. Dodaj pozycję Leads w menu (zobacz: resources_menu_verticalMenu_LEADS.json)"
echo "4. Sprawdź plik .env (baza danych i SERPER_API_KEY)"
echo "5. Uruchom migracje: php artisan migrate"
echo "6. Uruchom aplikację: npm run dev"
echo ""
echo -e "${GREEN}Gotowe! System leadów został zainstalowany.${NC}"
echo ""

