/**
 * Leads Create Form - Wizard with Serper Integration
 */

'use strict';

(function () {
  const leadStepper = document.querySelector('.wizard-numbered');
  if (!leadStepper) return;

  // Initialize Stepper
  const stepper = new Stepper(leadStepper, {
    linear: false
  });

  // Elements
  const searchBtn = document.getElementById('searchBtn');
  const searchQuery = document.getElementById('searchQuery');
  const searchResults = document.getElementById('searchResults');
  const searchLoading = document.getElementById('searchLoading');
  const placesList = document.getElementById('placesList');
  const resultsCount = document.getElementById('resultsCount');
  const nextStepBtn = document.getElementById('nextStepBtn');
  const skipSearchBtn = document.getElementById('skipSearchBtn');
  const btnPrev = document.querySelector('.btn-prev');

  // Form fields - Step 2
  const leadTitle = document.getElementById('leadTitle');
  const placePreview = document.getElementById('placePreview');
  const serperResponseField = document.getElementById('serperResponseField');

  // Hidden fields for Serper data
  const addressField = document.getElementById('addressField');
  const latitudeField = document.getElementById('latitudeField');
  const longitudeField = document.getElementById('longitudeField');
  const ratingField = document.getElementById('ratingField');
  const ratingCountField = document.getElementById('ratingCountField');
  const priceLevelField = document.getElementById('priceLevelField');
  const categoryField = document.getElementById('categoryField');
  const phoneNumberField = document.getElementById('phoneNumberField');
  const websiteField = document.getElementById('websiteField');
  const cidField = document.getElementById('cidField');

  // Preview elements
  const previewAddressText = document.getElementById('previewAddressText');
  const previewPhoneText = document.getElementById('previewPhoneText');
  const previewRatingText = document.getElementById('previewRatingText');
  const previewWebsiteLink = document.getElementById('previewWebsiteLink');
  const previewCategoryText = document.getElementById('previewCategoryText');

  let selectedPlace = null;

  /**
   * Search Places via Serper API
   */
  async function searchPlaces(query) {
    searchLoading.classList.remove('d-none');
    searchResults.classList.add('d-none');
    placesList.innerHTML = '';

    try {
      const response = await fetch('/api/leads/search-places', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ query })
      });

      const data = await response.json();

      searchLoading.classList.add('d-none');

      if (data.success && data.data.places && data.data.places.length > 0) {
        displayResults(data.data.places);
      } else {
        showNoResults();
      }
    } catch (error) {
      searchLoading.classList.add('d-none');
      showError('Wystąpił błąd podczas wyszukiwania. Spróbuj ponownie.');
      console.error('Search error:', error);
    }
  }

  /**
   * Display search results
   */
  function displayResults(places) {
    searchResults.classList.remove('d-none');
    resultsCount.textContent = `Znaleziono ${places.length} wyników`;

    placesList.innerHTML = places
      .map(
        (place, index) => `
      <a href="#" class="list-group-item list-group-item-action place-item" data-index="${index}">
        <div class="d-flex w-100 justify-content-between align-items-start">
          <div>
            <h6 class="mb-1">${place.title || 'Brak nazwy'}</h6>
            <p class="mb-1 text-muted small">
              <i class="ti ti-map-pin me-1"></i>
              ${place.address || 'Brak adresu'}
            </p>
            ${
              place.rating
                ? `
            <span class="badge bg-label-primary">
              <i class="ti ti-star-filled"></i>
              ${place.rating} (${place.ratingCount || 0})
            </span>
            `
                : ''
            }
            ${place.category ? `<span class="badge bg-label-secondary ms-1">${place.category}</span>` : ''}
          </div>
          <div class="text-end">
            ${place.phoneNumber ? `<small class="text-muted d-block"><i class="ti ti-phone me-1"></i>${place.phoneNumber}</small>` : ''}
            ${place.website ? `<small class="text-primary d-block mt-1"><i class="ti ti-world me-1"></i>Strona www</small>` : ''}
          </div>
        </div>
      </a>
    `
      )
      .join('');

    // Store places data for later use
    window.placesData = places;

    // Add click handlers to place items
    document.querySelectorAll('.place-item').forEach(item => {
      item.addEventListener('click', function (e) {
        e.preventDefault();
        const index = this.dataset.index;
        selectPlace(places[index]);
      });
    });
  }

  /**
   * Show no results message
   */
  function showNoResults() {
    searchResults.classList.remove('d-none');
    resultsCount.textContent = 'Nie znaleziono wyników';
    placesList.innerHTML = `
      <div class="alert alert-warning mb-0">
        <i class="ti ti-alert-triangle me-2"></i>
        Nie znaleziono żadnych wyników. Spróbuj zmienić frazę wyszukiwania lub dodaj leada ręcznie.
      </div>
    `;
  }

  /**
   * Show error message
   */
  function showError(message) {
    searchResults.classList.remove('d-none');
    resultsCount.textContent = 'Błąd';
    placesList.innerHTML = `
      <div class="alert alert-danger mb-0">
        <i class="ti ti-alert-circle me-2"></i>
        ${message}
      </div>
    `;
  }

  /**
   * Select a place from results
   */
  function selectPlace(place) {
    selectedPlace = place;

    // Highlight selected item
    document.querySelectorAll('.place-item').forEach(item => {
      item.classList.remove('active');
    });
    event.target.closest('.place-item').classList.add('active');

    // Enable next button
    nextStepBtn.disabled = false;

    // Fill form fields
    fillFormWithPlaceData(place);
  }

  /**
   * Fill form with place data
   */
  function fillFormWithPlaceData(place) {
    // Main title
    leadTitle.value = place.title || '';

    // Hidden fields
    serperResponseField.value = JSON.stringify(place);
    addressField.value = place.address || '';
    latitudeField.value = place.latitude || '';
    longitudeField.value = place.longitude || '';
    ratingField.value = place.rating || '';
    ratingCountField.value = place.ratingCount || '';
    priceLevelField.value = place.priceLevel || '';
    categoryField.value = place.category || '';
    phoneNumberField.value = place.phoneNumber || '';
    websiteField.value = place.website || '';
    cidField.value = place.cid || '';

    // Preview
    placePreview.classList.remove('d-none');
    previewAddressText.textContent = place.address || '-';
    previewPhoneText.textContent = place.phoneNumber || '-';
    previewRatingText.textContent = place.rating
      ? `${place.rating} ⭐ (${place.ratingCount || 0} opinii)`
      : '-';
    previewCategoryText.textContent = place.category || '-';

    if (place.website) {
      previewWebsiteLink.href = place.website;
      previewWebsiteLink.textContent = place.website;
      previewWebsiteLink.parentElement.classList.remove('d-none');
    } else {
      previewWebsiteLink.parentElement.classList.add('d-none');
    }
  }

  /**
   * Clear form (when skipping search)
   */
  function clearForm() {
    leadTitle.value = '';
    placePreview.classList.add('d-none');
    serperResponseField.value = '';
    addressField.value = '';
    latitudeField.value = '';
    longitudeField.value = '';
    ratingField.value = '';
    ratingCountField.value = '';
    priceLevelField.value = '';
    categoryField.value = '';
    phoneNumberField.value = '';
    websiteField.value = '';
    cidField.value = '';
  }

  // Event Listeners

  // Search button click
  searchBtn.addEventListener('click', function () {
    const query = searchQuery.value.trim();
    if (query.length >= 2) {
      searchPlaces(query);
    } else {
      alert('Wpisz co najmniej 2 znaki');
    }
  });

  // Search on Enter
  searchQuery.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchBtn.click();
    }
  });

  // Skip search button
  skipSearchBtn.addEventListener('click', function () {
    clearForm();
    nextStepBtn.disabled = false;
    stepper.next();
  });

  // Next button
  nextStepBtn.addEventListener('click', function () {
    stepper.next();
  });

  // Previous button
  if (btnPrev) {
    btnPrev.addEventListener('click', function () {
      stepper.previous();
    });
  }
})();

