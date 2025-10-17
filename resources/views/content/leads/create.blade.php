@extends('layouts/layoutMaster')

@section('title', 'Dodaj Lead')

<!-- Vendor Styles -->
@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/tagify/tagify.css') }}" />
@endsection

<!-- Page Styles -->
@section('page-style')
<style>
.search-results {
    max-height: 400px;
    overflow-y: auto;
}
.place-result {
    cursor: pointer;
    transition: all 0.2s;
}
.place-result:hover {
    background-color: #f5f5f9;
}
.place-preview {
    background-color: #f8f9fa;
    border-left: 4px solid #696cff;
    padding: 1rem;
    margin-bottom: 1rem;
}
/* Drag & Drop dla Tagify */
.tagify__tag {
    cursor: move;
}
.sortable-ghost {
    opacity: 0.4;
}
</style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/tagify/tagify.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="card-header">
        <h4 class="mb-0">Dodaj nowy lead</h4>
      </div>
      <div class="card-body">
        
        <!-- Wizard -->
        <div class="bs-stepper wizard-numbered" id="lead-wizard">
          <div class="bs-stepper-header">
            <div class="step" data-target="#search-step">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle">1</span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Wyszukaj lokal</span>
                  <span class="bs-stepper-subtitle">Znajdź wizytówkę Google</span>
                </span>
              </button>
            </div>
            <div class="line">
              <i class="ti ti-chevron-right"></i>
            </div>
            <div class="step" data-target="#contact-step">
              <button type="button" class="step-trigger">
                <span class="bs-stepper-circle">2</span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Dane kontaktowe</span>
                  <span class="bs-stepper-subtitle">Osoba kontaktowa</span>
                </span>
              </button>
            </div>
          </div>

          <div class="bs-stepper-content">
            <form id="lead-form" method="POST" action="{{ route('leads.store') }}">
              @csrf
              
              <!-- Krok 1: Wyszukiwanie -->
              <div id="search-step" class="content">
                <div class="content-header mb-4">
                  <h6 class="mb-0">Wyszukaj lokal w Google Places</h6>
                  <small>Wprowadź nazwę lokalu, aby znaleźć wizytówkę w Google</small>
                </div>
                
                <div class="row g-6">
                  <div class="col-12">
                    <label class="form-label" for="search-query">Nazwa lokalu</label>
                    <div class="input-group">
                      <input type="text" id="search-query" class="form-control" placeholder="np. Deja Vu Pub Poznań" />
                      <button type="button" class="btn btn-primary" id="search-btn" data-search-url="{{ route('leads.search-places') }}">
                        <i class="ti ti-search me-1"></i> Szukaj
                      </button>
                    </div>
                    <div id="search-loading" class="mt-2" style="display: none;">
                      <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Wyszukiwanie...</span>
                      </div>
                      <span class="ms-2">Wyszukiwanie...</span>
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                      <span class="alert-icon text-info me-2">
                        <i class="ti ti-info-circle ti-sm"></i>
                      </span>
                      <span>
                        Chcesz dodać leada bez wyszukiwania w Google? 
                        <a href="#" id="skip-search" class="alert-link fw-semibold">Kliknij tutaj</a>
                      </span>
                    </div>
                  </div>

                  <!-- Wyniki wyszukiwania -->
                  <div class="col-12" id="search-results-container" style="display: none;">
                    <h6 class="mb-3">Wyniki wyszukiwania:</h6>
                    <div id="search-results" class="search-results"></div>
                  </div>

                  <!-- Ukryte pola do przechowania danych z Serper -->
                  <input type="hidden" name="serper_response" id="serper_response">
                  <input type="hidden" name="latitude" id="latitude">
                  <input type="hidden" name="longitude" id="longitude">
                  <input type="hidden" name="rating" id="rating">
                  <input type="hidden" name="rating_count" id="rating_count">
                  <input type="hidden" name="price_level" id="price_level">
                  <input type="hidden" name="category" id="category">
                  <input type="hidden" name="phone_number" id="phone_number">
                  <input type="hidden" name="website" id="website">
                  <input type="hidden" name="cid" id="cid">
                  <input type="hidden" name="address" id="address">

                  <div class="col-12 d-flex justify-content-between mt-4">
                    <a href="{{ route('leads.index') }}" class="btn btn-label-secondary">
                      <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                      <span class="align-middle d-sm-inline-block d-none">Anuluj</span>
                    </a>
                    <button type="button" class="btn btn-primary btn-next" id="next-to-contact" disabled> 
                      <span class="align-middle d-sm-inline-block d-none me-sm-2">Dalej</span> 
                      <i class="ti ti-arrow-right ti-xs"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Krok 2: Dane kontaktowe -->
              <div id="contact-step" class="content">
                <div class="content-header mb-4">
                  <h6 class="mb-0">Dane lokalu i osoby kontaktowej</h6>
                  <small>Uzupełnij informacje o osobie kontaktowej</small>
                </div>

                <div class="row g-6">
                  <!-- Podgląd wizytówki (jeśli z Serper) -->
                  <div class="col-12" id="place-preview-container" style="display: none;">
                    <div class="place-preview">
                      <h6 class="mb-2">
                        <i class="ti ti-map-pin text-primary me-2"></i>
                        Wybrana wizytówka:
                      </h6>
                      <div id="place-preview-content"></div>
                    </div>
                  </div>

                  <!-- Nazwa lokalu -->
                  <div class="col-12">
                    <label class="form-label" for="title">Nazwa lokalu <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                           placeholder="np. Deja Vu Pub" required value="{{ old('title') }}" />
                    @error('title')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Dane osoby kontaktowej -->
                  <div class="col-sm-6">
                    <label class="form-label" for="contact_first_name">Imię <span class="text-danger">*</span></label>
                    <input type="text" name="contact_first_name" id="contact_first_name" 
                           class="form-control @error('contact_first_name') is-invalid @enderror" 
                           placeholder="Jan" required value="{{ old('contact_first_name') }}" />
                    @error('contact_first_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-sm-6">
                    <label class="form-label" for="contact_last_name">Nazwisko <span class="text-danger">*</span></label>
                    <input type="text" name="contact_last_name" id="contact_last_name" 
                           class="form-control @error('contact_last_name') is-invalid @enderror" 
                           placeholder="Kowalski" required value="{{ old('contact_last_name') }}" />
                    @error('contact_last_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-sm-6">
                    <label class="form-label" for="contact_position">Stanowisko</label>
                    <select name="contact_position" id="contact_position" 
                            class="form-select @error('contact_position') is-invalid @enderror">
                      <option value="">Wybierz stanowisko</option>
                      <option value="właściciel" {{ old('contact_position') == 'właściciel' ? 'selected' : '' }}>Właściciel</option>
                      <option value="manager" {{ old('contact_position') == 'manager' ? 'selected' : '' }}>Manager</option>
                      <option value="sekretarka" {{ old('contact_position') == 'sekretarka' ? 'selected' : '' }}>Sekretarka</option>
                      <option value="pracownik" {{ old('contact_position') == 'pracownik' ? 'selected' : '' }}>Pracownik</option>
                    </select>
                    @error('contact_position')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-sm-6">
                    <label class="form-label" for="contact_phone">Telefon kontaktowy <span class="text-danger">*</span></label>
                    <input type="tel" name="contact_phone" id="contact_phone" 
                           class="form-control @error('contact_phone') is-invalid @enderror" 
                           placeholder="+48 123 456 789" required value="{{ old('contact_phone') }}" />
                    @error('contact_phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Prywatny numer telefonu osoby kontaktowej</small>
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="contact_email">Email</label>
                    <input type="email" name="contact_email" id="contact_email" 
                           class="form-control @error('contact_email') is-invalid @enderror" 
                           placeholder="jan.kowalski@example.com" value="{{ old('contact_email') }}" />
                    @error('contact_email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="notes">Notatki (opcjonalne)</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                              placeholder="Dodatkowe informacje...">{{ old('notes') }}</textarea>
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="phrases">Frazy <span class="text-danger">*</span></label>
                    <input name="phrases" id="phrases" class="form-control @error('phrases') is-invalid @enderror" 
                           placeholder="Wpisz frazę i naciśnij Enter" value="{{ old('phrases') }}" />
                    @error('phrases')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Dodaj frazy, które będą wykorzystane w kampanii. Możesz dodać wiele fraz.</small>
                  </div>

                  <div class="col-12 d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-label-secondary btn-prev"> 
                      <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                      <span class="align-middle d-sm-inline-block d-none">Wstecz</span>
                    </button>
                    <button type="submit" class="btn btn-success">
                      <i class="ti ti-check me-1"></i> Zapisz lead
                    </button>
                  </div>
                </div>
              </div>

            </form>
          </div>
        </div>
        <!-- /Wizard -->

      </div>
    </div>
  </div>
</div>

<!-- Page Scripts -->
@section('page-script')
<script>
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

      const searchUrl = $(this).data('search-url');

      $.ajax({
        url: searchUrl,
        method: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
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
          const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Nieznany błąd';
          alert('Błąd podczas wyszukiwania: ' + message);
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
        const placeJson = JSON.stringify(place).replace(/'/g, '&#39;');
        html += '<div class="list-group-item list-group-item-action place-result" data-place=\'' + placeJson + '\'>';
        html += '<div class="d-flex w-100 justify-content-between">';
        html += '<h6 class="mb-1">' + (place.title || 'Brak nazwy') + '</h6>';
        if (place.rating) {
          html += '<small class="text-warning"><i class="ti ti-star-filled"></i> ' + place.rating + ' (' + (place.ratingCount || 0) + ')</small>';
        }
        html += '</div>';
        html += '<p class="mb-1 text-muted"><i class="ti ti-map-pin"></i> ' + (place.address || 'Brak adresu') + '</p>';
        html += '<div class="d-flex gap-2 flex-wrap">';
        if (place.category) {
          html += '<small class="badge bg-label-primary">' + place.category + '</small>';
        }
        if (place.priceLevel) {
          html += '<small class="badge bg-label-secondary">' + place.priceLevel + '</small>';
        }
        if (place.phoneNumber) {
          html += '<small class="text-muted"><i class="ti ti-phone"></i> ' + place.phoneNumber + '</small>';
        }
        html += '</div>';
        html += '</div>';
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

      let previewHtml = '<div class="row g-2">';
      previewHtml += '<div class="col-12"><strong>' + (selectedPlace.title || 'Brak nazwy') + '</strong></div>';
      previewHtml += '<div class="col-12"><small class="text-muted"><i class="ti ti-map-pin me-1"></i>' + (selectedPlace.address || 'Brak adresu') + '</small></div>';
      previewHtml += '<div class="col-12 d-flex gap-3 flex-wrap">';
      if (selectedPlace.rating) {
        previewHtml += '<small><i class="ti ti-star-filled text-warning"></i> ' + selectedPlace.rating + ' (' + (selectedPlace.ratingCount || 0) + ' opinii)</small>';
      }
      if (selectedPlace.category) {
        previewHtml += '<small><i class="ti ti-category me-1"></i>' + selectedPlace.category + '</small>';
      }
      if (selectedPlace.priceLevel) {
        previewHtml += '<small><i class="ti ti-currency-dollar me-1"></i>' + selectedPlace.priceLevel + '</small>';
      }
      previewHtml += '</div>';
      if (selectedPlace.phoneNumber) {
        previewHtml += '<div class="col-12"><small><i class="ti ti-phone me-1"></i>' + selectedPlace.phoneNumber + '</small></div>';
      }
      if (selectedPlace.website) {
        previewHtml += '<div class="col-12"><small><i class="ti ti-world me-1"></i><a href="' + selectedPlace.website + '" target="_blank">' + selectedPlace.website + '</a></small></div>';
      }
      previewHtml += '</div>';
      
      $('#place-preview-content').html(previewHtml);
      $('#place-preview-container').show();
    }

    // Obsługa przejścia do kroku 2 - pokaż podgląd jeśli wybrano miejsce
    leadWizard.addEventListener('show.bs-stepper', function(event) {
      if (event.detail.to === 1 && selectedPlace && !isManualEntry) {
        preparePreview();
      }
    });
  }

  // Inicjalizacja Tagify dla pola fraz
  const phrasesInput = document.querySelector('#phrases');
  if (phrasesInput) {
    const tagify = new Tagify(phrasesInput, {
      duplicates: false,
      maxTags: 50,
      placeholder: 'Wpisz frazę i naciśnij Enter',
      dropdown: {
        enabled: 0 // Wyłącz dropdown
      }
    });

    // Dodaj ukryte pole do przechowywania JSON
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'phrases';
    hiddenInput.id = 'phrases_json';
    phrasesInput.parentNode.appendChild(hiddenInput);

    // Usuń atrybut name z oryginalnego inputa
    phrasesInput.removeAttribute('name');

    // Włącz drag & drop używając Sortable
    new Sortable(tagify.DOM.scope, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd: function() {
        // Aktualizuj wartość Tagify po przesunięciu
        tagify.updateValueByDOMTags();
        hiddenInput.value = JSON.stringify(tagify.value);
      }
    });

    // Aktualizuj ukryte pole przy zmianach (dodawanie, usuwanie)
    tagify.on('add remove', function() {
      hiddenInput.value = JSON.stringify(tagify.value);
    });
  }
});
</script>
@endsection
@endsection
