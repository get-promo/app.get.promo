#!/bin/bash

echo "========================================"
echo "  SETUP - Get Promo System Leadów"
echo "========================================"
echo ""

cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo

echo "Kopiowanie plików ze starter-kit..."
cp -rv theme/html-laravel-version/Bootstrap5/vite/starter-kit/* .
cp -v theme/html-laravel-version/Bootstrap5/vite/starter-kit/.env.example .
cp -v theme/html-laravel-version/Bootstrap5/vite/starter-kit/.gitignore .

echo ""
echo "✓ Pliki skopiowane!"
echo ""
echo "Uruchamiam skrypt instalacyjny systemu leadów..."
chmod +x install_leads_system.sh
./install_leads_system.sh

echo ""
echo "========================================"
echo "  GOTOWE!"
echo "========================================"
echo ""
echo "NASTĘPNE KROKI:"
echo ""
echo "1. Ustaw w MAMP document root na:"
echo "   /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo/public"
echo ""
echo "2. Edytuj .env (ustaw bazę danych)"
echo ""
echo "3. Uruchom:"
echo "   composer install"
echo "   npm install"
echo "   php artisan key:generate"
echo "   php artisan migrate"
echo "   npm run dev"
echo ""
echo "4. Otwórz: http://app.get.promo.local"
echo ""

