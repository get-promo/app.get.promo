#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Skrypt do sprawdzania postępu parsera emaili.
"""

import re
import os

# Parametry
LOG_FILE = 'scripts/email_scraper.log'
FIRST_ID = 19508
LAST_ID = 594647
TOTAL_TO_PROCESS = 244073  # Z początku skryptu

def get_last_processed_id(log_file):
    """Wyciąga ostatnie przetworzone ID z loga."""
    if not os.path.exists(log_file):
        return None
    
    try:
        with open(log_file, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        # Szukaj od końca linii z [ID]
        for line in reversed(lines):
            # Szukaj wzorca [12345]
            match = re.search(r'\[(\d+)\]', line)
            if match:
                return int(match.group(1))
        
        return None
    except Exception as e:
        print(f"Błąd odczytu loga: {e}")
        return None


def main():
    print("=" * 70)
    print("POSTĘP PARSERA EMAILI")
    print("=" * 70)
    print()
    
    # Pobierz ostatnie ID z loga
    last_id = get_last_processed_id(LOG_FILE)
    
    if last_id is None:
        print("❌ Nie znaleziono ID w logach")
        return
    
    print(f"📍 Zakres ID w bazie:")
    print(f"  Pierwsze ID: {FIRST_ID:,}")
    print(f"  Ostatnie ID: {LAST_ID:,}")
    print(f"  Całkowity zakres: {LAST_ID - FIRST_ID:,} ID")
    print()
    
    print(f"🔄 Aktualny postęp:")
    print(f"  Ostatnie przetworzone ID: {last_id:,}")
    print(f"  Przetworzone od początku: {last_id - FIRST_ID:,} ID")
    print()
    
    # Oblicz procent względem zakresu ID
    progress_by_id = ((last_id - FIRST_ID) / (LAST_ID - FIRST_ID)) * 100
    print(f"📊 Postęp względem zakresu ID: {progress_by_id:.2f}%")
    print()
    
    # Oblicz procent względem rekordów do przetworzenia
    if TOTAL_TO_PROCESS > 0:
        # Szacunek: załóżmy liniowy rozkład ID
        estimated_processed = int((last_id - FIRST_ID) / (LAST_ID - FIRST_ID) * TOTAL_TO_PROCESS)
        progress_by_records = (estimated_processed / TOTAL_TO_PROCESS) * 100
        
        print(f"📈 Szacowany postęp rekordów:")
        print(f"  Do przetworzenia: {TOTAL_TO_PROCESS:,} miejsc (z website)")
        print(f"  Szacunkowo przetworzono: ~{estimated_processed:,}")
        print(f"  Procent: ~{progress_by_records:.2f}%")
        print()
    
    # Dodatkowo sprawdź plik progress.json
    progress_file = 'scripts/email_progress.json'
    if os.path.exists(progress_file):
        import json
        try:
            with open(progress_file, 'r') as f:
                progress = json.load(f)
            
            completed_count = sum(1 for v in progress.values() if v.get('completed'))
            found_count = sum(1 for v in progress.values() if v.get('result') == 'email_found')
            
            print(f"✅ Dokładne dane z checkpointu:")
            print(f"  Przetworzone (checkpoint): {completed_count:,}")
            print(f"  Znalezione emaile: {found_count:,}")
            if completed_count > 0:
                success_rate = (found_count / completed_count) * 100
                print(f"  Skuteczność: {success_rate:.1f}%")
            print()
            
            if TOTAL_TO_PROCESS > 0:
                exact_progress = (completed_count / TOTAL_TO_PROCESS) * 100
                print(f"📍 Dokładny postęp: {exact_progress:.2f}%")
                
                # ETA
                if exact_progress > 0:
                    import time
                    # Sprawdź czas pierwszego i ostatniego wpisu
                    timestamps = [v.get('timestamp') for v in progress.values() if v.get('timestamp')]
                    if len(timestamps) >= 2:
                        from datetime import datetime
                        first_time = datetime.fromisoformat(timestamps[0])
                        last_time = datetime.fromisoformat(timestamps[-1])
                        elapsed = (last_time - first_time).total_seconds()
                        
                        if elapsed > 0 and completed_count > 0:
                            speed = completed_count / elapsed  # rekordów/sekundę
                            remaining = TOTAL_TO_PROCESS - completed_count
                            eta_seconds = remaining / speed
                            eta_hours = eta_seconds / 3600
                            
                            print(f"⏱️  Szybkość: {speed:.2f} rekordów/sek")
                            print(f"⏳ Szacowany czas do końca: {eta_hours:.1f} godzin")
        except:
            pass
    
    print("=" * 70)


if __name__ == '__main__':
    main()


