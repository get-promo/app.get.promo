#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Skrypt do pobierania danych z Serper API dla wszystkich kombinacji fraz i miast.
Wykorzystuje wielowątkowość (16 wątków) i system checkpointów.
"""

import os
import sys
import json
import time
import csv
import re
import logging
import requests
import pymysql
from concurrent.futures import ThreadPoolExecutor, as_completed
from threading import Lock
from datetime import datetime
from dotenv import load_dotenv

# Konfiguracja logowania
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler('scripts/scraper.log', encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# Globalne zmienne dla statystyk
stats_lock = Lock()
stats = {
    'added': 0,
    'updated': 0,
    'errors': 0,
    'processed_combinations': 0,
    'total_places': 0
}

# Lock dla checkpointów
checkpoint_lock = Lock()

# Ścieżki do plików
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)
ENV_PATH = os.path.join(PROJECT_ROOT, '.env')
PHRASES_PATH = os.path.join(SCRIPT_DIR, 'phrases.txt')
CITIES_PATH = os.path.join(SCRIPT_DIR, 'cities.csv')
PROGRESS_PATH = os.path.join(SCRIPT_DIR, 'progress.json')

# Wczytanie konfiguracji z .env
load_dotenv(ENV_PATH)

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE', 'laravel')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')
SERPER_API_KEY = os.getenv('SERPER_API_KEY')

if not SERPER_API_KEY:
    logger.error("SERPER_API_KEY nie znaleziony w pliku .env")
    sys.exit(1)


def clean_string(text):
    """
    Funkcja czyszcząca tekst z emoji i znaków specjalnych.
    Odpowiednik funkcji cleanString() z PHP.
    """
    if not text or not isinstance(text, str):
        return ''
    
    # Usuwa emoji i znaki specjalne
    emoji_pattern = re.compile("["
        u"\U0001F600-\U0001F64F"  # emotikony
        u"\U0001F300-\U0001F5FF"  # symbole i piktogramy
        u"\U0001F680-\U0001F6FF"  # symbole transportu i mapy
        u"\U0001F700-\U0001F77F"  # symbole alchemiczne
        u"\U0001F780-\U0001F7FF"  # symbole geometryczne
        u"\U0001F800-\U0001F8FF"  # symbole uzupełniające
        u"\U0001F900-\U0001F9FF"  # symbole uzupełniające
        u"\U0001FA00-\U0001FA6F"  # symbole szachowe
        u"\U0001FA70-\U0001FAFF"  # symbole emoji
        u"\U00002600-\U000026FF"  # symbole różne
        u"\U00002700-\U000027BF"  # symbole dekoracyjne
        "]+", flags=re.UNICODE)
    
    text = emoji_pattern.sub('', text)
    
    # Usuwa znaki niebędące literami, cyframi, spacjami, myślnikami lub kropkami
    text = re.sub(r'[^\w\s\-\.]', '', text, flags=re.UNICODE)
    
    # Usuwa nadmiarowe spacje
    text = re.sub(r'\s+', ' ', text).strip()
    
    return text


def get_db_connection():
    """Tworzy połączenie z bazą danych."""
    try:
        connection = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USERNAME,
            password=DB_PASSWORD,
            database=DB_DATABASE,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=False
        )
        return connection
    except Exception as e:
        logger.error(f"Błąd połączenia z bazą danych: {e}")
        raise


def load_phrases():
    """Wczytuje frazy z pliku phrases.txt."""
    try:
        with open(PHRASES_PATH, 'r', encoding='utf-8') as f:
            phrases = [line.strip() for line in f if line.strip()]
        logger.info(f"Wczytano {len(phrases)} fraz")
        return phrases
    except Exception as e:
        logger.error(f"Błąd wczytywania fraz: {e}")
        raise


def load_cities():
    """Wczytuje miasta z pliku cities.csv (kolumna 0=nazwa, kolumna 1=rozmiar)."""
    try:
        cities = []
        with open(CITIES_PATH, 'r', encoding='utf-8') as f:
            reader = csv.reader(f)
            for row in reader:
                if len(row) >= 2:
                    city_name = row[0].strip()
                    city_size = row[1].strip()
                    cities.append({'name': city_name, 'size': city_size})
        logger.info(f"Wczytano {len(cities)} miast")
        return cities
    except Exception as e:
        logger.error(f"Błąd wczytywania miast: {e}")
        raise


def load_progress():
    """Wczytuje postęp z pliku progress.json."""
    if os.path.exists(PROGRESS_PATH):
        try:
            with open(PROGRESS_PATH, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            logger.warning(f"Błąd wczytywania postępu: {e}")
    return {}


def save_progress(progress):
    """Zapisuje postęp do pliku progress.json."""
    with checkpoint_lock:
        try:
            with open(PROGRESS_PATH, 'w', encoding='utf-8') as f:
                json.dump(progress, f, ensure_ascii=False, indent=2)
        except Exception as e:
            logger.error(f"Błąd zapisywania postępu: {e}")


def fetch_serper_places(query, page=1, retries=3):
    """
    Pobiera dane z Serper API dla danego zapytania i strony.
    Implementuje retry logic.
    """
    url = 'https://google.serper.dev/places'
    headers = {
        'X-API-KEY': SERPER_API_KEY,
        'Content-Type': 'application/json'
    }
    payload = {
        'q': query,
        'gl': 'pl',
        'page': page
    }
    
    for attempt in range(retries):
        try:
            response = requests.post(url, headers=headers, json=payload, timeout=30)
            
            if response.status_code == 200:
                return response.json()
            else:
                logger.warning(f"Serper API zwrócił kod {response.status_code} dla '{query}' strona {page}")
                if attempt < retries - 1:
                    time.sleep(2 ** attempt)  # Exponential backoff
                    continue
                return None
                
        except Exception as e:
            logger.error(f"Błąd podczas zapytania Serper API (próba {attempt + 1}/{retries}): {e}")
            if attempt < retries - 1:
                time.sleep(2 ** attempt)
            else:
                return None
    
    return None


def place_exists(cursor, cid):
    """Sprawdza czy miejsce o danym CID już istnieje w bazie."""
    cursor.execute("SELECT id FROM places WHERE cid = %s", (cid,))
    return cursor.fetchone() is not None


def insert_place(connection, place_data):
    """Wstawia nowe miejsce do bazy danych."""
    try:
        cursor = connection.cursor()
        
        sql = """
            INSERT INTO places (
                cid, title, address, latitude, longitude, rating, rating_count,
                price_level, category, phone_number, website, serper_response,
                search_phrase, city_name, city_size, created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
            )
        """
        
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        cursor.execute(sql, (
            place_data['cid'],
            place_data['title'],
            place_data['address'],
            place_data['latitude'],
            place_data['longitude'],
            place_data['rating'],
            place_data['rating_count'],
            place_data['price_level'],
            place_data['category'],
            place_data['phone_number'],
            place_data['website'],
            place_data['serper_response'],
            place_data['search_phrase'],
            place_data['city_name'],
            place_data['city_size'],
            now,
            now
        ))
        
        connection.commit()
        return True
        
    except pymysql.IntegrityError as e:
        # Duplikat CID - aktualizujemy
        connection.rollback()
        return update_place(connection, place_data)
    except Exception as e:
        logger.error(f"Błąd podczas wstawiania miejsca: {e}")
        connection.rollback()
        return False


def update_place(connection, place_data):
    """Aktualizuje istniejące miejsce w bazie danych."""
    try:
        cursor = connection.cursor()
        
        sql = """
            UPDATE places SET
                title = %s,
                address = %s,
                latitude = %s,
                longitude = %s,
                rating = %s,
                rating_count = %s,
                price_level = %s,
                category = %s,
                phone_number = %s,
                website = %s,
                serper_response = %s,
                search_phrase = %s,
                city_name = %s,
                city_size = %s,
                updated_at = %s
            WHERE cid = %s
        """
        
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        cursor.execute(sql, (
            place_data['title'],
            place_data['address'],
            place_data['latitude'],
            place_data['longitude'],
            place_data['rating'],
            place_data['rating_count'],
            place_data['price_level'],
            place_data['category'],
            place_data['phone_number'],
            place_data['website'],
            place_data['serper_response'],
            place_data['search_phrase'],
            place_data['city_name'],
            place_data['city_size'],
            now,
            place_data['cid']
        ))
        
        connection.commit()
        return 'updated'
        
    except Exception as e:
        logger.error(f"Błąd podczas aktualizacji miejsca: {e}")
        connection.rollback()
        return False


def process_place(place, search_phrase, city_name, city_size):
    """Przetwarza pojedyncze miejsce z odpowiedzi Serper."""
    # Filtrowanie miejsc zamkniętych
    if place.get('closedTemporarily') or place.get('closedPermanently'):
        return None
    
    # Sprawdzenie czy mamy CID
    cid = place.get('cid')
    if not cid:
        return None
    
    # Przygotowanie danych
    place_data = {
        'cid': clean_string(str(cid)),
        'title': clean_string(place.get('title', '')),
        'address': clean_string(place.get('address', '')),
        'latitude': place.get('latitude', 0),
        'longitude': place.get('longitude', 0),
        'rating': place.get('rating', 0),
        'rating_count': place.get('ratingCount', 0),
        'price_level': clean_string(place.get('priceLevel', '')),
        'category': clean_string(place.get('category', '')),
        'phone_number': clean_string(place.get('phoneNumber', '')),
        'website': clean_string(place.get('website', '')),
        'serper_response': json.dumps(place, ensure_ascii=False),
        'search_phrase': search_phrase,
        'city_name': city_name,
        'city_size': city_size
    }
    
    return place_data


def process_combination(phrase, city, progress):
    """
    Przetwarza jedną kombinację fraza+miasto.
    Pobiera dane z Serper API (strony 1-3) i zapisuje do bazy.
    """
    combination_key = f"{phrase}|{city['name']}"
    
    # Sprawdzenie czy kombinacja nie została już przetworzona
    if progress.get(combination_key, {}).get('completed'):
        logger.info(f"Pomijam już przetworzoną kombinację: {combination_key}")
        return
    
    logger.info(f"Przetwarzanie: {phrase} - {city['name']}")
    
    connection = None
    local_stats = {'added': 0, 'updated': 0, 'errors': 0}
    
    try:
        connection = get_db_connection()
        cursor = connection.cursor()
        
        search_query = f"{phrase} {city['name']}"
        
        # Pobieranie stron 1-3
        for page in range(1, 4):
            response = fetch_serper_places(search_query, page)
            
            if not response or 'places' not in response:
                logger.warning(f"Brak wyników dla '{search_query}' strona {page}")
                continue
            
            places = response.get('places', [])
            logger.info(f"Znaleziono {len(places)} miejsc dla '{search_query}' strona {page}")
            
            for place in places:
                place_data = process_place(place, phrase, city['name'], city['size'])
                
                if not place_data:
                    continue
                
                # Sprawdzenie czy miejsce już istnieje
                if place_exists(cursor, place_data['cid']):
                    result = update_place(connection, place_data)
                    if result == 'updated':
                        local_stats['updated'] += 1
                        logger.debug(f"Zaktualizowano: {place_data['title']}")
                    else:
                        local_stats['errors'] += 1
                else:
                    if insert_place(connection, place_data):
                        local_stats['added'] += 1
                        logger.debug(f"Dodano: {place_data['title']}")
                    else:
                        local_stats['errors'] += 1
            
            # Jeśli mniej niż 10 wyników, nie ma sensu próbować kolejnej strony
            if len(places) < 10:
                break
            
            # Krótka przerwa między stronami
            time.sleep(0.5)
        
        # Aktualizacja globalnych statystyk
        with stats_lock:
            stats['added'] += local_stats['added']
            stats['updated'] += local_stats['updated']
            stats['errors'] += local_stats['errors']
            stats['processed_combinations'] += 1
            stats['total_places'] += local_stats['added'] + local_stats['updated']
        
        # Zapis checkpointu
        progress[combination_key] = {
            'completed': True,
            'timestamp': datetime.now().isoformat(),
            'added': local_stats['added'],
            'updated': local_stats['updated'],
            'errors': local_stats['errors']
        }
        save_progress(progress)
        
        logger.info(f"Zakończono: {combination_key} | Dodano: {local_stats['added']}, Zaktualizowano: {local_stats['updated']}, Błędy: {local_stats['errors']}")
        
    except Exception as e:
        logger.error(f"Błąd podczas przetwarzania kombinacji {combination_key}: {e}")
        with stats_lock:
            stats['errors'] += 1
    finally:
        if connection:
            connection.close()


def main():
    """Główna funkcja skryptu."""
    logger.info("=" * 80)
    logger.info("Rozpoczynam pobieranie danych z Serper API")
    logger.info("=" * 80)
    
    start_time = time.time()
    
    # Wczytanie danych
    phrases = load_phrases()
    cities = load_cities()
    progress = load_progress()
    
    total_combinations = len(phrases) * len(cities)
    completed_combinations = sum(1 for v in progress.values() if v.get('completed'))
    
    logger.info(f"Liczba fraz: {len(phrases)}")
    logger.info(f"Liczba miast: {len(cities)}")
    logger.info(f"Całkowita liczba kombinacji: {total_combinations}")
    logger.info(f"Ukończone kombinacje: {completed_combinations}")
    logger.info(f"Pozostałe kombinacje: {total_combinations - completed_combinations}")
    logger.info(f"Liczba wątków: 16")
    logger.info("=" * 80)
    
    # Przygotowanie listy zadań
    tasks = []
    for phrase in phrases:
        for city in cities:
            combination_key = f"{phrase}|{city['name']}"
            if not progress.get(combination_key, {}).get('completed'):
                tasks.append((phrase, city))
    
    logger.info(f"Do przetworzenia: {len(tasks)} kombinacji")
    
    # Przetwarzanie z wykorzystaniem ThreadPoolExecutor
    with ThreadPoolExecutor(max_workers=16) as executor:
        futures = {
            executor.submit(process_combination, phrase, city, progress): (phrase, city)
            for phrase, city in tasks
        }
        
        for future in as_completed(futures):
            phrase, city = futures[future]
            try:
                future.result()
            except Exception as e:
                logger.error(f"Nieobsłużony błąd dla {phrase} - {city['name']}: {e}")
    
    # Podsumowanie
    elapsed_time = time.time() - start_time
    logger.info("=" * 80)
    logger.info("PODSUMOWANIE")
    logger.info("=" * 80)
    logger.info(f"Czas wykonania: {elapsed_time:.2f} sekund ({elapsed_time/60:.2f} minut)")
    logger.info(f"Przetworzone kombinacje: {stats['processed_combinations']}")
    logger.info(f"Całkowita liczba miejsc: {stats['total_places']}")
    logger.info(f"Dodano nowych miejsc: {stats['added']}")
    logger.info(f"Zaktualizowano miejsc: {stats['updated']}")
    logger.info(f"Liczba błędów: {stats['errors']}")
    logger.info("=" * 80)
    logger.info("Zakończono!")


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        logger.info("\nPrzerwano przez użytkownika. Postęp został zapisany.")
        sys.exit(0)
    except Exception as e:
        logger.error(f"Krytyczny błąd: {e}", exc_info=True)
        sys.exit(1)

