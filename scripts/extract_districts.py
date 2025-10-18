#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Skrypt do jednorazowego wyekstrahowania dzielnic dla największych miast Polski.
Zapisuje w formacie: Miasto Dzielnica (jeden wiersz = jedna dzielnica)
"""

import os
import csv
import time
import requests
from dotenv import load_dotenv

load_dotenv()
SERPER_API_KEY = os.getenv('SERPER_API_KEY')

if not SERPER_API_KEY:
    print("❌ SERPER_API_KEY nie znaleziony w .env")
    exit(1)


def fetch_places_for_city(city_name, test_phrase="fryzjer", pages=5):
    """Pobiera miejsca dla miasta z Serper API (wiele stron)."""
    url = 'https://google.serper.dev/places'
    headers = {
        'X-API-KEY': SERPER_API_KEY,
        'Content-Type': 'application/json'
    }
    
    all_places = []
    
    for page in range(1, pages + 1):
        query = f"{test_phrase} {city_name}"
        payload = {'q': query, 'gl': 'pl', 'page': page}
        
        try:
            response = requests.post(url, headers=headers, json=payload, timeout=30)
            
            if response.status_code != 200:
                break
            
            data = response.json()
            
            if 'places' not in data or not data['places']:
                break
            
            all_places.extend(data['places'])
            
            # Jeśli mniej niż 10 wyników, nie ma sensu próbować kolejnej strony
            if len(data['places']) < 10:
                break
            
            time.sleep(0.5)  # Przerwa między stronami
            
        except Exception as e:
            print(f"    Błąd strony {page}: {e}")
            break
    
    return all_places


def extract_districts_from_addresses(places, city_name):
    """
    Wyciąga dzielnice z adresów miejsc.
    Dzielnice w Polsce często są w formacie: "ulica, dzielnica, kod-pocztowy miasto"
    """
    districts = set()
    
    for place in places:
        address = place.get('address', '')
        
        if not address:
            continue
        
        # Spróbuj różnych metod ekstrakcji
        
        # Metoda 1: Split po przecinkach
        parts = [p.strip() for p in address.split(',')]
        
        # Szukamy części która:
        # - Nie jest numerem/ulicą (zazwyczaj pierwsza część)
        # - Nie zawiera kodu pocztowego
        # - Nie jest samą nazwą miasta
        # - Ma więcej niż 3 znaki
        
        for part in parts:
            # Pomijamy ulice (zazwyczaj zawierają cyfry)
            if any(char.isdigit() for char in part):
                continue
            
            # Pomijamy część z kodem pocztowym
            if '-' in part and any(char.isdigit() for char in part):
                continue
            
            # Pomijamy samą nazwę miasta
            if part.lower() == city_name.lower():
                continue
            
            # Pomijamy za krótkie
            if len(part) < 3:
                continue
            
            # Pomijamy województwa
            if 'woj' in part.lower() or 'opolskie' in part.lower() or 'śląskie' in part.lower():
                continue
            
            # Jeśli część zawiera nazwę miasta jako część (np. "Warszawa-Mokotów")
            if city_name.lower() in part.lower():
                # Wyciągnij tylko dzielnicę
                district = part.replace(city_name, '').replace('-', '').strip()
                if district and len(district) >= 3:
                    districts.add(district)
            else:
                # To może być dzielnica
                districts.add(part)
    
    return sorted(list(districts))


def load_cities_from_csv(limit=30):
    """Wczytuje miasta z CSV (tylko N pierwszych - największe)."""
    cities = []
    with open('scripts/cities.csv', 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for i, row in enumerate(reader):
            if i >= limit:
                break
            if len(row) >= 1:
                city_name = row[0].strip()
                cities.append(city_name)
    return cities


def main():
    print("=" * 70)
    print("EKSTRAKCJA DZIELNIC DLA NAJWIĘKSZYCH MIAST POLSKI")
    print("=" * 70)
    print()
    
    # Pytaj użytkownika ile miast przetwarzać
    print("Ile największych miast przetworzyć?")
    print("  Sugestie: 10 (~50 zapytań), 20 (~100 zapytań), 30 (~150 zapytań)")
    
    try:
        limit = int(input("Liczba miast [domyślnie 20]: ") or "20")
    except:
        limit = 20
    
    print(f"\n✓ Przetwarzam {limit} największych miast")
    print()
    
    cities = load_cities_from_csv(limit)
    print(f"✓ Wczytano {len(cities)} miast")
    print()
    
    all_results = []
    total_queries = 0
    
    for i, city_name in enumerate(cities, 1):
        print(f"{i}/{len(cities)}. {city_name}")
        
        # Pobierz miejsca (5 stron = 5 zapytań Serper)
        places = fetch_places_for_city(city_name, pages=5)
        total_queries += min(5, (len(places) // 10) + 1)  # Szacowana liczba zapytań
        
        print(f"  📍 Znaleziono {len(places)} miejsc")
        
        # Wyciągnij dzielnice
        districts = extract_districts_from_addresses(places, city_name)
        
        if districts:
            print(f"  ✓ Dzielnice ({len(districts)}): {', '.join(districts[:5])}" + 
                  (f" (+{len(districts)-5} więcej)" if len(districts) > 5 else ""))
            
            for district in districts:
                all_results.append(f"{city_name} {district}")
        else:
            print(f"  ⚠️  Brak dzielnic")
        
        print()
        time.sleep(1)  # Przerwa między miastami
    
    # Zapisz wyniki
    output_file = 'scripts/districts_output.txt'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("# Format: Miasto Dzielnica (jeden wiersz = jedna dzielnica)\n")
        f.write("# Możesz ręcznie uporządkować do CSV\n")
        f.write("\n")
        for result in all_results:
            f.write(result + "\n")
    
    # Podsumowanie
    print("=" * 70)
    print("PODSUMOWANIE")
    print("=" * 70)
    print(f"✓ Przetworzono miast: {len(cities)}")
    print(f"✓ Znaleziono kombinacji miasto-dzielnica: {len(all_results)}")
    print(f"✓ Zużyte kredyty Serper: ~{total_queries}")
    print(f"\n✓ Wyniki zapisane w: {output_file}")
    print()
    print("Teraz możesz:")
    print("  1. Otworzyć plik districts_output.txt")
    print("  2. Przejrzeć i poprawić dzielnice (usunąć błędne)")
    print("  3. Skopiować do CSV w odpowiednim formacie")
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

