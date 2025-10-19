#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Debug konkretnej strony dreamhouse
"""

import re
import requests
import warnings
from urllib.parse import urljoin

warnings.filterwarnings('ignore')
import urllib3
urllib3.disable_warnings()

url = "https://dreamhouse.krishome.pl/"

print("=" * 70)
print(f"TEST: {url}")
print("=" * 70)

# 1. Pobierz stronę
print("\n1. Pobieram stronę...")
try:
    response = requests.get(url, timeout=10, verify=False, 
                           headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'})
    
    if response.status_code != 200:
        print(f"❌ Status: {response.status_code}")
        exit(1)
    
    html = response.text
    print(f"✓ Pobrano {len(html):,} znaków")
    
except Exception as e:
    print(f"❌ Błąd: {e}")
    exit(1)

# 2. Znajdź emaile - kod z parseEmails.py
print("\n2. Szukam emaili w HTML (kod z parseEmails)...")

emails = []
file_extensions = ['.png', '.jpg', '.jpeg', '.svg', '.gif', '.webp', '.bmp']

# 2a. Regex dla emaili w tekście
pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
matches = re.findall(pattern, html)

print(f"   Regex znalazł {len(matches)} potencjalnych emaili")

for email in matches:
    from urllib.parse import unquote
    email = unquote(email)
    
    is_file = any(email.lower().endswith(ext) for ext in file_extensions)
    
    if not is_file and '@' in email:
        if len(email) > 5 and '.' in email.split('@')[1]:
            emails.append(email.lower())
            print(f"   ✓ {email}")

# 2b. Szukaj mailto: w HTML
mailto_pattern = r'mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})'
mailto_matches = re.findall(mailto_pattern, html, re.IGNORECASE)

print(f"\n   Mailto pattern znalazł {len(mailto_matches)} emaili")

for email in mailto_matches:
    email = email.split('?')[0].strip().lower()
    if '@' in email and len(email) > 5:
        emails.append(email)
        print(f"   ✓ mailto: {email}")

# Wynik
emails = list(set(emails))

print("\n" + "=" * 70)
if emails:
    print(f"✅ ZNALEZIONO {len(emails)} EMAILI:")
    for e in emails:
        print(f"   • {e}")
else:
    print("❌ NIE ZNALEZIONO EMAILI")
    print("\nSzukam na stronach kontaktowych...")
    
    # 3. Znajdź linki kontaktowe
    contact_links = []
    
    # mailto
    mailto = re.findall(r'href=["\']?(mailto:[^"\'>\s]+)', html, re.IGNORECASE)
    contact_links.extend(mailto)
    print(f"   Mailto linki: {mailto}")
    
    # kontakt/contact
    link_pattern = r'href=["\']([^"\']*(?:kontakt|contact)[^"\']*)["\']'
    link_matches = re.findall(link_pattern, html, re.IGNORECASE)
    
    print(f"   Kontakt linki: {link_matches[:5]}")
    
    for href in link_matches[:3]:
        if href.startswith('tel:'):
            continue
        absolute_url = urljoin(url, href)
        contact_links.append(absolute_url)
    
    print(f"\n   Sprawdzam {len(contact_links[:3])} linki kontaktowe...")
    
    for link in contact_links[:3]:
        if link.startswith('mailto:'):
            email = link[7:].split('?')[0]
            print(f"   ✓ Email z mailto: {email}")
            continue
        
        print(f"   Sprawdzam: {link}")
        try:
            r = requests.get(link, timeout=5, verify=False, 
                           headers={'User-Agent': 'Mozilla/5.0'})
            if r.status_code == 200:
                contact_emails = re.findall(pattern, r.text)
                if contact_emails:
                    print(f"   ✓ Znaleziono: {contact_emails[0]}")
                    break
        except:
            pass

print("=" * 70)

