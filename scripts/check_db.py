#!/usr/bin/env python3
import pymysql
import os
from dotenv import load_dotenv

# Wczytaj konfigurację z .env
load_dotenv()

conn = pymysql.connect(
    host=os.getenv('DB_HOST', '127.0.0.1'),
    port=int(os.getenv('DB_PORT', 3306)),
    user=os.getenv('DB_USERNAME', 'root'),
    password=os.getenv('DB_PASSWORD', ''),
    database=os.getenv('DB_DATABASE', 'laravel'),
    charset='utf8mb4'
)

cursor = conn.cursor()
cursor.execute('SELECT COUNT(*) FROM places')
count = cursor.fetchone()[0]
print(f'✓ Liczba rekordów w places: {count}')

if count > 0:
    cursor.execute('''
        SELECT title, city_name, city_size, search_phrase, 
               rating, rating_count, address, latitude, longitude
        FROM places LIMIT 1
    ''')
    row = cursor.fetchone()
    print(f'\n✓ Pierwszy zapisany rekord:')
    print(f'  Title: {row[0]}')
    print(f'  City: {row[1]} (wielkość: {row[2]})')
    print(f'  Phrase: {row[3]}')
    print(f'  Rating: {row[4]} ({row[5]} opinii)')
    print(f'  Address: {row[6]}')
    print(f'  Coords: {row[7]}, {row[8]}')

conn.close()

