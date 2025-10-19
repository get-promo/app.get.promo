#!/usr/bin/env python3
import pymysql
import os
from dotenv import load_dotenv

load_dotenv()

conn = pymysql.connect(
    host=os.getenv('DB_HOST'),
    port=int(os.getenv('DB_PORT', 3306)),
    user=os.getenv('DB_USERNAME'),
    password=os.getenv('DB_PASSWORD'),
    database=os.getenv('DB_DATABASE'),
    charset='utf8mb4'
)

cursor = conn.cursor()

# Sprawdź duplikaty
cursor.execute("""
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT cid) as unique_cids,
        COUNT(*) - COUNT(DISTINCT cid) as duplicates
    FROM places
    WHERE cid IS NOT NULL AND cid != ''
""")

result = cursor.fetchone()
print(f"Total records: {result[0]}")
print(f"Unique CIDs: {result[1]}")
print(f"Duplicates: {result[2]}")

if result[2] > 0:
    print(f"\n⚠️ Znaleziono {result[2]} duplikatów!")
    
    # Pokaż duplikaty
    cursor.execute("""
        SELECT cid, COUNT(*) as count
        FROM places
        WHERE cid IS NOT NULL AND cid != ''
        GROUP BY cid
        HAVING COUNT(*) > 1
        ORDER BY count DESC
        LIMIT 10
    """)
    
    print("\nTop 10 duplikatów:")
    for row in cursor.fetchall():
        print(f"  CID {row[0]}: {row[1]} razy")
else:
    print("\n✅ Brak duplikatów!")

conn.close()

