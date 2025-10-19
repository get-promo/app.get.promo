#!/bin/bash
# Sprawdź czy proces żyje i co robi

PID=$(pgrep -f parseEmails.py)

if [ -z "$PID" ]; then
    echo "❌ Proces nie działa"
    exit 1
fi

echo "✓ Proces działa (PID: $PID)"
echo ""
echo "Stany wątków:"
ps -T -p $PID | tail -5
echo ""
echo "Co robi (ostatnie 10 wywołań systemowych):"
timeout 2 strace -p $PID 2>&1 | head -20

