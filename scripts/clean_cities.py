#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Usuwa z cities.csv pojedyncze miasta, które mają dzielnice.
"""

# Miasta które mają dzielnice (nie mogą być pojedynczo)
cities_with_districts = {
    'Warszawa', 'Kraków', 'Łódź', 'Wrocław', 'Poznań',
    'Białystok', 'Częstochowa', 'Radom', 'Toruń', 'Gdynia',
    'Rybnik', 'Ruda Śląska', 'Gdańsk', 'Szczecin', 'Bydgoszcz',
    'Lublin', 'Bytom', 'Elbląg'
}

input_file = 'scripts/cities.csv'
output_lines = []

print("Czyszczenie cities.csv...")
print(f"Miasta z dzielnicami (do usunięcia pojedynczo): {len(cities_with_districts)}")
print()

removed_count = 0
kept_count = 0

with open(input_file, 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        
        # Pomiń puste linie i komentarze
        if not line or line.startswith('#'):
            output_lines.append(line)
            continue
        
        # Sprawdź czy to pojedyncze miasto (bez spacji w nazwie przed przecinkiem)
        parts = line.split(',')
        if len(parts) >= 2:
            location = parts[0].strip()
            
            # Jeśli zawiera spację, to jest "Miasto Dzielnica" - zachowaj
            if ' ' in location:
                output_lines.append(line)
                kept_count += 1
            else:
                # To pojedyncze miasto - sprawdź czy ma dzielnice
                if location in cities_with_districts:
                    print(f"  ❌ Usuwam: {location} (ma dzielnice)")
                    removed_count += 1
                else:
                    output_lines.append(line)
                    kept_count += 1
        else:
            output_lines.append(line)

# Zapisz wynik
with open(input_file, 'w', encoding='utf-8') as f:
    for line in output_lines:
        f.write(line + '\n')

print()
print(f"✓ Zachowano: {kept_count} lokalizacji")
print(f"✓ Usunięto: {removed_count} pojedynczych miast (mają dzielnice)")
print(f"✓ Zapisano do: {input_file}")

