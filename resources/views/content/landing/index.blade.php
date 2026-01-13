@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Sprawdź czy się kwalifikujesz')

@section('page-style')
<style>
  @font-face {
    font-family: 'Product Sans';
    src: url('/assets/fonts/ProductSans-Regular.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
  }
  
  @font-face {
    font-family: 'Product Sans';
    src: url('/assets/fonts/ProductSans-Bold.ttf') format('truetype');
    font-weight: 700;
    font-style: normal;
  }
  
  @font-face {
    font-family: 'Product Sans';
    src: url('/assets/fonts/ProductSans-Medium.ttf') format('truetype');
    font-weight: 500;
    font-style: normal;
  }
  
  body {
    font-family: 'Product Sans', 'Google Sans', Roboto, Arial, sans-serif;
    background-color: #ffffff;
  }
  
  .landing-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .hero-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin-bottom: 40px;
    gap: 40px;
  }
  
  .hero-image {
    flex: 0 0 40%;
    background-image: url('/assets/images/landing/1200-lady.webp');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    min-height: 300px;
  }
  
  .hero-text {
    flex: 1;
    text-align: right;
  }
  
  .hero-text .line1,
  .hero-text .line3 {
    font-weight: 700;
    font-size: 48px;
    color: #000000;
    line-height: 1.2;
  }
  
  .hero-text .line2 {
    font-weight: 700;
    font-size: 48px;
    color: #4d84f1;
    line-height: 1.2;
  }
  
  .landing-box {
    max-width: 500px;
    width: 100%;
  }
  
  @media (max-width: 768px) {
    .hero-section {
      flex-direction: column;
      text-align: center;
    }
    
    .hero-image {
      min-height: 200px;
      width: 100%;
    }
    
    .hero-text {
      text-align: center;
    }
    
    .hero-text .line1,
    .hero-text .line2,
    .hero-text .line3 {
      font-size: 32px;
    }
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
  
  .phone-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .phone-prefix {
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid #dadce0;
    border-radius: 4px;
    background-color: #f8f9fa;
    color: #202124;
    font-weight: 500;
  }
  
  .phone-input {
    flex: 1;
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid #dadce0;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.2s;
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
  
  .thank-you-message {
    text-align: center;
    padding: 40px 20px;
  }
  
  .thank-you-message h2 {
    font-size: 24px;
    font-weight: 500;
    color: #1a73e8;
    margin-bottom: 20px;
  }
  
  .thank-you-message p {
    font-size: 16px;
    color: #5f6368;
    line-height: 1.6;
  }
</style>
@endsection

@section('content')
<div class="landing-container">
  <!-- Hero Section -->
  <div class="hero-section">
    <div class="hero-image"></div>
    <div class="hero-text">
      <div class="line1">Odbierz</div>
      <div class="line2">1200 zł</div>
      <div class="line3">na pozycjonowanie</div>
    </div>
  </div>
  
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
      <label for="phoneInput" class="input-label">
        Podaj numer telefonu
      </label>
      
      <div class="phone-wrapper">
        <div class="phone-prefix">+48</div>
        <input 
          type="tel" 
          id="phoneInput" 
          class="phone-input" 
          placeholder="789 123 456"
          maxlength="11"
        >
      </div>
      
      <button id="submitPhoneBtn" class="btn-google">Sprawdź</button>
    </div>
    
    <!-- Thank You Page -->
    <div id="thankYouPage" class="hidden">
      <div class="thank-you-message">
        <h2>Dziękujemy!</h2>
        <p>Nasz certyfikowany ekspert Google Ads skontaktuje się z Tobą w ciągu 10 minut</p>
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
  const placeSearchForm = document.getElementById('placeSearchForm');
  const phoneForm = document.getElementById('phoneForm');
  const phoneInput = document.getElementById('phoneInput');
  const submitPhoneBtn = document.getElementById('submitPhoneBtn');
  const thankYouPage = document.getElementById('thankYouPage');
  
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
    
    // Loguj wybór miejsca (selected)
    fetch('/api/public/log-selected', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ place: place })
    }).catch(error => console.error('Error logging selected:', error));
  }
  
  // Kliknięcie przycisku "Sprawdź"
  checkBtn.addEventListener('click', () => {
    if (selectedPlace) {
      // Loguj kliknięcie "Sprawdź" (checked)
      fetch('/api/public/log-checked', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ place: selectedPlace })
      }).catch(error => console.error('Error logging checked:', error));
      
      // Pokaż formularz telefonu
      placeSearchForm.classList.add('hidden');
      phoneForm.classList.remove('hidden');
    }
  });
  
  // Przełączanie na formularz z telefonem (alternatywny link)
  alternativeLink.addEventListener('click', (e) => {
    e.preventDefault();
    selectedPlace = null; // Czyścimy wybrane miejsce
    placeSearchForm.classList.add('hidden');
    phoneForm.classList.remove('hidden');
  });
  
  // Obsługa wysyłania numeru telefonu
  submitPhoneBtn.addEventListener('click', () => {
    const phone = phoneInput.value.trim().replace(/\s/g, '');
    
    if (phone.length !== 9 || !/^\d+$/.test(phone)) {
      alert('Wprowadź poprawny 9-cyfrowy numer telefonu');
      return;
    }
    
    const fullPhone = '+48' + phone;
    
    // Wyślij numer telefonu
    fetch('/api/public/submit-phone', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ 
        phone: fullPhone,
        place: selectedPlace // Dołącz dane miejsca jeśli wybrano
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Pokaż thank you page
        phoneForm.classList.add('hidden');
        thankYouPage.classList.remove('hidden');
      } else {
        alert('Wystąpił błąd podczas wysyłania');
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
  
  // Formatowanie numeru telefonu (automatyczne spacje)
  phoneInput.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, ''); // Usuń wszystko oprócz cyfr
    
    if (value.length > 9) {
      value = value.slice(0, 9);
    }
    
    // Formatuj: XXX XXX XXX
    if (value.length > 6) {
      value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
    } else if (value.length > 3) {
      value = value.slice(0, 3) + ' ' + value.slice(3);
    }
    
    e.target.value = value;
  });
</script>
@endsection

