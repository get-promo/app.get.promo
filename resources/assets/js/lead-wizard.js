/**
 * Lead Wizard
 */

'use strict';

$(function() {
  const leadWizard = document.querySelector('#lead-wizard');
  let selectedPlace = null;
  let isManualEntry = false;

  if (leadWizard) {
    const stepper = new Stepper(leadWizard, {
      linear: false
    });

    // Przycisk "Dalej" z kroku 1 do kroku 2
    const nextBtn = document.querySelector('#next-to-contact');
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        stepper.next();
      });
    }

    // Przycisk "Wstecz" z kroku 2 do kroku 1
    const prevBtn = document.querySelector('.btn-prev');
    if (prevBtn) {
      prevBtn.addEventListener('click', function() {
        stepper.previous();
      });
    }

    // Wyszukiwanie przez Serper
    $('#search-btn').on('click', function() {
      const query = $('#search-query').val().trim();
      
      if (query.length < 2) {
        alert('Wprowadź co najmniej 2 znaki');
        return;
      }

      $('#search-loading').show();
      $('#search-results-container').hide();
      $('#search-results').empty();

      // Pobierz CSRF token z meta taga
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const searchUrl = $('#search-btn').data('search-url');

      $.ajax({
        url: searchUrl,
        method: 'POST',
        data: {
          _token: csrfToken,
          query: query
        },
        success: function(response) {
          $('#search-loading').hide();
          
          if (response.success && response.data.places && response.data.places.length > 0) {
            displaySearchResults(response.data.places);
            $('#search-results-container').show();
          } else {
            $('#search-results').html('<div class="alert alert-warning">Nie znaleziono wyników. Spróbuj innego zapytania.</div>');
            $('#search-results-container').show();
          }
        },
        error: function(xhr) {
          $('#search-loading').hide();
          alert('Błąd podczas wyszukiwania: ' + (xhr.responseJSON?.message || 'Nieznany błąd'));
        }
      });
    });

    // Enter w polu wyszukiwania
    $('#search-query').on('keypress', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        $('#search-btn').click();
      }
    });

    // Pomiń wyszukiwanie - przejdź od razu do kroku 2
    $('#skip-search').on('click', function(e) {
      e.preventDefault();
      isManualEntry = true;
      selectedPlace = null;
      
      // Wyczyść ukryte pola
      $('#serper_response, #latitude, #longitude, #rating, #rating_count, #price_level, #category, #phone_number, #website, #cid, #address').val('');
      
      // Wyczyść nazwę lokalu i podgląd
      $('#title').val('');
      $('#place-preview-container').hide();
      
      // Aktywuj przycisk "Dalej" i przejdź do kroku 2
      $('#next-to-contact').prop('disabled', false);
      stepper.next();
    });

    // Wyświetlenie wyników wyszukiwania
    function displaySearchResults(places) {
      let html = '<div class="list-group">';
      
      places.forEach(function(place) {
        html += `
          <div class="list-group-item list-group-item-action place-result" data-place='${JSON.stringify(place).replace(/'/g, '&#39;')}'>
            <div class="d-flex w-100 justify-content-between">
              <h6 class="mb-1">${place.title || 'Brak nazwy'}</h6>
              ${place.rating ? `<small class="text-warning"><i class="ti ti-star-filled"></i> ${place.rating} (${place.ratingCount || 0})</small>` : ''}
            </div>
            <p class="mb-1 text-muted"><i class="ti ti-map-pin"></i> ${place.address || 'Brak adresu'}</p>
            <div class="d-flex gap-2 flex-wrap">
              ${place.category ? `<small class="badge bg-label-primary">${place.category}</small>` : ''}
              ${place.priceLevel ? `<small class="badge bg-label-secondary">${place.priceLevel}</small>` : ''}
              ${place.phoneNumber ? `<small class="text-muted"><i class="ti ti-phone"></i> ${place.phoneNumber}</small>` : ''}
            </div>
          </div>
        `;
      });
      
      html += '</div>';
      $('#search-results').html(html);

      // Obsługa kliknięcia w wynik
      $('.place-result').on('click', function() {
        selectedPlace = JSON.parse($(this).attr('data-place'));
        isManualEntry = false;
        
        // Podświetl wybrany element
        $('.place-result').removeClass('active');
        $(this).addClass('active');
        
        // Wypełnij ukryte pola
        $('#serper_response').val(JSON.stringify(selectedPlace));
        $('#title').val(selectedPlace.title || '');
        $('#address').val(selectedPlace.address || '');
        $('#latitude').val(selectedPlace.latitude || '');
        $('#longitude').val(selectedPlace.longitude || '');
        $('#rating').val(selectedPlace.rating || '');
        $('#rating_count').val(selectedPlace.ratingCount || '');
        $('#price_level').val(selectedPlace.priceLevel || '');
        $('#category').val(selectedPlace.category || '');
        $('#phone_number').val(selectedPlace.phoneNumber || '');
        $('#website').val(selectedPlace.website || '');
        $('#cid').val(selectedPlace.cid || '');
        
        // Aktywuj przycisk "Dalej"
        $('#next-to-contact').prop('disabled', false);
        
        // Przygotuj podgląd dla kroku 2
        preparePreview();
      });
    }

    // Przygotowanie podglądu wizytówki
    function preparePreview() {
      if (!selectedPlace) {
        $('#place-preview-container').hide();
        return;
      }

      let previewHtml = `
        <div class="row g-2">
          <div class="col-12">
            <strong>${selectedPlace.title || 'Brak nazwy'}</strong>
          </div>
          <div class="col-12">
            <small class="text-muted">
              <i class="ti ti-map-pin me-1"></i>${selectedPlace.address || 'Brak adresu'}
            </small>
          </div>
          <div class="col-12 d-flex gap-3 flex-wrap">
            ${selectedPlace.rating ? `<small><i class="ti ti-star-filled text-warning"></i> ${selectedPlace.rating} (${selectedPlace.ratingCount || 0} opinii)</small>` : ''}
            ${selectedPlace.category ? `<small><i class="ti ti-category me-1"></i>${selectedPlace.category}</small>` : ''}
            ${selectedPlace.priceLevel ? `<small><i class="ti ti-currency-dollar me-1"></i>${selectedPlace.priceLevel}</small>` : ''}
          </div>
          ${selectedPlace.phoneNumber ? `<div class="col-12"><small><i class="ti ti-phone me-1"></i>${selectedPlace.phoneNumber}</small></div>` : ''}
          ${selectedPlace.website ? `<div class="col-12"><small><i class="ti ti-world me-1"></i><a href="${selectedPlace.website}" target="_blank">${selectedPlace.website}</a></small></div>` : ''}
        </div>
      `;
      
      $('#place-preview-content').html(previewHtml);
      $('#place-preview-container').show();
    }

    // Obsługa przejścia do kroku 2 - pokaż podgląd jeśli wybrano miejsce
    leadWizard.addEventListener('show.bs-stepper', function(event) {
      if (event.detail.to === 1 && selectedPlace && !isManualEntry) { // Krok 2 (indeks 1)
        preparePreview();
      }
    });
  }
});

