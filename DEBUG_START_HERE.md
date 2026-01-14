# 🐛 Debugowanie Facebook In-App Browser - START TUTAJ

## 🎯 Problem
Landing page nie działa w przeglądarce wbudowanej w Facebook na telefonie.

---

## ⚡ SZYBKI START (2 minuty)

### Krok 1: Test z telefonu (Facebook browser)
Wyślij sobie ten link na Messenger i kliknij:
```
https://app.get.promo/sprawdz-wizytowke/debug
```

**Co zrobić:**
1. Kliknij "Test Search API"
2. Zrób screenshot całej strony
3. Wyślij mi screenshot

### Krok 2: Sprawdź logi na serwerze
```bash
cd /Users/maciejkostecki/Documents/WORKSPACE/app.get.promo
./watch-logs.sh
```

Potem otwórz landing na telefonie i spróbuj wyszukać coś.

**To wszystko!** Te dwa kroki pokażą gdzie jest problem.

---

## 🛠️ Dostępne narzędzia

### 1. 🌐 Strona testowa (dla telefonu)
**URL:** https://app.get.promo/sprawdz-wizytowke/debug

**Co robi:**
- Pokazuje info o przeglądarce
- Testuje czy cookies/localStorage działają  
- Testuje połączenie z API
- Pokazuje logi w czasie rzeczywistym

**Kiedy użyć:** Zawsze na początku debugowania!

---

### 2. 📋 Oglądanie logów
**Komenda:** `./watch-logs.sh`

**Co robi:**
- Pokazuje logi Laravel w czasie rzeczywistym
- Filtruje tylko logi związane z landingiem
- Automatycznie się aktualizuje

**Kiedy użyć:** Gdy chcesz zobaczyć co dzieje się na serwerze.

---

### 3. 🧪 Test API z terminala
**Komenda:** `./test-landing-api.sh`

**Co robi:**
- Testuje debug endpoint
- Testuje search API
- Testuje polskie znaki
- Pokazuje kolorowe wyniki

**Kiedy użyć:** Gdy chcesz szybko sprawdzić czy API działa.

---

### 4. 🔧 Debug endpoint
**URL:** https://app.get.promo/api/public/debug-request

**Co robi:**
- Zwraca wszystkie informacje o requeście
- Headers, IP, user agent, session ID

**Kiedy użyć:** Gdy chcesz zobaczyć dokładnie co przychodzi z przeglądarki.

**Test curl:**
```bash
curl https://app.get.promo/api/public/debug-request
```

---

## 📚 Dokumentacja

### Dla początkujących:
👉 **Ten plik** - zaczynasz tutaj

### Szczegółowa instrukcja:
👉 `JAK_DEBUGOWAC_FACEBOOK_INAPP.md` - krok po kroku

### Podsumowanie zmian:
👉 `DEBUG_FACEBOOK_BROWSER_SUMMARY.md` - co zostało zrobione

---

## 🚦 Flowchart debugowania

```
START
  │
  ├─→ Otwórz /sprawdz-wizytowke/debug na telefonie (FB browser)
  │   │
  │   ├─→ Kliknij "Test Search API"
  │   │   │
  │   │   ├─→ ✅ SUCCESS → API działa!
  │   │   │                Problem jest w UI landingu
  │   │   │                Zobacz logi w konsoli na stronie debug
  │   │   │
  │   │   └─→ ❌ ERROR → API nie działa
  │   │                   Przejdź do kroku 2
  │   │
  │   └─→ Zobacz informacje o przeglądarce
  │       ├─→ Cookies: NIE → Facebook blokuje cookies
  │       └─→ LocalStorage: NIE → Facebook blokuje storage
  │
  └─→ KROK 2: Sprawdź logi serwera
      │
      ├─→ ./watch-logs.sh
      │   │
      │   ├─→ Są logi? → Request dochodzi, sprawdź szczegóły błędu
      │   │
      │   └─→ Brak logów? → Request nie dochodzi (DNS/firewall/routing)
      │
      └─→ ./test-landing-api.sh (test z serwera)
          │
          ├─→ ✅ Działa lokalnie → Problem tylko w FB browser
          │
          └─→ ❌ Nie działa → Problem z serwerem/API
```

---

## 📱 Jak otworzyć Remote Debugging (zaawansowane)

### iOS + Mac:
1. Podłącz iPhone do Mac kablem
2. Na iPhonie: Ustawienia → Safari → Zaawansowane → Web Inspector: ON
3. Na Mac: Safari → Develop → [Twój iPhone] → [Facebook]
4. Zobacz Console i Network

### Android:
1. Podłącz telefon USB
2. Chrome na komputerze: `chrome://inspect`
3. Znajdź Facebook WebView
4. Kliknij "Inspect"

---

## 🆘 Nic nie działa? Wyślij mi:

1. **Screenshot z `/sprawdz-wizytowke/debug`** (z telefonu)

2. **Ostatnie logi:**
   ```bash
   tail -100 storage/logs/laravel.log | grep LANDING > debug.txt
   ```

3. **Screenshot błędu** z normalnego landingu

4. **Info o telefonie:**
   - Model
   - iOS/Android wersja
   - Wersja aplikacji Facebook

---

## 🎬 Quick Commands

```bash
# Oglądaj logi na żywo
./watch-logs.sh

# Test API
./test-landing-api.sh

# Wyczyść stare logi
> storage/logs/laravel.log

# Test konkretnego query
curl -X POST https://app.get.promo/api/public/search-places \
  -H "Content-Type: application/json" \
  -d '{"query":"test"}'

# Info o request
curl https://app.get.promo/api/public/debug-request
```

---

## ✅ Checklist

- [ ] Otworzyłem `/sprawdz-wizytowke/debug` na telefonie
- [ ] Kliknąłem "Test Search API"
- [ ] Zrobiłem screenshot
- [ ] Uruchomiłem `./watch-logs.sh` na serwerze
- [ ] Uruchomiłem `./test-landing-api.sh` z terminala
- [ ] Sprawdziłem ostatnie logi `tail storage/logs/laravel.log`
- [ ] Wiem gdzie jest problem! 🎉

---

## 💡 Co zostało naprawione/dodane:

✅ CSRF wyłączony dla `/api/public/*`  
✅ Szczegółowe logowanie w kontrolerze  
✅ Szczegółowe logowanie na frontendzie  
✅ Detekcja Facebook/Instagram browser  
✅ Banner ostrzegawczy dla użytkowników FB  
✅ Strona testowa `/debug`  
✅ Debug endpoint  
✅ Skrypt do oglądania logów  
✅ Skrypt do testowania API  
✅ Pełna dokumentacja  

---

**Powodzenia! 🚀**

Jeśli masz pytania, sprawdź `JAK_DEBUGOWAC_FACEBOOK_INAPP.md`

