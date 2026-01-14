#!/bin/bash

# Kolorowy tail do logów Laravel
# Użycie: ./watch-logs.sh

LOG_FILE="storage/logs/laravel.log"

if [ ! -f "$LOG_FILE" ]; then
    echo "❌ Plik logów nie istnieje: $LOG_FILE"
    exit 1
fi

echo "📋 Oglądanie logów Laravel..."
echo "🔍 Filtrowanie: LANDING SEARCH"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Tail z filtrowaniem
tail -f "$LOG_FILE" | grep --line-buffered "LANDING\|landing"

