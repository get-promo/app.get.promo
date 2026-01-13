@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Sprawdź czy się kwalifikujesz')

@section('page-style')
<style>
  body {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
    background-color: #ffffff;
  }
  
  .landing-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  
  .landing-box {
    max-width: 500px;
    width: 100%;
  }
  
  .landing-title {
    font-size: 32px;
    font-weight: 500;
    color: #202124;
    margin-bottom: 40px;
    text-align: center;
  }
  
  .input-label {
    font-size: 16px;
    font-weight: 400;
    color: #202124;
    margin-bottom: 8px;
    display: block;
  }
  
  .search-wrapper {
    position: relative;
    margin-bottom: 20px;
  }
  
  .search-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid #dadce0;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.2s;
  }
  
  .search-input:focus {
    border-color: #1a73e8;
  }
  
  .suggestions-list {
    position: absolute;
    top: calc(100% - 32px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dadce0;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  
  .suggestions-list.show {
    display: block;
  }
  
  .suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f4;
    transition: background-color 0.2s;
  }
  
  .suggestion-item:hover {
    background-color: #f8f9fa;
  }
  
  .suggestion-item:last-child {
    border-bottom: none;
  }
  
  .suggestion-title {
    font-weight: 500;
    color: #202124;
    margin-bottom: 4px;
  }
  
  .suggestion-address {
    font-size: 14px;
    color: #5f6368;
  }
  
  .btn-google {
    background-color: #1a73e8;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 500;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.2s;
  }
  
  .btn-google:hover:not(:disabled) {
    background-color: #1765cc;
  }
  
  .btn-google:disabled {
    background-color: #dadce0;
    cursor: not-allowed;
  }
  
  .alternative-link {
    text-align: center;
    margin-top: 20px;
  }
  
  .alternative-link a {
    color: #1a73e8;
    text-decoration: none;
    font-size: 14px;
  }
  
  .alternative-link a:hover {
    text-decoration: underline;
  }
  
  .phone-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid #dadce0;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.2s;
    margin-bottom: 20px;
  }
  
  .phone-input:focus {
    border-color: #1a73e8;
  }
  
  .hidden {
    display: none;
  }
  
  .loading {
    text-align: center;
    color: #5f6368;
    font-size: 14px;
    padding: 12px;
  }
</style>
@endsection

