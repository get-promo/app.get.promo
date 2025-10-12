@extends('layouts/layoutMaster')

@section('title', 'Edytuj lead')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/tagify/tagify.css') }}" />
@endsection

@section('page-style')
<style>
/* Drag & Drop dla Tagify */
.tagify__tag {
    cursor: move;
}
.sortable-ghost {
    opacity: 0.4;
}
</style>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/tagify/tagify.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edytuj lead</h5>
        <a href="{{ route('leads.show', $lead) }}" class="btn btn-label-secondary btn-sm">
          <i class="ti ti-arrow-left me-1"></i> Powrót
        </a>
      </div>
      <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <form method="POST" action="{{ route('leads.update', $lead) }}">
          @csrf
          @method('PUT')

          <div class="row g-4">
            <!-- Informacje o lokalu (tylko do odczytu) -->
            <div class="col-12">
              <h6 class="mb-3">
                <i class="ti ti-building-store me-2 text-primary"></i>
                Informacje o lokalu
              </h6>
            </div>

            <div class="col-md-6">
              <label class="form-label">Nazwa lokalu</label>
              <input type="text" class="form-control" value="{{ $lead->title }}" disabled>
              <small class="text-muted">Nazwa lokalu nie może być edytowana</small>
            </div>

            @if($lead->address)
            <div class="col-md-6">
              <label class="form-label">Adres</label>
              <input type="text" class="form-control" value="{{ $lead->address }}" disabled>
            </div>
            @endif

            @if($lead->category || $lead->rating)
            <div class="col-12">
              <div class="d-flex gap-3">
                @if($lead->category)
                  <span class="badge bg-label-primary">{{ $lead->category }}</span>
                @endif
                @if($lead->rating)
                  <span class="text-warning">
                    <i class="ti ti-star-filled"></i> {{ $lead->rating }}
                    @if($lead->rating_count) ({{ $lead->rating_count }}) @endif
                  </span>
                @endif
              </div>
            </div>
            @endif

            <div class="col-12"><hr></div>

            <!-- Dane osoby kontaktowej (edytowalne) -->
            <div class="col-12">
              <h6 class="mb-3">
                <i class="ti ti-user me-2 text-primary"></i>
                Osoba kontaktowa
              </h6>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contact_first_name">
                Imię <span class="text-danger">*</span>
              </label>
              <input type="text" 
                     name="contact_first_name" 
                     id="contact_first_name" 
                     class="form-control @error('contact_first_name') is-invalid @enderror" 
                     value="{{ old('contact_first_name', $lead->contact_first_name) }}" 
                     required>
              @error('contact_first_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contact_last_name">
                Nazwisko <span class="text-danger">*</span>
              </label>
              <input type="text" 
                     name="contact_last_name" 
                     id="contact_last_name" 
                     class="form-control @error('contact_last_name') is-invalid @enderror" 
                     value="{{ old('contact_last_name', $lead->contact_last_name) }}" 
                     required>
              @error('contact_last_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contact_position">
                Stanowisko
              </label>
              <select name="contact_position" 
                      id="contact_position" 
                      class="form-select @error('contact_position') is-invalid @enderror">
                <option value="">Wybierz stanowisko</option>
                <option value="właściciel" {{ old('contact_position', $lead->contact_position) == 'właściciel' ? 'selected' : '' }}>Właściciel</option>
                <option value="manager" {{ old('contact_position', $lead->contact_position) == 'manager' ? 'selected' : '' }}>Manager</option>
                <option value="sekretarka" {{ old('contact_position', $lead->contact_position) == 'sekretarka' ? 'selected' : '' }}>Sekretarka</option>
                <option value="pracownik" {{ old('contact_position', $lead->contact_position) == 'pracownik' ? 'selected' : '' }}>Pracownik</option>
              </select>
              @error('contact_position')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contact_phone">
                Telefon kontaktowy <span class="text-danger">*</span>
              </label>
              <input type="tel" 
                     name="contact_phone" 
                     id="contact_phone" 
                     class="form-control @error('contact_phone') is-invalid @enderror" 
                     value="{{ old('contact_phone', $lead->contact_phone) }}" 
                     required>
              @error('contact_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Prywatny numer telefonu osoby kontaktowej</small>
            </div>

            <div class="col-12">
              <label class="form-label" for="contact_email">
                Email
              </label>
              <input type="email" 
                     name="contact_email" 
                     id="contact_email" 
                     class="form-control @error('contact_email') is-invalid @enderror" 
                     value="{{ old('contact_email', $lead->contact_email) }}">
              @error('contact_email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12"><hr></div>

            <!-- Status i notatki -->
            <div class="col-12">
              <h6 class="mb-3">
                <i class="ti ti-flag me-2 text-primary"></i>
                Status i notatki
              </h6>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
              <select name="status" 
                      id="status" 
                      class="form-select @error('status') is-invalid @enderror" 
                      required>
                <option value="new" {{ old('status', $lead->status) == 'new' ? 'selected' : '' }}>Nowy</option>
                <option value="contacted" {{ old('status', $lead->status) == 'contacted' ? 'selected' : '' }}>Skontaktowany</option>
                <option value="qualified" {{ old('status', $lead->status) == 'qualified' ? 'selected' : '' }}>Zakwalifikowany</option>
                <option value="converted" {{ old('status', $lead->status) == 'converted' ? 'selected' : '' }}>Skonwertowany</option>
                <option value="rejected" {{ old('status', $lead->status) == 'rejected' ? 'selected' : '' }}>Odrzucony</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="notes">Notatki</label>
              <textarea name="notes" 
                        id="notes" 
                        class="form-control @error('notes') is-invalid @enderror" 
                        rows="4" 
                        placeholder="Dodatkowe informacje, historia kontaktu, uwagi...">{{ old('notes', $lead->notes) }}</textarea>
              @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="phrases">Frazy <span class="text-danger">*</span></label>
              <input name="phrases_display" 
                     id="phrases" 
                     class="form-control @error('phrases') is-invalid @enderror" 
                     placeholder="Wpisz frazę i naciśnij Enter"
                     value="{{ old('phrases', $lead->phrases->pluck('phrase')->implode(',')) }}" />
              @error('phrases')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Dodaj frazy, które będą wykorzystane w kampanii. Możesz dodać wiele fraz.</small>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-1"></i> Zapisz zmiany
              </button>
              <a href="{{ route('leads.show', $lead) }}" class="btn btn-label-secondary ms-2">
                Anuluj
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
'use strict';

$(function() {
  // Inicjalizacja Tagify dla pola fraz
  const phrasesInput = document.querySelector('#phrases');
  if (phrasesInput) {
    // Przygotuj wartość początkową w formacie Tagify
    const initialValue = phrasesInput.value;
    const initialTags = initialValue ? initialValue.split(',').map(phrase => ({value: phrase.trim()})) : [];
    
    const tagify = new Tagify(phrasesInput, {
      duplicates: false,
      maxTags: 50,
      placeholder: 'Wpisz frazę i naciśnij Enter',
      dropdown: {
        enabled: 0 // Wyłącz dropdown
      }
    });

    // Ustaw początkowe tagi
    if (initialTags.length > 0) {
      tagify.addTags(initialTags);
    }

    // Dodaj ukryte pole do przechowywania JSON
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'phrases';
    hiddenInput.id = 'phrases_json';
    hiddenInput.value = JSON.stringify(initialTags);
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
