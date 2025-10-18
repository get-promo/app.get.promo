#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Test skryptu do sprawdzenia dla jakich miast Serper zwraca dzielnice.
"""

import os
import csv
import json
import time
import requests
from dotenv import load_dotenv

# Wczytaj konfigurację
load_dotenv()
SERPER_API_KEY = os.getenv('SERPER_API_KEY')

if not SERPER_API_KEY:
    print("❌ SERPER_API_KEY nie znaleziony w .env")
    exit(1)

def fetch_districts_for_city(city_name, test_phrase="fryzjer"):
    """
    Sprawdza czy dla danego miasta są dostępne dzielnice.
    Używa Serper + Google Places API Details (jak stary skrypt PHP).
    """
    # Krok 1: Pobierz miejsca z Serper
    url = 'https://google.serper.dev/places'
    headers = {
        'X-API-KEY': SERPER_API_KEY,
        'Content-Type': 'application/json'
    }
    
    query = f"{test_phrase} {city_name}"
    payload = {
        'q': query,
        'gl': 'pl',
        'page': 1
    }
    
    try:
        response = requests.post(url, headers=headers, json=payload, timeout=30)
        
        if response.status_code != 200:
            print(f"  ❌ Błąd Serper API: {response.status_code}")
            return None
        
        data = response.json()
        
        if 'places' not in data or not data['places']:
            print(f"  ⚠️  Brak wyników z Serper")
            return []
        
        # Krok 2: Dla każdego miejsca pobierz szczegóły z Google Places API
        # i znajdź sublocality_level_1 (dzielnice)
        districts = set()
        google_api_key = os.getenv('GOOGLE_PLACES_API_KEY')
        
        if not google_api_key:
            print(f"  ⚠️  GOOGLE_PLACES_API_KEY nie znaleziony w .env")
            return []
        
        print(f"  📍 Znaleziono {len(data['places'])} miejsc, sprawdzam dzielnice...")
        
        for place in data['places'][:5]:  # Sprawdź tylko pierwsze 5 miejsc żeby oszczędzić zapytania
            cid = place.get('cid')
            if not cid:
                continue
            
            try:
                # Pobierz szczegóły miejsca z Google Places API
                details_url = f"https://maps.googleapis.com/maps/api/place/details/json?key={google_api_key}&cid={cid}"
                details_response = requests.get(details_url, timeout=10)
                
                if details_response.status_code != 200:
                    continue
                
                details_data = details_response.json()
                
                if 'result' not in details_data or 'address_components' not in details_data['result']:
                    continue
                
                # Szukaj sublocality_level_1 (dzielnice)
                for component in details_data['result']['address_components']:
                    if 'types' in component and 'sublocality_level_1' in component['types']:
                        district = component.get('long_name', '')
                        if district:
                            districts.add(district)
                
                time.sleep(0.2)  # Krótka przerwa między zapytaniami Google API
                
            except Exception as e:
                continue
        
        return list(districts)
        
    except Exception as e:
        print(f"  ❌ Błąd: {e}")
        return None


def load_cities_from_csv():
    """Wczytuje miasta z CSV."""
    cities = []
    with open('scripts/cities.csv', 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for row in reader:
            if len(row) >= 3:
                city_name = row[0].strip()
                population = row[2].strip()
                cities.append({'name': city_name, 'population': population})
    return cities


def main():
    """Główna funkcja testowa."""
    print("=" * 70)
    print("TEST: Sprawdzanie dostępności dzielnic dla miast w Polsce")
    print("=" * 70)
    print()
    
    cities = load_cities_from_csv()
    print(f"✓ Wczytano {len(cities)} miast z CSV")
    print()
    
    # Testujemy co 5 miast, żeby nie zużyć za dużo kredytów
    # Możesz zmienić step na 1 jeśli chcesz testować każde miasto
    test_step = 5
    
    results = []
    
    print("Testowanie miast (co 5-te miasto):")
    print("-" * 70)
    
    for i in range(0, min(len(cities), 50), test_step):  # Test pierwszych 50 miast
        city = cities[i]
        city_name = city['name']
        population = city['population']
        
        print(f"\n{i+1}. {city_name} (populacja: {population})")
        
        districts = fetch_districts_for_city(city_name)
        
        if districts is None:
            result = "ERROR"
            districts_count = 0
        elif len(districts) == 0:
            result = "BRAK"
            districts_count = 0
        else:
            result = "ZNALEZIONO"
            districts_count = len(districts)
            print(f"  ✓ Dzielnice: {', '.join(districts[:3])}" + 
                  (f" (+{len(districts)-3} więcej)" if len(districts) > 3 else ""))
        
        results.append({
            'position': i + 1,
            'city': city_name,
            'population': population,
            'districts_count': districts_count,
            'result': result,
            'districts': districts if districts else []
        })
        
        # Krótka przerwa między zapytaniami
        time.sleep(1)
    
    # Podsumowanie
    print("\n" + "=" * 70)
    print("PODSUMOWANIE")
    print("=" * 70)
    print()
    
    print(f"{'Poz':<5} {'Miasto':<25} {'Populacja':<10} {'Dzielnice':<10} {'Status'}")
    print("-" * 70)
    
    for r in results:
        print(f"{r['position']:<5} {r['city']:<25} {r['population']:<10} {r['districts_count']:<10} {r['result']}")
    
    # Znajdź granicę
    print("\n" + "=" * 70)
    print("WNIOSKI")
    print("=" * 70)
    
    cities_with_districts = [r for r in results if r['districts_count'] > 0]
    
    if cities_with_districts:
        last_city = cities_with_districts[-1]
        print(f"\n✓ Ostatnie miasto z dzielnicami: {last_city['city']} (pozycja {last_city['position']})")
        print(f"  Populacja: {last_city['population']}")
        print(f"  Liczba dzielnic: {last_city['districts_count']}")
        
        # Sugestia granicy
        suggested_limit = last_city['position']
        print(f"\n💡 SUGEROWANA GRANICA: pierwsze {suggested_limit} miast")
        print(f"\n📊 Kalkulacja kredytów z dzielnicami dla {suggested_limit} miast:")
        
        # Zakładamy średnio 5 dzielnic na miasto
        avg_districts = 5
        queries_for_districts = suggested_limit * 5  # pobieranie dzielnic
        queries_for_places = 72 * suggested_limit * avg_districts * 3  # frazy × miasta × dzielnice × strony
        remaining_cities = len(cities) - suggested_limit
        queries_without_districts = 72 * remaining_cities * 3  # pozostałe miasta bez dzielnic
        
        total_queries = queries_for_districts + queries_for_places + queries_without_districts
        
        print(f"  - Pobieranie dzielnic: {queries_for_districts:,} zapytań")
        print(f"  - Miejsca z dzielnicami: {queries_for_places:,} zapytań")
        print(f"  - Miejsca bez dzielnic: {queries_without_districts:,} zapytań")
        print(f"  RAZEM: ~{total_queries:,} kredytów")
    else:
        print("\n⚠️  Żadne z testowanych miast nie ma dzielnic")
    
    # Zapisz szczegółowe wyniki
    with open('scripts/districts_test_results.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    
    print(f"\n✓ Szczegółowe wyniki zapisane w: scripts/districts_test_results.json")
    print(f"\nZużyte kredyty w tym teście: ~{len(results)} kredytów")


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\n\nPrzerwano przez użytkownika")
        exit(0)
    except Exception as e:
        print(f"\n❌ Błąd: {e}")
        exit(1)

