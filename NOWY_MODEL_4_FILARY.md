# 📊 NOWY MODEL RAPORTU - 4 FILARY

## 🎯 Przegląd

Nowy model oceny profilu Google Business składa się z **4 filarów**, każdy z precyzyjnie określonymi wagami składowych.

---

## 🏗️ Architektura

### Nowe Scorery (w `app/Services/Report/`):

1. **OpinionsScorer** - ocena opinii (rating + liczba)
2. **OwnerRepliesScorer** - ocena odpowiedzi właściciela  
3. **PostsRecencyScorer** - świeżość postów (ostatnie 30 dni)
4. **PhotosRecencyScorer** - świeżość zdjęć (dni od ostatniego)
5. **PhotosCountScorer** - liczba zdjęć
6. **DescriptionLengthScorer** - długość opisu
7. **DescriptionAIScorer** - jakość opisu (heurystyka + TODO: OpenAI)
8. **CategoryFitScorer** - dopasowanie kategorii
9. **HoursUrlScorer** - godziny + strona WWW

### Transformer:

**FourPillarTransformer** - główny transformer który agreguje scorery w 4 filary

---

## 📐 Struktura Filarów

### 1️⃣ **Pozycja** (100% Position Score)
```
Pozycja = Position Score (z PositionScorer)
```

**Opis:** Widoczność firmy w wynikach wyszukiwania Google Maps

---

### 2️⃣ **Zaufanie** (opinions 70% + owner_replies 30%)
```
Zaufanie = (opinions_score × 0.7) + (owner_replies_score × 0.3)
```

**Składowe:**
- **Opinie** (70%) - rating + liczba opinii
- **Odpowiedzi właściciela** (30%) - procent opinii z odpowiedzią

**Opis:** Czy klienci ufają firmie

---

### 3️⃣ **Aktywność** (posts 40% + photos_recency 40% + owner_replies 20%)
```
Aktywność = (posts_recency × 0.4) + (photos_recency × 0.4) + (owner_replies × 0.2)

Jeśli brak danych o postach:
Aktywność = (photos_recency × 0.67) + (owner_replies × 0.33)
```

**Składowe:**
- **Świeżość postów** (40%) - liczba postów w ostatnich 30 dniach
- **Świeżość zdjęć** (40%) - dni od ostatniego zdjęcia
- **Odpowiedzi właściciela** (20%) - aktywność w komunikacji

**Opis:** Czy profil jest aktualny i żywy

---

### 4️⃣ **Prezentacja** (5 składowych)
```
Prezentacja = (photos_count × 0.20) +
              (description_length × 0.15) +
              (description_ai × 0.20) +
              (hours_url × 0.25) +
              (category_fit × 0.20)
```

**Składowe:**
- **Liczba zdjęć** (20%) - ilość zdjęć w galerii
- **Długość opisu** (15%) - liczba znaków w opisie
- **Jakość opisu AI** (20%) - ocena heurystyczna/AI jakości tekstu
- **Godziny + URL** (25%) - kompletność podstawowych danych
- **Dopasowanie kategorii** (20%) - ilość i jakość kategorii

**Opis:** Jak atrakcyjnie i spójnie profil się prezentuje

---

## 🎨 Statusy i Kolory

| Zakres Score | Status | Kolor | Hex |
|--------------|--------|-------|-----|
| **< 3.0** | ❌ Wymaga pilnej poprawy | Czerwony | `#f35023` |
| **3.0 - 3.9** | ⚠️ Częściowo zoptymalizowany | Pomarańczowy | `#ffb900` |
| **≥ 4.0** | ✅ Bardzo dobra kondycja | Zielony | `#7eba01` |

---

## 📊 Badges (odznaki globalne)

Na podstawie statusu 4 filarów:

- **🔴 Profil wymaga pilnej interwencji** - 2+ filary < 3.0
- **🟠 Profil do wzmocnienia** - 3+ filary w zakresie 3.0-3.9
- **🟢 Profil w bardzo dobrej kondycji** - 3+ filary ≥ 4.0
- **🟡 Profil częściowo zoptymalizowany** - pozostałe przypadki

---

## 💻 Użycie w Kodzie

### Przykład w GenerateReportJob:

```php
use App\Services\Report\FourPillarTransformer;

// W metodzie handle()
$transformedData = FourPillarTransformer::transform([
    'places_data' => $leadPlacesData,
    'position_score' => $leadPositionScore,
    'search_query' => $firstPhrase->phrase,
]);

// Zapisz do raportu
$report->public_data = $transformedData;
```

### Struktura wyjściowa:

