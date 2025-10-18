#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Formatuje plik districts.csv do finalnego formatu:
Kolumna 1: "Miasto Dzielnica" (np. "Warszawa Ochota")
Kolumna 2: Populacja całego miasta (ta sama dla wszystkich dzielnic)
"""

import csv

# Wczytaj mapę miasto -> populacja z cities.csv
city_population = {}
with open('scripts/cities.csv', 'r', encoding='utf-8') as f:
    reader = csv.reader(f)
    for row in reader:
        if len(row) >= 5:
            city_name = row[0].strip()
            population = row[4].strip()  # 5-ta kolumna (index 4) to populacja
            city_population[city_name] = population

print(f"✓ Wczytano populację dla {len(city_population)} miast")

# Wczytaj districts.csv i przekształć format
output_lines = []
cities_with_districts = set()

with open('scripts/districts.csv', 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        
        # Pomiń komentarze i puste linie
        if line.startswith('#') or not line:
            continue
        
        # Parsuj "Miasto,Dzielnica"
        parts = line.split(',', 1)
        if len(parts) != 2:
            continue
        
        city = parts[0].strip()
        district = parts[1].strip()
        
        # Pobierz populację miasta
        population = city_population.get(city, '0')
        
        # Format: "Miasto Dzielnica,populacja"
        formatted_line = f"{city} {district},{population}"
        output_lines.append(formatted_line)
        cities_with_districts.add(city)

print(f"✓ Przetworzono {len(output_lines)} kombinacji miasto-dzielnica")
print(f"✓ Miasta z dzielnicami: {len(cities_with_districts)}")

# Zapisz do nowego pliku
output_file = 'scripts/city_districts.csv'
with open(output_file, 'w', encoding='utf-8') as f:
    f.write("# Format: Miasto Dzielnica,Populacja\n")
    for line in output_lines:
        f.write(line + '\n')

print(f"\n✓ Zapisano do: {output_file}")
print(f"\nPrzykładowe linie:")
for line in output_lines[:5]:
    print(f"  {line}")
print(f"  ...")
for line in output_lines[-3:]:
    print(f"  {line}")

print(f"\n✓ Miasta z dzielnicami ({len(cities_with_districts)}):")
for city in sorted(cities_with_districts):
    count = sum(1 for line in output_lines if line.startswith(city + ' '))
    pop = city_population.get(city, '?')
    print(f"  - {city}: {count} dzielnic (populacja: {pop})")

