# Instrukcja instalacji Get Promo - System Leadów

## 1. Skopiuj pliki ze starter-kit:

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
cp -r theme/html-laravel-version/Bootstrap5/vite/starter-kit/* .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.env.example .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.gitignore .
```

## 2. Utwórz plik .env:

```bash
cp .env.example .env
```

I edytuj zawartość:
- Zmień `DB_CONNECTION=mysql`
- Zmień `DB_HOST=162.55.95.151`
- Zmień `DB_DATABASE=admin_appgetpromo`
- Zmień `DB_USERNAME=admin_appgetpromo`
- Zmień `DB_PASSWORD=Fgdd3YFV9NDRdgS5tLc5`
- Dodaj na końcu: `SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad`

## 3. Zainstaluj zależności:

```bash
composer install
npm install
```

## 4. Wygeneruj klucz aplikacji:

```bash
php artisan key:generate
```

## 5. Uruchom migracje:

```bash
php artisan migrate
```

## 6. Uruchom aplikację:

W MAMP ustaw document root na `/Users/maciejkostecki/Documents/WORKSPACE/app.get.promo/public`

Oraz:
```bash
npm run dev
```

## 7. Otwórz przeglądarkę:

http://app.get.promo.local

Gotowe! System leadów jest już zaimplementowany.