```json
{
  "header": {
    "title": "Analiza profilu Google Business - Nowy model 4-filarowy",
    "subtitle": "Zapytanie: «pizza kraków»",
    "badge": "🟢 Profil w bardzo dobrej kondycji - gotowy do skalowania"
  },
  "pillars": [
    {
      "name": "Pozycja",
      "description": "Widoczność Twojej firmy w wynikach wyszukiwania Google Maps",
      "score": 4.8,
      "status": "✅ Bardzo dobra kondycja",
      "color": "#7eba01",
      "insight": "Twoja firma jest w TOP 3! Doskonała widoczność w Google Maps."
    },
    {
      "name": "Zaufanie",
      "description": "Czy klienci ufają Twojej firmie na podstawie opinii i reakcji właściciela",
      "score": 4.6,
      "status": "✅ Bardzo dobra kondycja",
      "color": "#7eba01",
      "insight": "Twój profil budzi zaufanie! Klienci widzą dobre oceny i aktywną komunikację.",
      "breakdown": {
        "opinions": "4.8 (waga 70%)",
        "owner_replies": "4.0 (waga 30%)"
      }
    },
    {
      "name": "Aktywność",
      "description": "Czy profil wygląda na aktualny, żywy i regularnie aktualizowany",
      "score": 4.4,
      "status": "✅ Bardzo dobra kondycja",
      "color": "#7eba01",
      "insight": "Profil wygląda na żywy i aktualny! Klienci widzą że firma działa na bieżąco.",
      "breakdown": {
        "photos_recency": "4.8 (waga 67%)",
        "owner_replies": "4.0 (waga 33%)",
        "posts_recency": "brak danych"
      }
    },
    {
      "name": "Prezentacja",
      "description": "Jak atrakcyjnie, spójnie i profesjonalnie prezentuje się Twój profil",
      "score": 4.3,
      "status": "✅ Bardzo dobra kondycja",
      "color": "#7eba01",
      "insight": "Profil prezentuje się profesjonalnie i atrakcyjnie! Klienci mają dobre pierwsze wrażenie.",
      "breakdown": {
        "photos_count": "4.7 (waga 20%)",
        "description_length": "4.5 (waga 15%)",
        "description_ai": "3.8 (waga 20%)",
        "hours_url": "4.5 (waga 25%)",
        "category_fit": "4.0 (waga 20%)"
      }
    }
  ],
  "global_score": 4.5,
  "components": { ... }
}
```

---

## 🔄 Migracja ze Starego Modelu

### Stary model (5 filarów):
- Zaufanie
- Dopasowanie
- Aktywność
- Prezentacja
- Spójność

### Nowy model (4 filary):
- **Pozycja** - nowy, osobny filar
- **Zaufanie** - zredefiniowany (tylko opinie + odpowiedzi)
- **Aktywność** - zredefiniowany (posty + zdjęcia + odpowiedzi)
- **Prezentacja** - rozszerzony (5 składowych, w tym AI scoring)

### Co się zmieniło:
1. ✅ **Position Score jest teraz osobnym filarem** - nie jest zagnieżdżony
2. ✅ **"Dopasowanie" i "Spójność" zostały wchłonięte** do Prezentacji
3. ✅ **Nowe scorery zamiast monolitycznego ProfileQualityScorer**
4. ✅ **AI scoring opisu** (heurystyka + TODO: OpenAI API)
5. ✅ **Dynamiczne wagi** - jeśli brak danych o postach, przeliczają się wagi

---

## 🚀 Następne Kroki

### TODO:
1. ⏳ **Integracja z OpenAI API** dla `DescriptionAIScorer`
2. ⏳ **Aktualizacja widoku raportu** (`resources/views/content/reports/show.blade.php`)
3. ⏳ **Modyfikacja GenerateReportJob** żeby używał FourPillarTransformer
4. ⏳ **Testy jednostkowe** dla wszystkich scorerów
5. ⏳ **Migracja istniejących raportów** (jeśli potrzebne)

### Integracja OpenAI (przykład):

```php
// W DescriptionAIScorer::evaluateWithOpenAI()

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
    'Content-Type' => 'application/json',
])->post('https://api.openai.com/v1/chat/completions', [
    'model' => 'gpt-4',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Jesteś ekspertem od optymalizacji profili Google Business. Oceń jakość opisu w skali 0-5.'
        ],
        [
            'role' => 'user',
            'content' => "Kategorie: " . implode(', ', $categories) . "\nOpis: {$description}\n\nOceń pod względem: klarowności, profesjonalizmu, dopasowania do branży."
        ]
    ],
    'temperature' => 0.3,
]);

return (float) $response->json()['choices'][0]['message']['content'];
```

---

## 📞 Pytania?

Jeśli masz pytania lub sugestie dotyczące nowego modelu, skontaktuj się z zespołem deweloperskim.

**Utworzono:** 2025-01-17  
**Wersja:** 1.0  
**Status:** ✅ Scorery gotowe, czeka na integrację z GenerateReportJob

