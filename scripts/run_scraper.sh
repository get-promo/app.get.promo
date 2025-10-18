#!/bin/bash

# Skrypt pomocniczy do uruchamiania scrapera w tle

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "🚀 Uruchamianie scrapera Serper API..."
echo "📁 Katalog: $PROJECT_DIR"
echo ""

# Sprawdź czy proces już działa
if pgrep -f "buildPlacesDb.py" > /dev/null; then
    echo "⚠️  Scraper już działa!"
    echo ""
    echo "Aby zobaczyć logi:"
    echo "  tail -f scripts/scraper.log"
    echo ""
    echo "Aby zatrzymać:"
    echo "  pkill -f buildPlacesDb.py"
    exit 1
fi

# Uruchom w tle
nohup python3 scripts/buildPlacesDb.py > scripts/scraper_output.log 2>&1 &
PID=$!

echo "✅ Scraper uruchomiony w tle (PID: $PID)"
echo ""
echo "📊 Monitorowanie:"
echo "  tail -f scripts/scraper.log          # Logi na żywo"
echo "  python3 scripts/check_db.py          # Stan bazy danych"
echo "  cat scripts/progress.json            # Postęp"
echo ""
echo "🛑 Zatrzymanie:"
echo "  pkill -f buildPlacesDb.py"
echo ""
echo "📈 Oczekiwany czas: 30-90 minut dla 15,624 kombinacji"
echo ""

