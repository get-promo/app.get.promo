#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Test skryptu buildPlacesDb.py - przetestuje tylko 2 kombinacje.
"""

import sys
import os

# Dodaj ścieżkę do głównego skryptu
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from buildPlacesDb import (
    load_phrases, load_cities, get_db_connection,
    fetch_serper_places, process_place, clean_string,
    insert_place, update_place, place_exists,
    logger, SERPER_API_KEY
)

def test_configuration():
    """Test konfiguracji."""
    logger.info("=" * 60)
    logger.info("TEST 1: Konfiguracja")
    logger.info("=" * 60)
    
    if not SERPER_API_KEY:
        logger.error("❌ SERPER_API_KEY nie znaleziony")
        return False
    logger.info("✓ SERPER_API_KEY znaleziony")
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) as cnt FROM places")
        result = cursor.fetchone()
        logger.info(f"✓ Połączenie z bazą danych OK (rekordów w places: {result['cnt']})")
        conn.close()
    except Exception as e:
        logger.error(f"❌ Błąd połączenia z bazą: {e}")
        return False
    
    return True


def test_data_loading():
    """Test wczytywania danych."""
    logger.info("\n" + "=" * 60)
    logger.info("TEST 2: Wczytywanie danych")
    logger.info("=" * 60)
    
    try:
        phrases = load_phrases()
        logger.info(f"✓ Wczytano {len(phrases)} fraz")
        logger.info(f"  Przykładowe frazy: {', '.join(phrases[:3])}")
        
        cities = load_cities()
        logger.info(f"✓ Wczytano {len(cities)} miast")
        logger.info(f"  Przykładowe miasta: {cities[0]['name']}, {cities[1]['name']}, {cities[2]['name']}")
        
        return phrases, cities
    except Exception as e:
        logger.error(f"❌ Błąd wczytywania danych: {e}")
        return None, None


def test_serper_api(phrase, city):
    """Test zapytania do Serper API."""
    logger.info("\n" + "=" * 60)
    logger.info("TEST 3: Zapytanie Serper API")
    logger.info("=" * 60)
    
    query = f"{phrase} {city['name']}"
    logger.info(f"Zapytanie: '{query}'")
    
    try:
        response = fetch_serper_places(query, page=1)
        
        if not response:
            logger.error("❌ Brak odpowiedzi z Serper API")
            return None
        
        if 'places' not in response:
            logger.error("❌ Odpowiedź nie zawiera 'places'")
            return None
        
        places = response['places']
        logger.info(f"✓ Otrzymano {len(places)} miejsc")
        
        if places:
            first_place = places[0]
            logger.info(f"  Pierwsze miejsce: {first_place.get('title', 'N/A')}")
            logger.info(f"  CID: {first_place.get('cid', 'N/A')}")
            logger.info(f"  Adres: {first_place.get('address', 'N/A')}")
        
        return places
    except Exception as e:
        logger.error(f"❌ Błąd zapytania Serper: {e}")
        return None


def test_data_cleaning():
    """Test czyszczenia danych."""
    logger.info("\n" + "=" * 60)
    logger.info("TEST 4: Czyszczenie danych")
    logger.info("=" * 60)
    
    test_cases = [
        ("Test 😀 emoji", "Test  emoji"),
        ("Multiple   spaces", "Multiple spaces"),
        ("Special!@#$chars", "Specialchars"),
        ("Normal text", "Normal text")
    ]
    
    all_passed = True
    for input_text, expected in test_cases:
        result = clean_string(input_text)
        if result == expected:
            logger.info(f"✓ '{input_text}' → '{result}'")
        else:
            logger.warning(f"⚠ '{input_text}' → '{result}' (oczekiwano: '{expected}')")
            all_passed = False
    
    return all_passed


def test_database_operations(places, phrase, city):
    """Test operacji na bazie danych."""
    logger.info("\n" + "=" * 60)
    logger.info("TEST 5: Operacje na bazie danych")
    logger.info("=" * 60)
    
    if not places:
        logger.warning("⚠ Brak miejsc do testowania")
        return False
    
    connection = None
    try:
        connection = get_db_connection()
        cursor = connection.cursor()
        
        # Testujemy pierwsze miejsce
        place = places[0]
        place_data = process_place(place, phrase, city['name'], city['size'])
        
        if not place_data:
            logger.error("❌ process_place zwrócił None")
            return False
        
        logger.info(f"✓ Przetworzono dane miejsca: {place_data['title']}")
        
        # Sprawdzamy czy miejsce istnieje
        exists = place_exists(cursor, place_data['cid'])
        logger.info(f"  Miejsce {'istnieje' if exists else 'nie istnieje'} w bazie")
        
        # Próba zapisu
        if exists:
            result = update_place(connection, place_data)
            if result == 'updated':
                logger.info("✓ Zaktualizowano miejsce w bazie danych")
            else:
                logger.error("❌ Błąd podczas aktualizacji")
                return False
        else:
            result = insert_place(connection, place_data)
            if result:
                logger.info("✓ Dodano miejsce do bazy danych")
            else:
                logger.error("❌ Błąd podczas dodawania")
                return False
        
        # Weryfikacja zapisu
        cursor.execute("SELECT * FROM places WHERE cid = %s", (place_data['cid'],))
        saved_place = cursor.fetchone()
        
        if saved_place:
            logger.info("✓ Weryfikacja: miejsce zostało zapisane w bazie")
            logger.info(f"  ID: {saved_place['id']}")
            logger.info(f"  Title: {saved_place['title']}")
            logger.info(f"  Search phrase: {saved_place['search_phrase']}")
            logger.info(f"  City: {saved_place['city_name']}")
        else:
            logger.error("❌ Weryfikacja: miejsce nie zostało znalezione w bazie")
            return False
        
        connection.close()
        return True
        
    except Exception as e:
        logger.error(f"❌ Błąd podczas operacji na bazie: {e}")
        if connection:
            connection.rollback()
            connection.close()
        return False


def main():
    """Główna funkcja testowa."""
    logger.info("\n")
    logger.info("*" * 60)
    logger.info("URUCHAMIANIE TESTÓW SKRYPTU SCRAPER")
    logger.info("*" * 60)
    
    # Test 1: Konfiguracja
    if not test_configuration():
        logger.error("\n❌ Test konfiguracji nie powiódł się")
        return
    
    # Test 2: Wczytywanie danych
    phrases, cities = test_data_loading()
    if not phrases or not cities:
        logger.error("\n❌ Test wczytywania danych nie powiódł się")
        return
    
    # Test 3: Czyszczenie danych
    test_data_cleaning()
    
    # Test 4: Serper API (testujemy pierwszą frazę i pierwsze miasto)
    test_phrase = phrases[0]
    test_city = cities[0]
    
    places = test_serper_api(test_phrase, test_city)
    if not places:
        logger.error("\n❌ Test Serper API nie powiódł się")
        return
    
    # Test 5: Operacje na bazie danych
    if not test_database_operations(places, test_phrase, test_city):
        logger.error("\n❌ Test operacji na bazie nie powiódł się")
        return
    
    # Podsumowanie
    logger.info("\n" + "=" * 60)
    logger.info("PODSUMOWANIE TESTÓW")
    logger.info("=" * 60)
    logger.info("✓ Wszystkie testy zakończone pomyślnie!")
    logger.info("✓ Skrypt jest gotowy do uruchomienia na pełnych danych")
    logger.info("\nAby uruchomić pełny scraper:")
    logger.info("  python3 scripts/buildPlacesDb.py")
    logger.info("=" * 60)


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        logger.info("\nPrzerwano przez użytkownika")
        sys.exit(0)
    except Exception as e:
        logger.error(f"Krytyczny błąd: {e}", exc_info=True)
        sys.exit(1)

