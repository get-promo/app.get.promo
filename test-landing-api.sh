#!/bin/bash

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="https://app.get.promo"
# Możesz zmienić na localhost jeśli testujesz lokalnie:
# BASE_URL="http://localhost:8000"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🧪 Test Landing Page API${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Test 1: Debug endpoint
echo -e "${YELLOW}Test 1: Debug Endpoint${NC}"
echo -e "${BLUE}URL:${NC} $BASE_URL/api/public/debug-request"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/api/public/debug-request")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ SUCCESS (HTTP $HTTP_CODE)${NC}"
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
else
    echo -e "${RED}❌ ERROR (HTTP $HTTP_CODE)${NC}"
    echo "$BODY"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Test 2: Search API
echo -e "${YELLOW}Test 2: Search Places API${NC}"
echo -e "${BLUE}URL:${NC} $BASE_URL/api/public/search-places"
echo -e "${BLUE}Query:${NC} 'test cafe warszawa'"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" \
  -X POST "$BASE_URL/api/public/search-places" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"query":"test cafe warszawa"}')

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ SUCCESS (HTTP $HTTP_CODE)${NC}"
    
    # Sprawdź czy są wyniki
    PLACES_COUNT=$(echo "$BODY" | python3 -c "import sys, json; data=json.load(sys.stdin); print(len(data.get('data', {}).get('places', [])))" 2>/dev/null)
    
    if [ -n "$PLACES_COUNT" ]; then
        echo -e "${GREEN}📍 Znaleziono miejsc: $PLACES_COUNT${NC}"
    fi
    
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
else
    echo -e "${RED}❌ ERROR (HTTP $HTTP_CODE)${NC}"
    echo "$BODY"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Test 3: Search z polskim znakiem
echo -e "${YELLOW}Test 3: Search z polskimi znakami${NC}"
echo -e "${BLUE}Query:${NC} 'Colex Kraków'"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" \
  -X POST "$BASE_URL/api/public/search-places" \
  -H "Content-Type: application/json; charset=utf-8" \
  -H "Accept: application/json" \
  -d '{"query":"Colex Kraków"}')

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ SUCCESS (HTTP $HTTP_CODE)${NC}"
    
    PLACES_COUNT=$(echo "$BODY" | python3 -c "import sys, json; data=json.load(sys.stdin); print(len(data.get('data', {}).get('places', [])))" 2>/dev/null)
    
    if [ -n "$PLACES_COUNT" ]; then
        if [ "$PLACES_COUNT" -gt 0 ]; then
            echo -e "${GREEN}📍 Znaleziono miejsc: $PLACES_COUNT${NC}"
        else
            echo -e "${YELLOW}⚠️  Brak wyników dla tej frazy${NC}"
        fi
    fi
    
    echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
else
    echo -e "${RED}❌ ERROR (HTTP $HTTP_CODE)${NC}"
    echo "$BODY"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Podsumowanie
echo -e "${GREEN}🎉 Testy zakończone!${NC}"
echo ""
echo -e "${YELLOW}💡 Wskazówki:${NC}"
echo "  - Jeśli wszystkie testy przeszły ✅ → API działa poprawnie"
echo "  - Jeśli są błędy ❌ → Sprawdź logi: ./watch-logs.sh"
echo "  - Jeśli brak wyników → Sprawdź bazę danych lub Serper API key"
echo ""
echo -e "${BLUE}📱 Strona testowa dla telefonu:${NC}"
echo "  $BASE_URL/sprawdz-wizytowke/debug"
echo ""

