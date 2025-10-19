#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Skrypt naprawczy - przepisuje website z serper_response JSON do kolumny website.
"""

import os
import sys
import json
import pymysql
from dotenv import load_dotenv

# Wczytaj konfigurację
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)
ENV_PATH = os.path.join(PROJECT_ROOT, '.env')

load_dotenv(ENV_PATH)

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE', 'laravel')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')

print("=" * 70)
print("FIX: Przepisywanie website z serper_response do kolumny website")
print("=" * 70)

# Połącz z bazą
try:
    conn = pymysql.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USERNAME,
        password=DB_PASSWORD,
        database=DB_DATABASE,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )
    print("✓ Połączono z bazą danych")
except Exception as e:
    print(f"❌ Błąd połączenia: {e}")
    sys.exit(1)

cursor = conn.cursor()

# Pobierz wszystkie rekordy z serper_response
cursor.execute("SELECT id, cid, title, website, serper_response FROM places WHERE serper_response IS NOT NULL")
records = cursor.fetchall()

print(f"✓ Znaleziono {len(records)} rekordów do sprawdzenia")
print()

fixed_count = 0
skipped_count = 0
error_count = 0

for record in records:
    try:
        record_id = record['id']
        current_website = record['website'] or ''
        serper_json = record['serper_response']
        
        # Parsuj JSON
        if isinstance(serper_json, str):
            serper_data = json.loads(serper_json)
        else:
            serper_data = serper_json
        
        # Wyciągnij website z JSON
        correct_website = serper_data.get('website', '')
        
        # Jeśli website jest pusty w JSON, pomiń
        if not correct_website:
            skipped_count += 1
            continue
        
        # Jeśli website jest już poprawny, pomiń
        if current_website == correct_website:
            skipped_count += 1
            continue
        
        # Aktualizuj website
        update_sql = "UPDATE places SET website = %s WHERE id = %s"
        cursor.execute(update_sql, (correct_website, record_id))
        
        fixed_count += 1
        
        if fixed_count % 100 == 0:
            print(f"  Naprawiono {fixed_count} rekordów...")
        
    except Exception as e:
        error_count += 1
        print(f"  ⚠️ Błąd dla rekordu {record.get('id')}: {e}")
        continue

# Commit zmian
conn.commit()
conn.close()

print()
print("=" * 70)
print("PODSUMOWANIE")
print("=" * 70)
print(f"✓ Naprawiono rekordów: {fixed_count}")
print(f"  Pominięto (OK lub brak URL): {skipped_count}")
print(f"  Błędy: {error_count}")
print("=" * 70)
print("Zakończono!")

