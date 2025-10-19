#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Skrypt do wyszukiwania emaili ze stron internetowych dla rekordów w tabeli places.
- Wielowątkowość (8 wątków)
- System checkpointów
- Logika z parseEmails.php: strona główna → strony kontaktowe
"""

import os
import sys
import json
import time
import re
import logging
import requests
import pymysql
from concurrent.futures import ThreadPoolExecutor, as_completed
from threading import Lock
from datetime import datetime
from dotenv import load_dotenv
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse

# Konfiguracja logowania
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler('scripts/email_scraper.log', encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# Globalne zmienne dla statystyk
stats_lock = Lock()
stats = {
    'processed': 0,
    'found_emails': 0,
    'not_found': 0,
    'errors': 0
}

# Lock dla checkpointów
checkpoint_lock = Lock()

# Ścieżki do plików
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)
ENV_PATH = os.path.join(PROJECT_ROOT, '.env')
PROGRESS_PATH = os.path.join(SCRIPT_DIR, 'email_progress.json')

# Wczytanie konfiguracji
load_dotenv(ENV_PATH)

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE', 'laravel')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')

# Parametry
REQUEST_TIMEOUT = 10
MAX_WORKERS = 16


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


def find_emails(html):
    """
    Wyszukuje adresy email w HTML.
    Filtruje rozszerzenia plików graficznych.
    """
    if not html:
        return []
    
    # Regex dla emaili
    pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
    matches = re.findall(pattern, html)
    
    emails = []
    file_extensions = ['.png', '.jpg', '.jpeg', '.svg', '.gif', '.webp', '.bmp']
    
    for email in matches:
        # Dekoduj URL-encoded
        from urllib.parse import unquote
        email = unquote(email)
        
        # Sprawdź czy nie kończy się rozszerzeniem graficznym
        is_file = any(email.lower().endswith(ext) for ext in file_extensions)
        
        if not is_file and '@' in email:
            # Walidacja podstawowa
            if len(email) > 5 and '.' in email.split('@')[1]:
                emails.append(email.lower())
    
    return list(set(emails))  # Unikalne


def find_contact_links(html, base_url):
    """
    Znajduje linki do stron kontaktowych.
    """
    if not html:
        return []
    
    try:
        soup = BeautifulSoup(html, 'html.parser')
        links = soup.find_all('a', href=True)
        contact_links = []
        
        for link in links:
            href = link.get('href', '')
            text = link.get_text().lower()
            
            # Sprawdź czy link/tekst zawiera "kontakt" lub "contact"
            if ('kontakt' in href.lower() or 'contact' in href.lower() or
                'kontakt' in text or 'contact' in text):
                
                # Konwertuj na absolutny URL
                absolute_url = urljoin(base_url, href)
                contact_links.append(absolute_url)
        
        return list(set(contact_links))[:3]  # Max 3 strony kontaktowe
    
    except Exception as e:
        logger.debug(f"Błąd parsowania linków: {e}")
        return []


def get_website_content(url):
    """Pobiera zawartość strony z timeoutem."""
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }
    
    try:
        response = requests.get(url, headers=headers, timeout=REQUEST_TIMEOUT, verify=False)
        if response.status_code == 200:
            return response.text
        return ''
    except Exception as e:
        logger.debug(f"Nie udało się pobrać {url}: {e}")
        return ''


def process_place(place_record, progress):
    """
    Przetwarza jedno miejsce - szuka emaila na stronie.
    """
    place_id = place_record['id']
    website = place_record['website']
    
    # Sprawdź czy już przetworzony
    if progress.get(str(place_id), {}).get('completed'):
        return
    
    logger.info(f"[{place_id}] Przetwarzam: {website}")
    
    connection = None
    
    try:
        connection = get_db_connection()
        cursor = connection.cursor()
        
        # Sprawdź czy to nie social media
        if 'facebook.com' in website.lower() or 'instagram.com' in website.lower():
            logger.info(f"[{place_id}] Pomijam social media: {website}")
            cursor.execute("UPDATE places SET email_checked = 1 WHERE id = %s", (place_id,))
            connection.commit()
            
            with stats_lock:
                stats['processed'] += 1
                stats['not_found'] += 1
            
            progress[str(place_id)] = {'completed': True, 'result': 'social_media'}
            save_progress(progress)
            return
        
        # Pobierz stronę główną
        html = get_website_content(website)
        
        if not html:
            logger.warning(f"[{place_id}] Nie udało się pobrać strony: {website}")
            cursor.execute("UPDATE places SET email_checked = 1 WHERE id = %s", (place_id,))
            connection.commit()
            
            with stats_lock:
                stats['processed'] += 1
                stats['errors'] += 1
            
            progress[str(place_id)] = {'completed': True, 'result': 'fetch_error'}
            save_progress(progress)
            return
        
        # Szukaj emaili na stronie głównej
        emails = find_emails(html)
        
        # Jeśli nie ma emaili, szukaj na stronach kontaktowych
        if not emails:
            logger.info(f"[{place_id}] Brak emaili na stronie głównej, szukam na stronach kontaktowych")
            
            contact_links = find_contact_links(html, website)
            
            for contact_link in contact_links:
                logger.info(f"[{place_id}] Sprawdzam: {contact_link}")
                
                contact_html = get_website_content(contact_link)
                
                if contact_html:
                    contact_emails = find_emails(contact_html)
                    if contact_emails:
                        emails = contact_emails
                        break
                
                time.sleep(0.5)  # Przerwa między stronami kontaktowymi
        
        # Zapisz wynik
        if emails:
            email = emails[0]  # Pierwszy znaleziony
            logger.info(f"[{place_id}] ✓ Znaleziono email: {email}")
            
            cursor.execute("""
                UPDATE places 
                SET email = %s, 
                    email_checked = 1, 
                    email_checked_at = NOW(),
                    email_source = 'website_scan'
                WHERE id = %s
            """, (email, place_id))
            
            with stats_lock:
                stats['found_emails'] += 1
        else:
            logger.info(f"[{place_id}] Nie znaleziono emaila")
            cursor.execute("UPDATE places SET email_checked = 1 WHERE id = %s", (place_id,))
            
            with stats_lock:
                stats['not_found'] += 1
        
        connection.commit()
        
        with stats_lock:
            stats['processed'] += 1
        
        progress[str(place_id)] = {
            'completed': True,
            'result': 'email_found' if emails else 'not_found',
            'email': emails[0] if emails else None
        }
        save_progress(progress)
        
    except Exception as e:
        logger.error(f"[{place_id}] Błąd: {e}")
        
        if connection:
            try:
                cursor.execute("UPDATE places SET email_checked = 1 WHERE id = %s", (place_id,))
                connection.commit()
            except:
                pass
        
        with stats_lock:
            stats['processed'] += 1
            stats['errors'] += 1
        
        progress[str(place_id)] = {'completed': True, 'result': 'error'}
        save_progress(progress)
        
    finally:
        if connection:
            connection.close()


def load_progress():
    """Wczytuje postęp z pliku JSON."""
    if os.path.exists(PROGRESS_PATH):
        try:
            with open(PROGRESS_PATH, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            logger.warning(f"Błąd wczytywania postępu: {e}")
    return {}


def save_progress(progress):
    """Zapisuje postęp do pliku JSON."""
    with checkpoint_lock:
        try:
            with open(PROGRESS_PATH, 'w', encoding='utf-8') as f:
                json.dump(progress, f, ensure_ascii=False, indent=2)
        except Exception as e:
            logger.error(f"Błąd zapisywania postępu: {e}")


def main():
    """Główna funkcja skryptu."""
    logger.info("=" * 80)
    logger.info("Email Scraper - Wyszukiwanie emaili ze stron internetowych")
    logger.info("=" * 80)
    
    start_time = time.time()
    
    # Wczytaj postęp
    progress = load_progress()
    
    # Połącz z bazą i pobierz rekordy do przetworzenia
    connection = get_db_connection()
    cursor = connection.cursor()
    
    cursor.execute("""
        SELECT id, website, title
        FROM places
        WHERE (email IS NULL OR email = '')
          AND website IS NOT NULL 
          AND website != ''
          AND (email_checked = 0 OR email_checked IS NULL)
        ORDER BY id ASC
    """)
    
    places = cursor.fetchall()
    connection.close()
    
    logger.info(f"Znaleziono {len(places)} miejsc do przetworzenia")
    
    # Filtruj już przetworzone
    tasks = [p for p in places if not progress.get(str(p['id']), {}).get('completed')]
    
    logger.info(f"Do przetworzenia (po pominiętych): {len(tasks)} miejsc")
    logger.info(f"Liczba wątków: {MAX_WORKERS}")
    logger.info("=" * 80)
    
    # Przetwarzanie wielowątkowe
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {
            executor.submit(process_place, place, progress): place
            for place in tasks
        }
        
        for future in as_completed(futures):
            place = futures[future]
            try:
                future.result()
            except Exception as e:
                logger.error(f"Nieobsłużony błąd dla {place['id']}: {e}")
    
    # Podsumowanie
    elapsed_time = time.time() - start_time
    logger.info("=" * 80)
    logger.info("PODSUMOWANIE")
    logger.info("=" * 80)
    logger.info(f"Czas wykonania: {elapsed_time:.2f} sekund ({elapsed_time/60:.2f} minut)")
    logger.info(f"Przetworzono miejsc: {stats['processed']}")
    logger.info(f"Znaleziono emaili: {stats['found_emails']}")
    logger.info(f"Nie znaleziono: {stats['not_found']}")
    logger.info(f"Błędy: {stats['errors']}")
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

