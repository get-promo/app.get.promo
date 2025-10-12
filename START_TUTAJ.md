# 🚀 START TUTAJ - System Leadów Get Promo

## HTML + Laravel + Bootstrap 5 (bez Vue, bez TypeScript)

---

## ⚡ SZYBKI START (3 minuty):

### 1️⃣ Skopiuj pliki ze starter-kit:

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
cp -r theme/html-laravel-version/Bootstrap5/vite/starter-kit/* .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.env.example .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.gitignore .
```

### 2️⃣ Uruchom skrypt instalacyjny:

```bash
chmod +x install_leads_system.sh
./install_leads_system.sh
```

### 3️⃣ Dodaj ręcznie (skrypt pokaże co):

- **Routes** do `routes/web.php` (zobacz: `routes_web_LEADS_ROUTES.php`)
- **Config Serper** do `config/services.php` (zobacz: `config_services_SERPER.php`)
- **Menu Leads** do `resources/menu/verticalMenu.json` (zobacz: `resources_menu_verticalMenu_LEADS.json`)

### 4️⃣ Zainstaluj zależności:

```bash
composer install
npm install
php artisan key:generate
```

### 5️⃣ Skonfiguruj .env:

```bash
cp .env.example .env
```

Edytuj `.env` - zmień DB i dodaj na końcu:
```
SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad
```

### 6️⃣ Uruchom migracje:

```bash
php artisan migrate
```

### 7️⃣ Uruchom aplikację:

W MAMP ustaw document root na `/Users/maciejkostecki/Documents/WORKSPACE/app.get.promo/public`

```bash
npm run dev
```

Otwórz: `http://app.get.promo.local`

---

## ✅ CO ZOSTAŁO STWORZONE:

### Backend (8 plików):
1. ✅ Migracja bazy - `database_migrations_create_leads_table.php`
2. ✅ Model Lead - `app_Models_Lead.php`
3. ✅ LeadController - `app_Http_Controllers_LeadController.php`
4. ✅ Routes - `routes_web_LEADS_ROUTES.php`
5. ✅ Config Serper - `config_services_SERPER.php`
6. ✅ composer.json (zależności PHP)
7. ✅ package.json (zależności JS - minimalne!)
8. ✅ artisan (CLI Laravel)

### Frontend (5 plików Blade + Bootstrap + jQuery):
1. ✅ Lista leadów - `resources_views_leads_index.blade.php`
2. ✅ Dodaj leada z autocomplete - `resources_views_leads_create.blade.php`
3. ✅ Szczegóły leada - `resources_views_leads_show.blade.php`
4. ✅ Edytuj leada - `resources_views_leads_edit.blade.php`
5. ✅ Menu - `resources_menu_verticalMenu_LEADS.json`

### Dokumentacja:
1. ✅ README_SYSTEM_LEADOW.md - pełna dokumentacja
2. ✅ INSTRUKCJA_INSTALACJI.md - krótka instrukcja
3. ✅ install_leads_system.sh - automatyczny skrypt
4. ✅ START_TUTAJ.md - ten plik

---

## 🎯 FUNKCJONALNOŚCI:

### ✨ Wyszukiwarka z Serper API:
- Wpisz nazwę lokalu (np. "Deja Vu Pub Poznań")
- Automatyczne wyszukiwanie z debouncing
- Dropdown z wynikami z Google Places
- Jeden klik - wszystkie dane lokalu wypełnione!

### 📊 Zarządzanie leadami:
- Lista z wyszukiwaniem i filtrowaniem
- Dodawanie leadów z danymi z Google Places
- Edycja danych kontaktowych
- Zmiana statusu (nowy → skontaktowany → zakwalifikowany → przekonwertowany)
- Usuwanie leadów

### 💾 Baza danych:
- Wszystkie pola z Serper API
- Pełna odpowiedź JSON z API
- Dane kontaktowe (imię, nazwisko, stanowisko, telefon prywatny, email)
- Statusy i notatki

---

## 🔧 TECHNOLOGIE:

- ✅ **Laravel 11** - PHP Framework
- ✅ **Bootstrap 5** - UI Framework (NIE Vuetify!)
- ✅ **jQuery** - AJAX i DOM manipulation (NIE Vue!)
- ✅ **Blade Templates** - Templating (NIE .vue files!)
- ✅ **Vite** - TYLKO do kompilacji SCSS (nie TypeScript!)
- ✅ **MySQL** - Baza danych (zdalna)
- ✅ **Serper.dev** - Google Places API

---

## 📝 RÓŻNICA: HTML vs Vue

| Aspekt | Wersja HTML (TA!) | Wersja Vue (stara) |
|--------|-------------------|-------------------|
| **Kompilacja** | Tylko SCSS → CSS | TS → JS, Vue → JS, SCSS → CSS |
| **npm** | Minimalne użycie | Konieczne do wszystkiego |
| **Pliki** | .blade.php (HTML+PHP) | .vue (SFC) + .ts |
| **JavaScript** | jQuery w <script> | TypeScript modules |
| **Łatwość** | ⭐⭐⭐⭐⭐ Bardzo łatwe | ⭐⭐ Trudniejsze |
| **Build time** | ~5 sekund | ~30 sekund |

---

## 🎉 GOTOWE!

Po wykonaniu powyższych kroków, system leadów będzie w pełni funkcjonalny!

**Test:**
1. Otwórz `http://app.get.promo.local/leads`
2. Kliknij "Dodaj nowego leada"
3. Wpisz "Deja Vu Pub"
4. Zobacz autocomplete w akcji!
5. Wybierz lokal, wypełnij dane, zapisz!

---

## 📞 Pomocy?

Sprawdź:
- `README_SYSTEM_LEADOW.md` - pełna dokumentacja
- `install_leads_system.sh` - pokaże co jest nie tak
- Pliki `*_LEADS_*.php` - gotowe fragmenty do wklejenia

---

**Miłej pracy z systemem leadów! 🚀**

