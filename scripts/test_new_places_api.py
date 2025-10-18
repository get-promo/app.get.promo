#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Test nowego Google Places API (v1) dla pobierania dzielnic.
"""

import os
import json
import requests
from dotenv import load_dotenv

load_dotenv()
SERPER_API_KEY = os.getenv('SERPER_API_KEY')
GOOGLE_API_KEY = os.getenv('GOOGLE_PLACES_API_KEY')

print("Test nowego Google Places API")
print("=" * 70)

# Krok 1: Pobierz miejsca z Serper dla Warszawy
print("\n1. Pobieranie miejsc z Serper dla Warszawy...")
response = requests.post(
    'https://google.serper.dev/places',
    headers={
        'X-API-KEY': SERPER_API_KEY,
        'Content-Type': 'application/json'
    },
    json={
        'q': 'fryzjer Warszawa',
        'gl': 'pl',
        'page': 1
    }
)

if not response.ok:
    print(f"❌ Błąd Serper: {response.status_code}")
    exit(1)

data = response.json()
first_place = data['places'][0]

print(f"✓ Znaleziono {len(data['places'])} miejsc")
print(f"  Pierwsze: {first_place.get('title')}")
print(f"  Adres: {first_place.get('address')}")

# Krok 2: Pobierz place_id używając nowego API (searchText)
print("\n2. Wyszukiwanie place_id przez nowe Places API...")

search_response = requests.post(
    'https://places.googleapis.com/v1/places:searchText',
    headers={
        'X-Goog-Api-Key': GOOGLE_API_KEY,
        'X-Goog-FieldMask': 'places.id,places.displayName,places.formattedAddress',
        'Content-Type': 'application/json'
    },
    json={
        'textQuery': f"{first_place.get('title')} {first_place.get('address')}"
    }
)

print(f"Status: {search_response.status_code}")

if not search_response.ok:
    print(f"❌ Błąd Places API: {search_response.text[:500]}")
    exit(1)

search_data = search_response.json()

if 'places' not in search_data or not search_data['places']:
    print("❌ Brak wyników")
    exit(1)

place_id = search_data['places'][0]['id']
print(f"✓ Znaleziono place_id: {place_id}")

# Krok 3: Pobierz szczegóły miejsca z addressComponents
print("\n3. Pobieranie szczegółów z addressComponents...")

details_response = requests.get(
    f'https://places.googleapis.com/v1/places/{place_id}',
    headers={
        'X-Goog-Api-Key': GOOGLE_API_KEY,
        'X-Goog-FieldMask': 'displayName,formattedAddress,addressComponents'
    }
)

print(f"Status: {details_response.status_code}")

if not details_response.ok:
    print(f"❌ Błąd Details: {details_response.text[:500]}")
    exit(1)

details_data = details_response.json()

print(f"\n✓ Dane miejsca:")
print(f"  Nazwa: {details_data.get('displayName', {}).get('text')}")
print(f"  Adres: {details_data.get('formattedAddress')}")

# Krok 4: Wyciągnij dzielnice z addressComponents
print(f"\n4. Address Components:")

if 'addressComponents' in details_data:
    for component in details_data['addressComponents']:
        types = component.get('types', [])
        long_name = component.get('longText', '')
        
        print(f"  - {long_name} | types: {types}")
        
        # sublocality_level_1 = dzielnica
        if 'sublocality_level_1' in types or 'sublocality' in types:
            print(f"    ✓ ✓ ✓ DZIELNICA: {long_name} ✓ ✓ ✓")
else:
    print("  ⚠️ Brak addressComponents")

print("\n" + "=" * 70)
print("Test zakończony!")

