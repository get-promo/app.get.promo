# Landing Page - Sprawdź swoją wizytówkę Google

## Opis

Prosty, publiczny landing page bez wymagania logowania, który umożliwia:
1. Wyszukiwanie wizytówek Google po wpisaniu min. 4 znaków
2. Wybór konkretnej wizytówki z listy sugestii
3. Alternatywny formularz z numerem telefonu dla użytkowników bez wizytówki

## Dostęp

Landing page jest dostępny pod adresem:
```
https://twoja-domena.pl/sprawdz-wizytowke
```

Nie wymaga logowania - jest całkowicie publiczny.

## Utworzone pliki

### 1. Kontroler
**Plik:** `app/Http/Controllers/LandingController.php`

Zawiera 3 metody:
- `index()` - wyświetla landing page
- `searchPlaces()` - wyszukuje miejsca przez Serper API (min. 4 znaki)
- `submitPhone()` - przyjmuje numer telefonu (do uzupełnienia logiki)

### 2. Widok
**Plik:** `resources/views/content/landing/index.blade.php`

Zawiera:
- Wyszukiwarkę miejsc z live suggestions
- Przycisk "Sprawdź" (aktywny po wyborze miejsca)
- Link do alternatywnego formularza
- Formularz z numerem telefonu
- Style w kolorach Google (niebieski #1a73e8)

### 3. Trasy
**Plik:** `routes/web.php`

Dodane 3 publiczne trasy:
- `GET /sprawdz-wizytowke` - wyświetla landing page
- `POST /api/public/search-places` - wyszukuje miejsca
- `POST /api/public/submit-phone` - przyjmuje numer telefonu

## Funkcje

### Wyszukiwarka miejsc
- Aktywuje się po wpisaniu min. 4 znaków
- Wywołuje API po 500ms od ostatniego wpisanego znaku (debounce)
- Wyświetla do 5 sugestii z nazwą i adresem
- Po wyborze miejsca aktywuje przycisk "Sprawdź"
- Używa Serper API (Google Places)

### Alternatywny formularz
- Wyświetla się po kliknięciu linku
- Zawiera pole na numer telefonu
- Przycisk "Dalej" w kolorach Google
- Link powrotny do wyszukiwarki

## Co można dodać dalej?

### 1. Logika po kliknięciu "Sprawdź"
W pliku `index.blade.php` w linii ~236 jest:
```javascript
checkBtn.addEventListener('click', () => {
  if (selectedPlace) {
    console.log('Wybrane miejsce:', selectedPlace);
    alert('Wybrano: ' + selectedPlace.title);
  }
});
```

Możesz zmienić na np.:
```javascript
checkBtn.addEventListener('click', () => {
  if (selectedPlace) {
    // Przekieruj do strony z raportem
    window.location.href = '/raport/' + selectedPlace.cid;
    
    // LUB wyślij dane do serwera
    fetch('/api/public/check-place', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ place: selectedPlace })
    });
  }
});
```

### 2. Zapisywanie numeru telefonu
W pliku `LandingController.php` w metodzie `submitPhone()` (linia ~61):
```php
public function submitPhone(Request $request)
{
    $request->validate([
        'phone' => 'required|string|min:9'
    ]);
    
    // Dodaj logikę zapisu do bazy danych
    // np. Lead::create(['phone' => $request->phone]);
    
    return response()->json([
        'success' => true,
        'message' => 'Numer telefonu został zapisany'
    ]);
}
```

### 3. Google Analytics / Tracking
Dodaj w sekcji `@section('page-script')`:
```javascript
// Google Analytics
gtag('event', 'place_search', {
  'event_category': 'engagement',
  'event_label': query
});
```

### 4. Walidacja numeru telefonu
Dodaj lepszą walidację w JavaScript:
```javascript
function validatePhone(phone) {
  // Polski numer telefonu
  const phoneRegex = /^(\+48)?[\s-]?\d{3}[\s-]?\d{3}[\s-]?\d{3}$/;
  return phoneRegex.test(phone);
}
```

### 5. Loading state
Dodaj spinner podczas wyszukiwania i wysyłania formularza.

## Konfiguracja

### Serper API Key
Landing page używa Serper API do wyszukiwania miejsc. Klucz API jest już skonfigurowany w pliku `.env`:
```
SERPER_API_KEY=2137e71880570b22cb06fa2b0436211b35ff81ad
```

## Testowanie

1. Uruchom serwer Laravel:
```bash
php artisan serve
```

2. Otwórz w przeglądarce:
```
http://localhost:8000/sprawdz-wizytowke
```

3. Przetestuj:
   - Wpisz min. 4 znaki w wyszukiwarce
   - Wybierz miejsce z listy
   - Kliknij "Sprawdź"
   - Kliknij link alternatywny
   - Wpisz numer telefonu
   - Kliknij "Dalej"

## Bezpieczeństwo

- ✅ CSRF protection włączona
- ✅ Walidacja inputów po stronie serwera
- ✅ Rate limiting można dodać w `routes/web.php`:
```php
Route::post('/api/public/search-places', [LandingController::class, 'searchPlaces'])
    ->middleware('throttle:10,1') // 10 requestów na minutę
    ->name('landing.search-places');
```

## Style

Landing page używa:
- Google Sans / Roboto jako fonty
- Niebieski Google (#1a73e8) dla przycisków i linków
- Minimalistyczny design zgodny z Material Design
- Responsywny layout (działa na mobile)

## Uwagi

- Landing page używa `blankLayout` - bez menu i stopki
- Nie wymaga logowania (poza middleware auth)
- Wszystkie API endpointy są pod `/api/public/`

