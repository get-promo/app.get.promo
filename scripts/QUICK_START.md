# 🚀 Quick Start - Scraper Serper API

## ✅ Status implementacji

Wszystko jest gotowe do użycia!

- ✅ Migracja bazy danych wykonana (tabela `places`)
- ✅ Skrypt Python z wielowątkowością (16 wątków)
- ✅ System checkpointów (kontynuacja po przerwaniu)
- ✅ Moduły Python zainstalowane
- ✅ Testy zakończone sukcesem
- ✅ Pierwszy rekord zapisany w bazie

## 📊 Dane

- **72 frazy** (z `scripts/phrases.txt`)
- **217 miast** (z `scripts/cities.csv`)
- **15,624 kombinacji** do przetworzenia
- **~3 strony wyników** na kombinację (do 30 miejsc)

## 🏃 Uruchomienie

### Opcja 1: Podstawowe uruchomienie (foreground)

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
python3 scripts/buildPlacesDb.py
```

### Opcja 2: W tle (zalecane dla długich sesji)

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
nohup python3 scripts/buildPlacesDb.py > scripts/scraper_output.log 2>&1 &

# Sprawdzenie postępu:
tail -f scripts/scraper.log

# Znajdź proces:
ps aux | grep buildPlacesDb

# Zatrzymaj proces (jeśli potrzeba):
# pkill -f buildPlacesDb.py
```

## 📝 Monitorowanie

### Logi

```bash
# Na żywo:
tail -f scripts/scraper.log

# Ostatnie 50 linii:
tail -50 scripts/scraper.log

# Szukanie błędów:
grep ERROR scripts/scraper.log
```

### Postęp

```bash
# Sprawdź plik postępu:
cat scripts/progress.json | grep completed | wc -l

# Ile rekordów w bazie:
python3 scripts/check_db.py
```

## ⏱️ Szacowany czas

- **16 wątków równoległych**
- **~2-3 sekundy na kombinację** (średnio)
- **Całkowity czas: 30-90 minut** (w zależności od API)

## 🔄 Kontynuacja po przerwaniu

Skrypt automatycznie zapisuje postęp w `scripts/progress.json`.

Jeśli zostanie przerwany (Ctrl+C, błąd, restart):
```bash
# Po prostu uruchom ponownie - kontynuuje od ostatniego checkpointu:
python3 scripts/buildPlacesDb.py
```

## 📦 Struktura tabelki `places`

```sql
id              - ID rekordu
cid             - Google Place ID (unique)
title           - Nazwa miejsca
address         - Adres
latitude        - Szerokość geograficzna
longitude       - Długość geograficzna
rating          - Ocena (0-5)
rating_count    - Liczba opinii
price_level     - Poziom cen
category        - Kategoria
phone_number    - Telefon
website         - Strona WWW
serper_response - Pełna odpowiedź JSON z Serper
search_phrase   - Fraza na jaką szukano ✨
city_name       - Miasto ✨
city_size       - Wielkość miasta ✨
created_at      - Data utworzenia
updated_at      - Data aktualizacji
```

## 🧪 Weryfikacja testowa

Już wykonano test i dodano 1 rekord:
- **Title:** Pomoc Drogowa Warszawa
- **City:** Warszawa
- **Rating:** 4.8 (194 opinii)
- **Phrase:** pomoc drogowa

```bash
# Sprawdź stan bazy:
python3 scripts/check_db.py
```

## 🛠️ Rozwiązywanie problemów

### Problem: "Can't connect to MySQL server"
```bash
# Sprawdź konfigurację w .env:
grep DB_ .env
```

### Problem: "ModuleNotFoundError"
```bash
pip3 install -r scripts/requirements.txt
```

### Problem: Skrypt działa bardzo wolno
- Sprawdź limit API Serper (może być throttling)
- Zobacz logi: `tail -f scripts/scraper.log`

### Problem: Chcę zacząć od początku
```bash
# Usuń postęp:
rm scripts/progress.json

# Wyczyść tabelę (UWAGA: usuwa wszystkie dane!):
php artisan tinker --execute="DB::table('places')->truncate();"

# Uruchom ponownie:
python3 scripts/buildPlacesDb.py
```

## 📈 Oczekiwane wyniki

Po pełnym uruchomieniu (15,624 kombinacje):
- **~50,000 - 150,000 unikalnych miejsc** (w zależności od duplikatów)
- Każde miejsce ma przypisaną frazę i miasto
- Możliwość analizy pokrycia fraz w różnych miastach

## 🎯 Co dalej?

Po zakończeniu scrapingu masz pełną bazę miejsc z:
- Wszystkimi danymi kontaktowymi
- Ocenami i liczbą opinii
- Powiązaniem fraza-miasto
- Geolokalizacją
- Pełnym JSONem z Serper

Możesz:
- Analizować konkurencję
- Eksportować leady
- Tworzyć raporty
- Integrować z CRM

---

## 🚀 Gotowy? Uruchom teraz!

```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
python3 scripts/buildPlacesDb.py
```

**Powodzenia!** 🎉

