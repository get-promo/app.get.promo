# System Leadów - Get Promo
## HTML + Laravel + Bootstrap 5 + jQuery

Kompletny system zarządzania leadami z integracją Serper API (Google Places).

---

## 📋 Co zostało stworzone:

### Backend (PHP/Laravel):
1. ✅ **Migracja bazy danych** - `database_migrations_create_leads_table.php`
2. ✅ **Model Lead** - `app_Models_Lead.php`
3. ✅ **LeadController** - `app_Http_Controllers_LeadController.php`
4. ✅ **Routes** - `routes_web_LEADS_ROUTES.php`
5. ✅ **Konfiguracja Serper** - `config_services_SERPER.php`

### Frontend (Blade/Bootstrap/jQuery):
1. ✅ **Lista leadów** - `resources_views_leads_index.blade.php`
2. ✅ **Formularz dodawania** - `resources_views_leads_create.blade.php` (z jQuery autocomplete)
3. ✅ **Szczegóły leada** - `resources_views_leads_show.blade.php`
4. ✅ **Edycja leada** - `resources_views_leads_edit.blade.php`
5. ✅ **Menu** - `resources_menu_verticalMenu_LEADS.json`

---

## 🚀 INSTRUKCJA INSTALACJI:

### KROK 1: Skopiuj pliki ze starter-kit

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo

# Skopiuj wszystkie pliki ze starter-kit
cp -r theme/html-laravel-version/Bootstrap5/vite/starter-kit/* .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.env.example .
cp theme/html-laravel-version/Bootstrap5/vite/starter-kit/.gitignore .
```

**LUB przez Finder:**
1. Otwórz folder `theme/html-laravel-version/Bootstrap5/vite/starter-kit/`
2. Zaznacz wszystkie pliki (CMD+A)
3. Skopiuj (CMD+C)
4. Wklej do głównego katalogu projektu (CMD+V)

---

### KROK 2: Przenieś stworzone pliki do odpowiednich katalogów

```bash
# Migracja
mv database_migrations_create_leads_table.php database/migrations/2024_10_10_172500_create_leads_table.php

# Model
mkdir -p app/Models
mv app_Models_Lead.php app/Models/Lead.php

# Controller
mkdir -p app/Http/Controllers
mv app_Http_Controllers_LeadController.php app/Http/Controllers/LeadController.php

# Views
mkdir -p resources/views/content/leads
mv resources_views_leads_index.blade.php resources/views/content/leads/index.blade.php
mv resources_views_leads_create.blade.php resources/views/content/leads/create.blade.php
mv resources_views_leads_show.blade.php resources/views/content/leads/show.blade.php
mv resources_views_leads_edit.blade.php resources/views/content/leads/edit.blade.php
```

---

### KROK 3: Edytuj pliki konfiguracyjne

#### A. Dodaj routes do `routes/web.php`:
Otwórz plik `routes/web.php` i dodaj na końcu:

```php
use App\Http\Controllers\LeadController;

// Leads routes
Route::middleware(['web'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    
    // AJAX endpoint for Serper search
    Route::post('/api/leads/search-places', [LeadController::class, 'searchPlaces'])->name('leads.search-places');
});
```

#### B. Dodaj konfigurację Serper do `config/services.php`:
Otwórz plik `config/services.php` i dodaj:

```php
'serper' => [
    'api_key' => env('SERPER_API_KEY'),
],
```

#### C. Dodaj pozycję Leads w menu `resources/menu/verticalMenu.json`:
Otwórz plik i dodaj po dashboard:

```json
{
  "url": "/leads",
  "name": "Leady",
  "icon": "menu-icon tf-icons bx bx-user",
  "slug": "leads"
}
```

---

### KROK 4: Skonfiguruj .env

Skopiuj `.env.example` do `.env`:
```bash
cp .env.example .env
```

Następnie edytuj `.env` i zmień/dodaj:

```
APP_NAME="Get Promo"
APP_URL=http://app.get.promo.local

DB_CONNECTION=mysql
DB_HOST=162.55.95.151
DB_PORT=3306
DB_DATABASE=admin_appgetpromo
DB_USERNAME=admin_appgetpromo
DB_PASSWORD=Fgdd3YFV9NDRdgS5tLc5

SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad
```

---

### KROK 5: Zainstaluj zależności

```bash
# Zainstaluj zależności PHP
composer install

# Zainstaluj zależności JavaScript
npm install

# Wygeneruj klucz aplikacji
php artisan key:generate
```

---

### KROK 6: Uruchom migracje

```bash
php artisan migrate
```

To utworzy tabelę `leads` w bazie danych.

---

### KROK 7: Uruchom aplikację

**W MAMP:**
1. Ustaw Document Root na: `/Users/maciejkostecki/Documents/WORKSPACE/app.get.promo/public`
2. Ustaw wirtualny host: `app.get.promo.local`

**Kompilacja assets:**
```bash
npm run dev
```

**Otwórz w przeglądarce:**
```
http://app.get.promo.local
```

---

## 🎯 Funkcjonalności:

### ✅ Lista leadów (`/leads`):
- Tabela z wszystkimi leadami
- Wyszukiwanie po nazwie, adresie, osobie kontaktowej
- Filtrowanie po statusie (nowy, skontaktowany, zakwalifikowany, przekonwertowany, odrzucony)
- Paginacja
- Akcje: zobacz, edytuj, usuń

### ✅ Dodawanie leada (`/leads/create`):
- **Wyszukiwarka z autocomplete przez Serper API**
  - Wpisz minimum 2 znaki
  - Automatyczne wyszukiwanie z debouncing (500ms)
  - Dropdown z wynikami z Google Places
  - Kliknij, aby wybrać lokal
- **Formularz danych kontaktowych:**
  - Imię i nazwisko
  - Stanowisko (właściciel/manager/sekretarka/pracownik)
  - Numer telefonu (prywatny)
  - Email
  - Notatki
- **Walidacja** po stronie serwera i klienta
- **Zapisywanie pełnej odpowiedzi z Serper** w formacie JSON

### ✅ Szczegóły leada (`/leads/{id}`):
- Pełne informacje o lokalu
- Dane osoby kontaktowej
- Status
- Notatki
- Link do Google Maps

### ✅ Edycja leada (`/leads/{id}/edit`):
- Edycja danych kontaktowych
- Zmiana statusu
- Edycja notatek
- Usunięcie leada

---

## 🔧 Technologie:

- **Backend:** Laravel 11 + PHP 8.2
- **Frontend:** Blade Templates + Bootstrap 5 + jQuery
- **Database:** MySQL (zdalna baza)
- **API:** Serper.dev (Google Places)
- **Build:** Vite (tylko do kompilacji SCSS, nie wymaga skomplikowanego frontendu)

---

## 📊 Struktura bazy danych:

Tabela `leads`:
- **Dane z Serper:** title, address, latitude, longitude, rating, rating_count, price_level, category, phone_number, website, cid
- **JSON:** serper_response (pełna odpowiedź z API)
- **Dane kontaktowe:** contact_first_name, contact_last_name, contact_position, contact_phone, contact_email
- **Metadata:** status, notes, created_at, updated_at, deleted_at

---

## 🎉 Gotowe!

System leadów jest w pełni funkcjonalny i gotowy do użycia!

**Testuj:**
1. Przejdź do `/leads`
2. Kliknij "Dodaj nowego leada"
3. Wpisz "Deja Vu Pub Poznań"
4. Wybierz lokal z listy
5. Wypełnij dane kontaktowe
6. Zapisz!