@section('content')
<div class="landing-container">
  <div class="landing-box">
    <h1 class="landing-title">Sprawdź czy się kwalifikujesz</h1>
    
    <!-- Formularz z wyszukiwarką miejsc -->
    <div id="placeSearchForm">
      <div class="search-wrapper">
        <label for="placeSearch" class="input-label">
          Jak nazywa się Twoja firma w Google?
        </label>
        <input 
          type="text" 
          id="placeSearch" 
          class="search-input" 
          placeholder="Nazwa Profilu Firmy w Google, tzw. wizytówki"
          autocomplete="off"
        >
        <div id="suggestionsList" class="suggestions-list"></div>
      </div>
      
      <button id="checkBtn" class="btn-google" disabled>Sprawdź</button>
      
      <div class="alternative-link">
        <a href="#" id="alternativeLink">
          Nie masz wizytówki w Google lub nie pamiętasz jej nazwy? Kliknij tutaj
        </a>
      </div>
    </div>
    
    <!-- Formularz z numerem telefonu -->
    <div id="phoneForm" class="hidden">
      <input 
        type="tel" 
        id="phoneInput" 
        class="phone-input" 
        placeholder="Wprowadź numer telefonu"
      >
      
      <button id="submitPhoneBtn" class="btn-google">Dalej</button>
      
      <div class="alternative-link">
        <a href="#" id="backLink">
          Powrót do wyszukiwarki
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  let selectedPlace = null;
  let searchTimeout = null;
  
  const placeSearch = document.getElementById('placeSearch');
  const suggestionsList = document.getElementById('suggestionsList');
  const checkBtn = document.getElementById('checkBtn');
  const alternativeLink = document.getElementById('alternativeLink');
  const backLink = document.getElementById('backLink');
  const placeSearchForm = document.getElementById('placeSearchForm');
  const phoneForm = document.getElementById('phoneForm');
  const phoneInput = document.getElementById('phoneInput');
  const submitPhoneBtn = document.getElementById('submitPhoneBtn');
  
  // Obsługa wyszukiwania miejsc
  placeSearch.addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    // Wyczyść poprzedni timeout
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    
    // Jeśli mniej niż 4 znaki, wyczyść sugestie
    if (query.length < 4) {
      suggestionsList.innerHTML = '';
      suggestionsList.classList.remove('show');
      selectedPlace = null;
      checkBtn.disabled = true;
      return;
    }
    
    // Poczekaj 500ms przed wysłaniem zapytania
    searchTimeout = setTimeout(() => {
      searchPlaces(query);
    }, 500);
  });
  
  // Funkcja wyszukująca miejsca
  function searchPlaces(query) {
    suggestionsList.innerHTML = '<div class="loading">Szukam...</div>';
    suggestionsList.classList.add('show');
    
    fetch('/api/public/search-places', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ query: query })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.data.places && data.data.places.length > 0) {
        // Informacja skąd pochodzą dane (opcjonalnie do debugowania)
        const source = data.source === 'database' ? '💾 Z naszej bazy' : '🌐 Z Google';
        console.log('Źródło danych:', source, '- znaleziono', data.data.places.length, 'miejsc');
        
        displaySuggestions(data.data.places);
      } else {
        suggestionsList.innerHTML = '<div class="loading">Nie znaleziono wyników</div>';
      }
    })
    .catch(error => {
      console.error('Błąd:', error);
      suggestionsList.innerHTML = '<div class="loading">Wystąpił błąd podczas wyszukiwania</div>';
    });
  }
  
  // Wyświetlanie sugestii
  function displaySuggestions(places) {
    suggestionsList.innerHTML = '';
    
    places.slice(0, 5).forEach(place => {
      const item = document.createElement('div');
      item.className = 'suggestion-item';
      item.innerHTML = `
        <div class="suggestion-title">${place.title || ''}</div>
        <div class="suggestion-address">${place.address || ''}</div>
      `;
      
      item.addEventListener('click', () => {
        selectPlace(place);
      });
      
      suggestionsList.appendChild(item);
    });
    
    suggestionsList.classList.add('show');
  }
  
  // Wybór miejsca
  function selectPlace(place) {
    selectedPlace = place;
    placeSearch.value = place.title || '';
    suggestionsList.classList.remove('show');
    suggestionsList.innerHTML = '';
    checkBtn.disabled = false;
  }
  
  // Kliknięcie przycisku "Sprawdź"
  checkBtn.addEventListener('click', () => {
    if (selectedPlace) {
      // Tutaj możesz dodać logikę przekierowania lub wyświetlenia szczegółów
      console.log('Wybrane miejsce:', selectedPlace);
      alert('Wybrano: ' + selectedPlace.title);
    }
  });
  
  // Przełączanie na formularz z telefonem
  alternativeLink.addEventListener('click', (e) => {
    e.preventDefault();
    placeSearchForm.classList.add('hidden');
    phoneForm.classList.remove('hidden');
  });
  
  // Powrót do wyszukiwarki
  backLink.addEventListener('click', (e) => {
    e.preventDefault();
    phoneForm.classList.add('hidden');
    placeSearchForm.classList.remove('hidden');
  });
  
  // Obsługa wysyłania numeru telefonu
  submitPhoneBtn.addEventListener('click', () => {
    const phone = phoneInput.value.trim();
    
    if (phone.length < 9) {
      alert('Wprowadź poprawny numer telefonu');
      return;
    }
    
    fetch('/api/public/submit-phone', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ phone: phone })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Dziękujemy! Skontaktujemy się z Tobą wkrótce.');
        phoneInput.value = '';
      }
    })
    .catch(error => {
      console.error('Błąd:', error);
      alert('Wystąpił błąd podczas wysyłania');
    });
  });
  
  // Ukryj sugestie po kliknięciu poza
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper')) {
      suggestionsList.classList.remove('show');
    }
  });
</script>
@endsection

