# Scraper Serper API - Instrukcja użycia

## Opis

Skrypt Python do automatycznego pobierania danych miejsc z Serper API dla wszystkich kombinacji fraz i miast.

### Funkcjonalności:
- ✅ Wielowątkowość (16 wątków równoległych)
- ✅ System checkpointów - kontynuacja po przerwaniu
- ✅ Automatyczne czyszczenie danych (usuwanie emoji, znaków specjalnych)
- ✅ Retry logic (3 próby dla każdego zapytania)
- ✅ Logowanie do pliku i konsoli
- ✅ Statystyki w czasie rzeczywistym
- ✅ Obsługa duplikatów (aktualizacja istniejących rekordów)

## Wymagania

- Python 3.8+
- MySQL/MariaDB
- Klucz API Serper w pliku `.env`

## Instalacja

### 1. Instalacja zależności Python

```bash
pip install -r scripts/requirements.txt
```

lub

```bash
pip3 install -r scripts/requirements.txt
```

### 2. Migracja bazy danych

Migracja została już uruchomiona. Tabela `places` jest gotowa.

### 3. Konfiguracja .env

Upewnij się, że w pliku `.env` masz:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopium
DB_USERNAME=shopium
DB_PASSWORD=2ZLpcswskl3

SERPER_API_KEY=<twój_klucz>
```

## Uruchomienie

### Podstawowe uruchomienie:

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
python3 scripts/buildPlacesDb.py
```

### Uruchomienie w tle (zalecane dla długich sesji):

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
nohup python3 scripts/buildPlacesDb.py > scripts/scraper_output.log 2>&1 &
```

Aby sprawdzić postęp:
```bash
tail -f scripts/scraper.log
```

Aby zobaczyć proces:
```bash
ps aux | grep buildPlacesDb
```

## Pliki

- **buildPlacesDb.py** - główny skrypt
- **phrases.txt** - 72 frazy do wyszukania
- **cities.csv** - 217 miast (kolumna 0: nazwa, kolumna 1: wielkość)
- **progress.json** - automatycznie tworzony, zapisuje postęp
- **scraper.log** - log ze wszystkimi operacjami i błędami

## Struktura danych

### Tabela `places`:

- `cid` - Google Place ID (unique)
- `title` - Nazwa miejsca
- `address` - Adres
- `latitude`, `longitude` - Współrzędne
- `rating` - Ocena (0-5)
- `rating_count` - Liczba opinii
- `price_level` - Poziom cen
- `category` - Kategoria
- `phone_number` - Numer telefonu
- `website` - Strona WWW
- `serper_response` - Pełna odpowiedź JSON z Serper API
- `search_phrase` - Fraza na jaką szukano
- `city_name` - Nazwa miasta
- `city_size` - Wielkość miasta (populacja)

## Działanie

1. **Wczytanie danych:**
   - 72 frazy z `phrases.txt`
   - 217 miast z `cities.csv`
   - Łącznie: **15,624 kombinacji**

2. **System checkpointów:**
   - Każda ukończona kombinacja zapisywana w `progress.json`
   - Przy ponownym uruchomieniu kontynuacja od miejsca przerwania
   - Bezpieczne przerywanie (Ctrl+C)

3. **Wielowątkowość:**
   - 16 równoległych wątków
   - Każdy wątek przetwarza jedną kombinację fraza+miasto
   - Thread-safe operacje na bazie i plikach

4. **Paginacja Serper:**
   - Dla każdej kombinacji: strony 1-3
   - Łącznie do 30 wyników na kombinację
   - Przerwy między zapytaniami (rate limiting)

5. **Obsługa duplikatów:**
   - Sprawdzanie po `cid` (Google Place ID)
   - Jeśli istnieje → aktualizacja
   - Jeśli nie istnieje → dodanie

## Statystyki

Po zakończeniu otrzymasz podsumowanie:
- Czas wykonania
- Liczba przetworzonych kombinacji
- Liczba znalezionych miejsc
- Liczba dodanych rekordów
- Liczba zaktualizowanych rekordów
- Liczba błędów

## Przykładowy output:

```
================================================================================
Rozpoczynam pobieranie danych z Serper API
================================================================================
Wczytano 72 fraz
Wczytano 217 miast
Całkowita liczba kombinacji: 15624
Ukończone kombinacje: 0
Pozostałe kombinacje: 15624
Liczba wątków: 16
================================================================================
Do przetworzenia: 15624 kombinacji
2025-10-18 15:30:12 [INFO] Przetwarzanie: barber - Warszawa
2025-10-18 15:30:13 [INFO] Znaleziono 10 miejsc dla 'barber Warszawa' strona 1
...
================================================================================
PODSUMOWANIE
================================================================================
Czas wykonania: 3652.45 sekund (60.87 minut)
Przetworzone kombinacje: 15624
Całkowita liczba miejsc: 45231
Dodano nowych miejsc: 43120
Zaktualizowano miejsc: 2111
Liczba błędów: 12
================================================================================
```

## Rozwiązywanie problemów

### Problem: "SERPER_API_KEY nie znaleziony"
**Rozwiązanie:** Dodaj klucz do `.env`

### Problem: Błąd połączenia z bazą
**Rozwiązanie:** Sprawdź ustawienia DB_* w `.env`

### Problem: Brak modułu Python
**Rozwiązanie:** `pip3 install -r scripts/requirements.txt`

### Problem: Skrypt się zawiesza
**Rozwiązanie:** Ctrl+C i uruchom ponownie (kontynuuje od checkpointu)

## Czyszczenie i restart

Jeśli chcesz zacząć od początku:

```bash
# Usuń postęp
rm scripts/progress.json

# Wyczyść tabelę
mysql -u shopium -p shopium -e "TRUNCATE TABLE places;"

# Uruchom ponownie
python3 scripts/buildPlacesDb.py
```

## Szacowany czas wykonania

- **15,624 kombinacji**
- **16 wątków równoległych**
- **~2 sekundy na kombinację** (średnio)
- **Całkowity czas: ~30-60 minut** (w zależności od limitu API i szybkości sieci)

## Kontakt i wsparcie

W razie problemów sprawdź:
1. `scripts/scraper.log` - szczegółowe logi
2. `scripts/progress.json` - aktualny postęp
3. Tabelę `places` w bazie danych

