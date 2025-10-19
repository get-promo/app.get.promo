#!/usr/bin/env python3
import re
import requests
import warnings
warnings.filterwarnings('ignore')

url = "https://dreamhouse.krishome.pl/"
print(f"Testuję: {url}\n")

try:
    r = requests.get(url, timeout=5, verify=False, headers={'User-Agent': 'Mozilla/5.0'})
    html = r.text
    print(f"✓ Pobrano {len(html)} znaków\n")
    
    # Pokaż fragment z mailto
    if 'mailto' in html.lower():
        print("✓ Znaleziono 'mailto' w HTML!\n")
        # Pokaż kontekst
        idx = html.lower().find('mailto')
        print(f"Fragment HTML:\n{html[max(0,idx-100):idx+200]}\n")
    else:
        print("❌ NIE MA 'mailto' w HTML\n")
    
    # Test regexów
    print("=" * 60)
    print("TEST REGEXÓW:\n")
    
    # Pattern 1: podstawowy email
    emails1 = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', html)
    print(f"1. Podstawowy pattern: {len(emails1)} emaili")
    if emails1:
        for e in emails1[:5]:
            print(f"   - {e}")
    
    # Pattern 2: mailto:
    emails2 = re.findall(r'mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})', html, re.I)
    print(f"\n2. Mailto pattern: {len(emails2)} emaili")
    if emails2:
        for e in emails2[:5]:
            print(f"   - {e}")
    
    # Pattern 3: href mailto
    emails3 = re.findall(r'href=["\']?mailto:([^"\'>\s]+)', html, re.I)
    print(f"\n3. Href mailto pattern: {len(emails3)} emaili")
    if emails3:
        for e in emails3[:5]:
            print(f"   - {e}")
    
except Exception as e:
    print(f"❌ Błąd: {e}")

