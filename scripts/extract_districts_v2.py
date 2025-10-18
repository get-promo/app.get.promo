#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Ekstrakcja dzielnic dla największych miast Polski.
Używa Serper + nowe Google Places API v1 z addressComponents.
Zapisuje w formacie: Miasto Dzielnica (jeden wiersz = jedna dzielnica)
"""

import os
import csv
import time
import requests
from dotenv import load_dotenv

load_dotenv()
SERPER_API_KEY = os.getenv('SERPER_API_KEY')
GOOGLE_API_KEY = os.getenv('GOOGLE_PLACES_API_KEY')

if not SERPER_API_KEY or not GOOGLE_API_KEY:
    print("❌ Brak kluczy API w .env (SERPER_API_KEY, GOOGLE_PLACES_API_KEY)")
    exit(1)


def fetch_districts_for_city(city_name, test_phrase="fryzjer", pages=3):
    """
    Pobiera dzielnice dla miasta używając Serper (wiele stron) + nowe Google Places API.
    """
    districts = set()
    all_places = []
    
    # Krok 1: Pobierz miejsca z Serper (3 strony)
    try:
        for page in range(1, pages + 1):
            serper_response = requests.post(
                'https://google.serper.dev/places',
                headers={
                    'X-API-KEY': SERPER_API_KEY,
                    'Content-Type': 'application/json'
                },
                json={
                    'q': f'{test_phrase} {city_name}',
                    'gl': 'pl',
                    'page': page
                },
                timeout=30
            )
            
            if serper_response.status_code != 200:
                print(f"  ⚠️  Błąd Serper strona {page}: {serper_response.status_code}")
                break
            
            serper_data = serper_response.json()
            places = serper_data.get('places', [])
            
            if not places:
                break
            
            all_places.extend(places)
            
            # Jeśli mniej niż 10, nie ma sensu próbować kolejnej strony
            if len(places) < 10:
                break
            
            time.sleep(0.5)  # Przerwa między stronami
        
        if not all_places:
            print(f"  ⚠️  Brak miejsc")
            return []
        
        print(f"  📍 Znaleziono {len(all_places)} miejsc z {min(page, pages)} stron, sprawdzam dzielnice...")
        
        # Krok 2: Dla każdego miejsca pobierz dzielnicę
        for i, place in enumerate(all_places):
            try:
                title = place.get('title', '')
                address = place.get('address', '')
                
                if not title or not address:
                    continue
                
                # Krok 2a: Znajdź place_id przez searchText
                search_response = requests.post(
                    'https://places.googleapis.com/v1/places:searchText',
                    headers={
                        'X-Goog-Api-Key': GOOGLE_API_KEY,
                        'X-Goog-FieldMask': 'places.id',
                        'Content-Type': 'application/json'
                    },
                    json={
                        'textQuery': f'{title} {address}'
                    },
                    timeout=15
                )
                
                if search_response.status_code != 200:
                    continue
                
                search_data = search_response.json()
                
                if 'places' not in search_data or not search_data['places']:
                    continue
                
                place_id = search_data['places'][0]['id']
                
                # Krok 2b: Pobierz addressComponents
                details_response = requests.get(
                    f'https://places.googleapis.com/v1/places/{place_id}',
                    headers={
                        'X-Goog-Api-Key': GOOGLE_API_KEY,
                        'X-Goog-FieldMask': 'addressComponents'
                    },
                    timeout=15
                )
                
                if details_response.status_code != 200:
                    continue
                
                details_data = details_response.json()
                
                # Krok 2c: Wyciągnij sublocality_level_1
                if 'addressComponents' in details_data:
                    for component in details_data['addressComponents']:
                        types = component.get('types', [])
                        
                        if 'sublocality_level_1' in types or 'sublocality' in types:
                            district_name = component.get('longText', '')
                            if district_name:
                                districts.add(district_name)
                
                time.sleep(0.3)  # Rate limiting
                
            except Exception as e:
                continue
        
        return sorted(list(districts))
        
    except Exception as e:
        print(f"  ❌ Błąd: {e}")
        return []


def load_cities_from_csv(offset=0, limit=15):
    """Wczytuje miasta z CSV z offsetem (np. 15-30)."""
    cities = []
    with open('scripts/cities.csv', 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for i, row in enumerate(reader):
            # Pomijamy pierwsze 'offset' miast
            if i < offset:
                continue
            # Kończymy po 'limit' miastach od offsetu
            if i >= offset + limit:
                break
            if len(row) >= 1:
                city_name = row[0].strip()
                cities.append(city_name)
    return cities


def main():
    print("=" * 70)
    print("EKSTRAKCJA DZIELNIC - NOWE GOOGLE PLACES API v1")
    print("=" * 70)
    print()
    
    # Pytaj użytkownika o zakres miast
    print("Od którego miasta zacząć?")
    print("  0 = od początku (Warszawa)")
    print("  15 = od 16-tego miasta (Rzeszów)")
    print("  30 = od 31-szego miasta (Dąbrowa Górnicza)")
    print()
    
    try:
        offset = int(input("Offset [domyślnie 0]: ") or "0")
    except:
        offset = 0
    
    print("\nIle miast przetworzyć od tego miejsca?")
    print("  Sugestie (3 strony Serper per miasto):")
    print("    10 miast = ~200-300 zapytań Google + 30 Serper")
    print("    15 miast = ~300-450 zapytań Google + 45 Serper")
    print()
    
    try:
        limit = int(input("Liczba miast [domyślnie 15]: ") or "15")
    except:
        limit = 15
    
    print(f"\n✓ Przetwarzam miasta {offset+1}-{offset+limit}")
    print()
    
    cities = load_cities_from_csv(offset=offset, limit=limit)
    print(f"✓ Wczytano {len(cities)} miast")
    print()
    
    all_results = []
    total_serper = 0
    total_google = 0
    
    for i, city_name in enumerate(cities, 1):
        print(f"{i}/{len(cities)}. {city_name}")
        
        # Pobierz dzielnice (3 strony)
        districts = fetch_districts_for_city(city_name, pages=3)
        
        total_serper += 3  # 3 zapytania Serper na miasto (3 strony)
        total_google += len(districts) * 2 if districts else 0  # searchText + details dla każdej dzielnicy
        
        if districts:
            print(f"  ✅ Dzielnice ({len(districts)}): {', '.join(districts)}")
            
            for district in districts:
                all_results.append(f"{city_name},{district}")
        else:
            print(f"  ⚠️  Brak dzielnic")
        
        print()
        time.sleep(1)
    
    # Zapisz wyniki (dopisz jeśli offset > 0)
    output_file = 'scripts/districts.csv'
    mode = 'a' if offset > 0 else 'w'  # append jeśli nie od początku
    
    with open(output_file, mode, encoding='utf-8') as f:
        if offset == 0:  # Tylko dla pierwszego razu pisz header
            f.write("# Format CSV: Miasto,Dzielnica\n")
        for result in all_results:
            f.write(result + "\n")
    
    # Podsumowanie
    print("=" * 70)
    print("PODSUMOWANIE")
    print("=" * 70)
    print(f"✓ Przetworzono miast: {len(cities)}")
    print(f"✓ Znaleziono kombinacji miasto-dzielnica: {len(all_results)}")
    print(f"✓ Zużyte kredyty:")
    print(f"  - Serper: ~{total_serper}")
    print(f"  - Google Places: ~{total_google}")
    print(f"\n✓ Wyniki zapisane w: {output_file}")
    print()
    print("Plik jest gotowy do użycia!")
    print()


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\n\nPrzerwano przez użytkownika")
        exit(0)
    except Exception as e:
        print(f"\n❌ Błąd: {e}")
        import traceback
        traceback.print_exc()
        exit(1)

