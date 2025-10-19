#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Migracja danych z tabeli oldplaces (lub lead) do places.
Pomija duplikaty (sprawdza po CID).
Opcjonalnie aktualizuje email jeśli rekord istnieje ale nie ma emaila.
"""

import os
import sys
import pymysql
from datetime import datetime
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
print("MIGRACJA: oldplaces → places")
print("=" * 70)
print()

# Połącz z bazą
try:
    conn = pymysql.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USERNAME,
        password=DB_PASSWORD,
        database=DB_DATABASE,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=False
    )
    print("✓ Połączono z bazą danych")
except Exception as e:
    print(f"❌ Błąd połączenia: {e}")
    sys.exit(1)

cursor = conn.cursor()

# Sprawdź jaka nazwa tabeli (oldplaces lub lead)
cursor.execute("SHOW TABLES LIKE 'oldplaces'")
if cursor.fetchone():
    source_table = 'oldplaces'
else:
    cursor.execute("SHOW TABLES LIKE 'lead'")
    if cursor.fetchone():
        source_table = 'lead'
    else:
        print("❌ Nie znaleziono tabeli 'oldplaces' ani 'lead'")
        conn.close()
        sys.exit(1)

print(f"✓ Znaleziono tabelę źródłową: {source_table}")

# Policz rekordy
cursor.execute(f"SELECT COUNT(*) as cnt FROM {source_table}")
total_old = cursor.fetchone()['cnt']
print(f"✓ Rekordów w {source_table}: {total_old}")

cursor.execute("SELECT COUNT(*) as cnt FROM places")
total_places = cursor.fetchone()['cnt']
print(f"✓ Rekordów w places: {total_places}")
print()

# Pobierz wszystkie CID które już są w places
cursor.execute("SELECT cid FROM places WHERE cid IS NOT NULL AND cid != ''")
existing_cids = {row['cid'] for row in cursor.fetchall()}
print(f"✓ Istniejące CID w places: {len(existing_cids)}")
print()

# Pobierz wszystkie rekordy z oldplaces
cursor.execute(f"""
    SELECT 
        id, cid, name, address, lat, lng, rating, rating_count,
        category, phone, website, email, mail_checked, date_added
    FROM {source_table}
""")
old_records = cursor.fetchall()

print(f"Przetwarzanie {len(old_records)} rekordów...")
print()

stats = {
    'inserted': 0,
    'skipped_duplicate': 0,
    'skipped_no_cid': 0,
    'updated_email': 0,
    'errors': 0
}

now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

for record in old_records:
    try:
        # Bezpieczne pobranie CID
        cid_raw = record.get('cid')
        if cid_raw is None:
            stats['skipped_no_cid'] += 1
            continue
        
        cid = str(cid_raw).strip()
        
        # Pomiń rekordy bez CID
        if not cid:
            stats['skipped_no_cid'] += 1
            continue
        
        # Sprawdź czy CID już istnieje
        if cid in existing_cids:
            # Opcjonalnie: zaktualizuj email jeśli go nie ma
            email_raw = record.get('email')
            email = str(email_raw).strip() if email_raw else ''
            if email:
                cursor.execute("""
                    UPDATE places 
                    SET email = %s, 
                        email_checked = %s,
                        email_source = 'oldplaces_migration'
                    WHERE cid = %s AND (email IS NULL OR email = '')
                """, (email, bool(record.get('mail_checked', 0)), cid))
                
                if cursor.rowcount > 0:
                    stats['updated_email'] += 1
            
            stats['skipped_duplicate'] += 1
            continue
        
        # Wstaw nowy rekord (mapowanie pól)
        insert_sql = """
            INSERT INTO places (
                cid, title, address, latitude, longitude, rating, rating_count,
                category, phone_number, website, email, email_checked, email_source,
                created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
            )
        """
        
        cursor.execute(insert_sql, (
            cid,
            record.get('name') or '',
            record.get('address') or '',
            record.get('lat') or 0,
            record.get('lng') or 0,
            record.get('rating'),
            record.get('rating_count'),
            record.get('category') or '',
            record.get('phone') or '',
            record.get('website') or '',
            record.get('email') or None,
            bool(record.get('mail_checked', 0)),
            'oldplaces_migration',
            record.get('date_added') or now,
            now
        ))
        
        stats['inserted'] += 1
        existing_cids.add(cid)  # Dodaj do zbioru żeby uniknąć duplikatów w tej samej sesji
        
        # Co 100 rekordów commituj i wyświetl postęp
        if stats['inserted'] % 100 == 0:
            conn.commit()
            print(f"  Wstawiono {stats['inserted']} rekordów...")
        
    except Exception as e:
        stats['errors'] += 1
        print(f"  ⚠️ Błąd dla CID {record.get('cid')}: {e}")
        conn.rollback()
        continue

# Finalny commit
conn.commit()
conn.close()

# Podsumowanie
print()
print("=" * 70)
print("PODSUMOWANIE MIGRACJI")
print("=" * 70)
print(f"✓ Wstawiono nowych rekordów: {stats['inserted']}")
print(f"  Pominięto (duplikat CID): {stats['skipped_duplicate']}")
print(f"  Pominięto (brak CID): {stats['skipped_no_cid']}")
print(f"  Zaktualizowano email: {stats['updated_email']}")
print(f"  Błędy: {stats['errors']}")
print("=" * 70)
print("Zakończono!")

