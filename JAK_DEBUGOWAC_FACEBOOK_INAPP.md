# 🐛 Jak debugować Facebook In-App Browser

## Problem
Landing page nie działa w przeglądarce wbudowanej w aplikację Facebook na telefonie.

## Co zrobiłem:

### 1. ✅ Wyłączyłem CSRF dla publicznych API
CSRF token jest już wyłączony w `bootstrap/app.php` dla `api/public/*`

### 2. 📝 Dodałem szczegółowe logowanie

**W kontrolerze** (`app/Http/Controllers/LandingController.php`):
- Loguje WSZYSTKIE przychodzące requesty
- Loguje headery, IP, user agent
- Loguje szczegóły błędów z pełnym stack trace

**Na frontendzie** (`resources/views/content/landing/index.blade.php`):
- Loguje do console.log wszystkie kroki
- Pokazuje szczegółowe błędy użytkownikowi

### 3. 🧪 Stworzyłem narzędzia do debugowania

#### A) Strona testowa
```
https://twoja-domena.pl/sprawdz-wizytowke/debug
```

Ta strona pokazuje:
- Informacje o przeglądarce (User Agent, cookies, localStorage)
- Testy połączenia z API
- Logi konsoli w czasie rzeczywistym

**JAK UŻYWAĆ NA TELEFONIE:**
1. Otwórz link w Facebook in-app browser (kliknij na post z Facebooka)
2. Zobacz jakie informacje wyświetla strona
3. Kliknij "Test Endpoint" - sprawdzi czy serwer w ogóle odpowiada
4. Kliknij "Test Search API" - sprawdzi czy wyszukiwanie działa
5. Zrób screenshot i wyślij mi wyniki!

#### B) Debug endpoint
```
POST https://twoja-domena.pl/api/public/debug-request
```

Zwraca wszystkie informacje o requeście (headers, IP, itp.)

#### C) Skrypt do oglądania logów
```bash
./watch-logs.sh
```

Uruchom w terminalu na serwerze - będzie pokazywał logi w czasie rzeczywistym.

## Jak debugować krok po kroku:

### KROK 1: Sprawdź logi serwera

Na serwerze uruchom:
```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
./watch-logs.sh
```

Potem otwórz landing w Facebook browser na telefonie i spróbuj wyszukać coś.
Powinieneś zobaczyć logi typu:
```
=== LANDING SEARCH REQUEST START ===
```

**Jeśli NIE WIDZISZ logów:**
- Request nie dochodzi do serwera
- Problem z DNS/routing/firewall

**Jeśli WIDZISZ logi:**
- Sprawdź co dokładnie jest w logu
- Poszukaj błędów

### KROK 2: Otwórz stronę debug na telefonie

1. Na Facebooku udostępnij ten link (albo wyślij sobie wiadomość):
   ```
   https://app.get.promo/sprawdz-wizytowke/debug
   ```

2. Otwórz link w Facebooku (kliknij na niego w aplikacji FB)

3. Zobacz co pokazuje:
   - ❌ "Cookies włączone: NIE" → Problem z cookies!
   - ❌ "LocalStorage: Nie działa" → Problem z localStorage!
   - ✅ Wszystko OK → Problem jest gdzie indziej

4. Kliknij "Test Endpoint"
   - ✅ SUCCESS → Serwer działa, problem jest w search API
   - ❌ ERROR → Serwer nie odpowiada lub CORS problem

5. Kliknij "Test Search API"
   - ✅ SUCCESS → API działa! Problem jest w UI
   - ❌ ERROR → Zobacz szczegóły błędu

6. **WAŻNE:** Zrób screenshot całej strony i wyślij!

### KROK 3: Sprawdź logi Laravel

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
tail -100 storage/logs/laravel.log | grep LANDING
```

Albo po prostu otwórz plik:
```bash
nano storage/logs/laravel.log
```

I poszukaj najnowszych wpisów z "LANDING SEARCH".

### KROK 4: Test z normalnej przeglądarki (porównanie)

Otwórz te same URL w Safari/Chrome na telefonie:
```
https://app.get.promo/sprawdz-wizytowke
https://app.get.promo/sprawdz-wizytowke/debug
```

Jeśli działa w Safari ale nie działa w Facebook → problem jest specyficzny dla FB browser.

## Najczęstsze problemy z Facebook In-App Browser:

### Problem 1: Cookies/Session
**Objawy:** Request dochodzi ale nie ma session_id
**Rozwiązanie:** Użyj stateless API (bez sesji)

### Problem 2: CORS
**Objawy:** Network error, blocked by CORS policy
**Rozwiązanie:** Dodaj odpowiednie headery CORS

### Problem 3: Stary JavaScript
**Objawy:** Syntax error, undefined
**Rozwiązanie:** Transpiluj kod do ES5

### Problem 4: SSL/HTTPS
**Objawy:** Mixed content, security error
**Rozwiązanie:** Upewnij się że wszystko jest HTTPS

### Problem 5: Rate Limiting
**Objawy:** 429 Too Many Requests
**Rozwiązanie:** Zwiększ limity

## Rozwiązania awaryjne:

### Jeśli nic nie działa:

1. **Dodaj przycisk "Otwórz w przeglądarce"**
   ```javascript
   <button onclick="window.open(window.location.href, '_blank')">
     Otwórz w zewnętrznej przeglądarce
   </button>
   ```

2. **Dodaj detekcję Facebook browser**
   ```javascript
   const isFacebookBrowser = /FBAN|FBAV/i.test(navigator.userAgent);
   if (isFacebookBrowser) {
     alert('Uwaga: Używasz przeglądarki Facebook. ' +
           'Jeśli coś nie działa, kliknij "..." i wybierz "Otwórz w Safari/Chrome"');
   }
   ```

3. **Fallback do prostego formularza**
   Jeśli API nie działa → pokaż prosty formularz z samym numerem telefonu

## Kontakt

Jeśli dalej nie działa, wyślij mi:
1. Screenshot ze strony `/debug`
2. Plik logów `storage/logs/laravel.log` (ostatnie 100 linii)
3. Screenshot błędu z telefonu
4. Model telefonu i wersja iOS/Android

---

## Quick Commands

```bash
# Oglądaj logi na żywo
./watch-logs.sh

# Zobacz ostatnie 100 linii logów
tail -100 storage/logs/laravel.log

# Wyczyść logi (jeśli za duże)
> storage/logs/laravel.log

# Test API z terminala
curl -X POST https://app.get.promo/api/public/search-places \
  -H "Content-Type: application/json" \
  -d '{"query":"test cafe"}'

# Test debug endpoint
curl https://app.get.promo/api/public/debug-request
```

