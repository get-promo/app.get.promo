# 🐛 Debug Facebook In-App Browser - Podsumowanie zmian

## Co zostało zrobione:

### 1. ✅ CSRF już wyłączony
W `bootstrap/app.php` linia 20 - CSRF jest wyłączony dla `api/public/*`

### 2. 📝 Dodane szczegółowe logowanie

#### Backend (`app/Http/Controllers/LandingController.php`):
- Loguje każdy request z pełnymi szczegółami
- Wykrywa czy to Facebook/Instagram browser
- Loguje session ID, headers, IP
- Loguje dokładne błędy z stack trace

#### Frontend (`resources/views/content/landing/index.blade.php`):
- Wykrywa Facebook/Instagram browser przy ładowaniu strony
- Loguje wszystkie kroki wyszukiwania do console.log
- Pokazuje szczegółowe błędy użytkownikowi (tymczasowo, do debugowania)

### 3. 🧪 Nowe narzędzia do debugowania

#### A) Strona testowa: `/sprawdz-wizytowke/debug`
URL: `https://app.get.promo/sprawdz-wizytowke/debug`

Co robi:
- Pokazuje informacje o przeglądarce
- Testuje czy cookies/localStorage działają
- Ma przyciski do testowania API
- Pokazuje logi w czasie rzeczywistym

**Jak używać:**
1. Otwórz link w Facebook browser (kliknij na post z FB)
2. Zobacz co pokazuje
3. Kliknij "Test Endpoint" i "Test Search API"
4. Zrób screenshot i zobacz wyniki

#### B) Debug endpoint: `/api/public/debug-request`
Zwraca wszystkie informacje o requeście (GET lub POST)

Test z terminala:
```bash
curl https://app.get.promo/api/public/debug-request
```

#### C) Skrypt do logów: `./watch-logs.sh`
Uruchamia tail -f na logach Laravel z filtrowaniem LANDING

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
./watch-logs.sh
```

### 4. 📖 Dokumentacja

- `JAK_DEBUGOWAC_FACEBOOK_INAPP.md` - Szczegółowa instrukcja
- Ten plik - Szybkie podsumowanie

---

## 🚀 Jak teraz debugować:

### OPCJA 1: Najszybsza (zalecana)

1. Wyślij sobie link na Messenger:
   ```
   https://app.get.promo/sprawdz-wizytowke/debug
   ```

2. Kliknij link (otworzy się w Facebook browser)

3. Kliknij "Test Search API"

4. Zobacz wynik i zrób screenshot

### OPCJA 2: Sprawdź logi serwera

1. Na serwerze:
   ```bash
   cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
   ./watch-logs.sh
   ```

2. Na telefonie otwórz landing w Facebook browser

3. Spróbuj wyszukać "test"

4. Zobacz co pojawia się w logach

### OPCJA 3: Sprawdź normalny landing z debugiem

1. Otwórz:
   ```
   https://app.get.promo/sprawdz-wizytowke
   ```

2. Otwórz developer console (jeśli to możliwe na telefonie)

3. Wpisz minimum 4 znaki

4. Zobacz logi w konsoli - teraz są BARDZO szczegółowe:
   ```
   [DEBUG] CSRF Token: EXISTS
   [DEBUG] User Agent: ...
   [DEBUG] Query: test
   [DEBUG] Response status: 200
   [DEBUG] Response data: {...}
   ```

---

## 🔍 Co szukać w logach:

### ✅ Dobre znaki:
```
=== LANDING SEARCH REQUEST START ===
is_facebook_browser: true
has_session: true
Response status: 200
=== LANDING SEARCH SUCCESS ===
```

### ❌ Złe znaki:

**Brak requestów w logu:**
→ Request nie dochodzi do serwera (DNS, firewall, routing)

**"has_session: false":**
→ Problem z cookies/session w Facebook browser

**"Response status: 419":**
→ CSRF problem (ale już wyłączony, więc nie powinno się pojawić)

**"Response status: 500":**
→ Błąd serwera, sprawdź szczegóły w logu

**"Response status: 429":**
→ Rate limiting, za dużo requestów

**"Nie znaleziono wyników" w konsoli:**
→ API działa, ale nie znalazło miejsc (Serper API problem lub pusta baza)

---

## 🛠️ Możliwe problemy i rozwiązania:

### Problem: Request nie dochodzi do serwera
**Objawy:** Brak logów w `watch-logs.sh`
**Rozwiązanie:** 
- Sprawdź czy serwer działa: `php artisan serve`
- Sprawdź DNS/routing
- Sprawdź firewall

### Problem: CSRF error (419)
**Objawy:** Status 419 lub "CSRF token mismatch"
**Rozwiązanie:** Już wyłączone w `bootstrap/app.php`

### Problem: Session nie działa
**Objawy:** `has_session: false` w logu
**Rozwiązanie:** Facebook browser blokuje cookies
- Możesz użyć stateless approach
- Lub dodać instrukcję "Otwórz w zewnętrznej przeglądarce"

### Problem: "Nie znaleziono wyników"
**Objawy:** Status 200, ale pusta tablica places
**Rozwiązanie:** 
- Sprawdź czy Serper API działa
- Sprawdź czy w bazie są miejsca: `php artisan tinker` → `Place::count()`
- Zobacz dokładny response w logu

### Problem: CORS error
**Objawy:** "blocked by CORS policy" w konsoli
**Rozwiązanie:** Dodaj CORS headers w Laravel

---

## 📱 Remote debugging Facebook browser (advanced)

### iOS (Safari):
1. Podłącz iPhone do Mac
2. Safari → Develop → [Twój iPhone] → [Facebook]
3. Zobacz console i network

### Android (Chrome):
1. Podłącz Android do komputera
2. Chrome → chrome://inspect
3. Znajdź Facebook WebView
4. Inspect

---

## 🆘 Jeśli nic nie działa:

Wyślij mi:
1. **Screenshot ze strony `/sprawdz-wizytowke/debug`**
2. **Ostatnie 100 linii logów:**
   ```bash
   tail -100 storage/logs/laravel.log | grep LANDING > debug.txt
   ```
3. **Screenshot błędu z telefonu**
4. **Model telefonu i wersja systemu**

---

## 📝 Quick commands

```bash
# Oglądaj logi
./watch-logs.sh

# Wyczyść logi
> storage/logs/laravel.log

# Test API z terminala
curl -X POST https://app.get.promo/api/public/search-places \
  -H "Content-Type: application/json" \
  -d '{"query":"test cafe warszawa"}'

# Debug endpoint
curl https://app.get.promo/api/public/debug-request

# Sprawdź czy są miejsca w bazie
php artisan tinker
>>> Place::count()
>>> Place::first()
```

---

## 🎯 Następne kroki:

1. **Najpierw:** Otwórz `/sprawdz-wizytowke/debug` w Facebook browser
2. **Kliknij:** "Test Search API"
3. **Zrób:** Screenshot
4. **Sprawdź:** Logi serwera `./watch-logs.sh`
5. **Wyślij mi:** Screenshot + logi

---

Powodzenia! 🚀

