#!/bin/bash

echo "Kopiowanie wszystkich niezbędnych plików..."

SOURCE="/Users/maciejkostecki/Documents/WORKSPACE/app.get.promo/theme/html-laravel-version/Bootstrap5/vite/starter-kit"
DEST="/Users/maciejkostecki/Documents/WORKSPACE/app.get.promo"

cd "$DEST"

# Usuń stare pliki które już skopiowaliśmy ręcznie (żeby nie było konfliktów)
# NIE usuwamy theme/!

echo "Kopiowanie katalogów..."

# Katalogi Laravel
cp -r "$SOURCE/app" "$DEST/" 2>/dev/null || echo "app już istnieje"
cp -r "$SOURCE/config" "$DEST/" 2>/dev/null || echo "config już istnieje"
cp -r "$SOURCE/database" "$DEST/" 2>/dev/null || echo "database już istnieje"
cp -r "$SOURCE/lang" "$DEST/" 2>/dev/null || echo "lang już istnieje"
cp -r "$SOURCE/routes" "$DEST/" 2>/dev/null || echo "routes już istnieje"
cp -r "$SOURCE/storage" "$DEST/" 2>/dev/null || echo "storage już istnieje"
cp -r "$SOURCE/tests" "$DEST/" 2>/dev/null || echo "tests już istnieje"
cp -r "$SOURCE/resources" "$DEST/" 2>/dev/null || echo "resources już istnieje"

# Katalogi public (assets)
cp -r "$SOURCE/public/assets" "$DEST/public/" 2>/dev/null || echo "assets już istnieją"

# Pliki root
cp "$SOURCE/.gitignore" "$DEST/" 2>/dev/null || echo ".gitignore już istnieje"
cp "$SOURCE/.env.example" "$DEST/" 2>/dev/null || echo ".env.example już istnieje"
cp "$SOURCE/phpunit.xml" "$DEST/" 2>/dev/null || echo "phpunit.xml już istnieje"
cp "$SOURCE/vite.config.js" "$DEST/" 2>/dev/null || echo "vite.config.js już istnieje"
cp "$SOURCE/README.md" "$DEST/README_STARTER_KIT.md" 2>/dev/null || echo "README już istnieje"

# Dodatkowe pliki
cp "$SOURCE/docker-compose.yml" "$DEST/" 2>/dev/null || echo "docker-compose.yml już istnieje"

echo ""
echo "✓ Pliki skopiowane!"
echo ""
echo "TERAZ URUCHOM:"
echo "  cd $DEST"
echo "  composer install"
echo "  cp .env.example .env"
echo "  php artisan key:generate"
echo ""
echo "Następnie odśwież stronę w przeglądarce!"



