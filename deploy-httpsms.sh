#!/bin/bash

echo "🚀 Wdrażanie httpSMS API..."

# Przejdź do katalogu projektu
cd /home/admin/domains/app.get.promo/private

# Pobierz najnowsze zmiany z Git
echo "📥 Pobieranie najnowszego kodu..."
git pull origin main

# Uruchom migracje
echo "🗄️ Uruchamianie migracji..."
php artisan migrate --force

# Wyczyść cache
echo "🧹 Czyszczenie cache..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Sprawdź czy migracje zostały uruchomione
echo "✅ Sprawdzanie migracji..."
php artisan migrate:status

echo "🎉 Wdrożenie zakończone!"
echo ""
echo "Test endpoint: https://app.get.promo/httpSMS/test"
echo "API base URL: https://app.get.promo/httpSMS/v1"
