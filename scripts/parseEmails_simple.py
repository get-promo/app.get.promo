#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PROSTSZA WERSJA - bez wątków, do testowania.
"""

import os
import sys
import re
import requests
import pymysql
import warnings
from dotenv import load_dotenv
from urllib.parse import urljoin

# Wyłącz ostrzeżenia
warnings.filterwarnings('ignore')
import urllib3
urllib3.disable_warnings()

# Config
load_dotenv()
DB_HOST = os.getenv('DB_HOST')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE')
DB_USERNAME = os.getenv('DB_USERNAME')
DB_PASSWORD = os.getenv('DB_PASSWORD')

def get_website(url, timeout=3):
    """Pobierz stronę."""
    try:
        r = requests.get(url, timeout=timeout, verify=False, 
                        headers={'User-Agent': 'Mozilla/5.0'})
        return r.text if r.status_code == 200 else ''
    except:
        return ''

def find_emails(html):
    """Znajdź emaile."""
    if not html:
        return []
    pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
    matches = re.findall(pattern, html)
    emails = []
    for e in matches:
        e = e.lower()
        if not any(e.endswith(ext) for ext in ['.png','.jpg','.svg']):
            if len(e) > 5 and '.' in e.split('@')[1]:
                emails.append(e)
    # mailto:
    mailto = re.findall(r'mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})', html, re.I)
    emails.extend([m.lower() for m in mailto])
    return list(set(emails))

def find_contact_links(html, base):
    """Znajdź linki kontaktowe."""
    links = []
    # mailto
    mailto = re.findall(r'href=["\']?(mailto:[^"\'>\s]+)', html, re.I)
    links.extend(mailto)
    # kontakt/contact
    pattern = r'href=["\']([^"\']*(?:kontakt|contact)[^"\']*)["\']'
    for href in re.findall(pattern, html, re.I):
        if not href.startswith('tel:'):
            links.append(urljoin(base, href))
    return list(set(links))[:3]

# Połącz z bazą
print("Łączę z bazą...")
conn = pymysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USERNAME, 
                      password=DB_PASSWORD, database=DB_DATABASE, charset='utf8mb4')

cursor = conn.cursor(pymysql.cursors.DictCursor)
cursor.execute("""
    SELECT id, website FROM places
    WHERE (email IS NULL OR email = '')
      AND website IS NOT NULL AND website != ''
      AND (email_checked = 0 OR email_checked IS NULL)
      AND email_checked_at IS NULL
    ORDER BY id ASC
    LIMIT 100
""")

places = cursor.fetchall()
print(f"Znaleziono {len(places)} miejsc")

processed = 0
found = 0

for place in places:
    pid = place['id']
    web = place['website']
    
    print(f"[{pid}] {web}")
    
    email = None
    
    # mailto w URL
    if web.startswith('mailto:'):
        email = web[7:].split('?')[0].strip()
    elif 'instagram.com' in web:
        cursor.execute("UPDATE places SET email_checked=1 WHERE id=%s", (pid,))
        conn.commit()
        processed += 1
        continue
    else:
        html = get_website(web)
        if html:
            emails = find_emails(html)
            if not emails:
                for link in find_contact_links(html, web):
                    if link.startswith('mailto:'):
                        email = link[7:].split('?')[0]
                        break
                    chtml = get_website(link)
                    if chtml:
                        emails = find_emails(chtml)
                        if emails:
                            break
            if emails and not email:
                email = emails[0]
    
    if email:
        print(f"  ✓ Email: {email}")
        cursor.execute("UPDATE places SET email=%s, email_checked=1, email_checked_at=NOW(), email_source='scan' WHERE id=%s", 
                      (email, pid))
        found += 1
    else:
        print(f"  - Brak")
        cursor.execute("UPDATE places SET email_checked=1 WHERE id=%s", (pid,))
    
    conn.commit()
    processed += 1

print(f"\nPrzetworzono: {processed}, Znaleziono: {found}")
conn.close()

