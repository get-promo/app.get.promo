@extends('layouts/layoutMaster')

@section('title', 'Szczegóły leada')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Szczegóły leada</h5>
        <div>
          <form id="generateReportForm" action="{{ route('leads.generate-report', $lead) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success btn-sm me-2" id="generateReportBtn">
              <i class="ti ti-file-analytics me-1"></i> Generuj raport
            </button>
          </form>
          <a href="{{ route('leads.edit', $lead) }}" class="btn btn-primary btn-sm me-2">
            <i class="ti ti-pencil me-1"></i> Edytuj
          </a>
          <a href="{{ route('leads.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Powrót
          </a>
        </div>
      </div>
      <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert" id="successAlert">
          {{ session('success') }}
          @if(session('report_url'))
            <div class="mt-2">
              <strong>URL raportu:</strong>
              <div class="input-group mt-1">
                <input type="text" class="form-control" value="{{ session('report_url') }}" id="reportUrl" readonly>
                <button class="btn btn-outline-primary" type="button" onclick="copyReportUrl()">
                  <i class="ti ti-copy"></i> Kopiuj
                </button>
                <a href="{{ session('report_url') }}" target="_blank" class="btn btn-outline-secondary">
                  <i class="ti ti-external-link"></i> Otwórz
                </a>
              </div>
            </div>
          @endif
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('info'))
        <div class="alert alert-info alert-dismissible" role="alert">
          {{ session('info') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Progress Bar dla generowania raportu -->
        <div id="reportProgressContainer" class="alert alert-primary" role="alert" style="display: none;">
          <div class="d-flex align-items-center mb-2">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
              <span class="visually-hidden">Ładowanie...</span>
            </div>
            <strong>Generowanie raportu w toku...</strong>
          </div>
          <div class="progress mb-2" style="height: 25px;">
            <div id="reportProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
              0%
            </div>
          </div>
          <small id="reportProgressStep" class="text-muted">Inicjalizacja...</small>
        </div>

        <div class="row">
          <!-- Informacje o lokalu -->
          <div class="col-md-6">
            <h6 class="mb-3">
              <i class="ti ti-building-store me-2 text-primary"></i>
              Informacje o lokalu
            </h6>
            
            <div class="mb-3">
              <label class="form-label fw-medium">Nazwa lokalu:</label>
              <p>{{ $lead->title }}</p>
            </div>

            @if($lead->address)
            <div class="mb-3">
              <label class="form-label fw-medium">Adres:</label>
              <p>
                <i class="ti ti-map-pin text-muted me-1"></i>
                {{ $lead->address }}
              </p>
            </div>
            @endif

            @if($lead->category)
            <div class="mb-3">
              <label class="form-label fw-medium">Kategoria:</label>
              <p><span class="badge bg-label-primary">{{ $lead->category }}</span></p>
            </div>
            @endif

            @if($lead->rating)
            <div class="mb-3">
              <label class="form-label fw-medium">Ocena Google:</label>
              <p>
                <span class="text-warning">
                  <i class="ti ti-star-filled"></i> {{ $lead->rating }}
                </span>
                @if($lead->rating_count)
                  <span class="text-muted">({{ $lead->rating_count }} opinii)</span>
                @endif
              </p>
            </div>
            @endif

            @if($lead->price_level)
            <div class="mb-3">
              <label class="form-label fw-medium">Poziom cen:</label>
              <p>{{ $lead->price_level }}</p>
            </div>
            @endif

            @if($lead->phone_number)
            <div class="mb-3">
              <label class="form-label fw-medium">Telefon publiczny:</label>
              <p>
                <i class="ti ti-phone text-muted me-1"></i>
                <a href="tel:{{ $lead->phone_number }}">{{ $lead->phone_number }}</a>
              </p>
            </div>
            @endif

            @if($lead->website)
            <div class="mb-3">
              <label class="form-label fw-medium">Strona internetowa:</label>
              <p>
                <i class="ti ti-world text-muted me-1"></i>
                <a href="{{ $lead->website }}" target="_blank">{{ $lead->website }}</a>
              </p>
            </div>
            @endif
          </div>

          <!-- Osoba kontaktowa -->
          <div class="col-md-6">
            <h6 class="mb-3">
              <i class="ti ti-user me-2 text-primary"></i>
              Osoba kontaktowa
            </h6>

            <div class="mb-3">
              <label class="form-label fw-medium">Imię i nazwisko:</label>
              <p>{{ $lead->contact_first_name }} {{ $lead->contact_last_name }}</p>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Stanowisko:</label>
              <p><span class="badge bg-label-info">{{ ucfirst($lead->contact_position) }}</span></p>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Telefon kontaktowy:</label>
              <p>
                <i class="ti ti-phone text-muted me-1"></i>
                <a href="tel:{{ $lead->contact_phone }}">{{ $lead->contact_phone }}</a>
              </p>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Email:</label>
              <p>
                <i class="ti ti-mail text-muted me-1"></i>
                <a href="mailto:{{ $lead->contact_email }}">{{ $lead->contact_email }}</a>
              </p>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Status:</label>
              <p>
                @php
                  $statusClasses = [
                    'new' => 'bg-label-info',
                    'contacted' => 'bg-label-primary',
                    'qualified' => 'bg-label-warning',
                    'converted' => 'bg-label-success',
                    'rejected' => 'bg-label-danger'
                  ];
                  $statusLabels = [
                    'new' => 'Nowy',
                    'contacted' => 'Skontaktowany',
                    'qualified' => 'Zakwalifikowany',
                    'converted' => 'Skonwertowany',
                    'rejected' => 'Odrzucony'
                  ];
                @endphp
                <span class="badge {{ $statusClasses[$lead->status] ?? 'bg-label-secondary' }}">
                  {{ $statusLabels[$lead->status] ?? ucfirst($lead->status) }}
                </span>
              </p>
            </div>

            @if($lead->notes)
            <div class="mb-3">
              <label class="form-label fw-medium">Notatki:</label>
              <p>{{ $lead->notes }}</p>
            </div>
            @endif

            <div class="mb-3">
              <label class="form-label fw-medium">Frazy:</label>
              <div class="d-flex flex-wrap gap-2">
                @forelse($lead->phrases as $phrase)
                  <span class="badge bg-label-primary">{{ $phrase->phrase }}</span>
                @empty
                  <p class="text-muted mb-0">Brak przypisanych fraz</p>
                @endforelse
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Data dodania:</label>
              <p>
                <i class="ti ti-calendar text-muted me-1"></i>
                {{ $lead->created_at->format('d.m.Y H:i') }}
              </p>
            </div>

            @if($lead->updated_at != $lead->created_at)
            <div class="mb-3">
              <label class="form-label fw-medium">Ostatnia aktualizacja:</label>
              <p>
                <i class="ti ti-clock text-muted me-1"></i>
                {{ $lead->updated_at->format('d.m.Y H:i') }}
              </p>
            </div>
            @endif
          </div>
        </div>

        @if($lead->latitude && $lead->longitude)
        <hr class="my-4">
        <div class="row">
          <div class="col-12">
            <h6 class="mb-3">
              <i class="ti ti-map-2 me-2 text-primary"></i>
              Lokalizacja
            </h6>
            <div class="ratio ratio-21x9">
              <iframe
                src="https://maps.google.com/maps?q={{ $lead->latitude }},{{ $lead->longitude }}&hl=pl&z=15&output=embed"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
          </div>
        </div>
        @endif

        @if($lead->serper_response)
        <hr class="my-4">
        <div class="row">
          <div class="col-12">
            <h6 class="mb-3">
              <i class="ti ti-code me-2 text-primary"></i>
              Dane z Google Places (Serper)
            </h6>
            <div class="bg-lighter p-3 rounded">
              <pre class="mb-0"><code>{{ json_encode($lead->serper_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="card-footer">
        <form action="{{ route('leads.destroy', $lead) }}" method="POST" 
              onsubmit="return confirm('Czy na pewno chcesz usunąć tego leada? Ta operacja jest nieodwracalna.');"
              class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="ti ti-trash me-1"></i> Usuń leada
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
// Obsługa formularza generowania raportu - pokaż progress bar natychmiast
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('generateReportForm');
  const btn = document.getElementById('generateReportBtn');
  const progressContainer = document.getElementById('reportProgressContainer');
  
  if (form && btn && progressContainer) {
    form.addEventListener('submit', function(e) {
      // Zatrzymaj domyślne wysłanie
      e.preventDefault();
      
      // Pokaż confirm
      if (!confirm('Czy na pewno chcesz wygenerować raport? To może potrwać kilka minut.')) {
        return false;
      }
      
      // Natychmiast pokaż progress bar
      progressContainer.style.display = 'block';
      const progressBar = document.getElementById('reportProgressBar');
      const progressStep = document.getElementById('reportProgressStep');
      
      if (progressBar) {
        progressBar.style.width = '5%';
        progressBar.textContent = '5%';
      }
      if (progressStep) {
        progressStep.textContent = 'Uruchamianie generowania raportu...';
      }
      
      // Wyłącz przycisk
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uruchamianie...';
      
      // Wyślij formularz
      form.submit();
    });
  }
});

function copyReportUrl() {
  const input = document.getElementById('reportUrl');
  input.select();
  document.execCommand('copy');
  
  // Pokaż feedback
  const btn = event.target.closest('button');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="ti ti-check"></i> Skopiowano!';
  btn.classList.remove('btn-outline-primary');
  btn.classList.add('btn-success');
  
  setTimeout(() => {
    btn.innerHTML = originalText;
    btn.classList.remove('btn-success');
    btn.classList.add('btn-outline-primary');
  }, 2000);
}

// Polling dla statusu generowania raportu
@if(session('job_id'))
(function() {
  const jobId = '{{ session('job_id') }}';
  const progressContainer = document.getElementById('reportProgressContainer');
  const progressBar = document.getElementById('reportProgressBar');
  const progressStep = document.getElementById('reportProgressStep');
  const successAlert = document.getElementById('successAlert');
  
  // Pokaż progress bar (jeśli nie jest już pokazany z poprzedniego eventu)
  if (progressContainer.style.display !== 'block') {
    progressContainer.style.display = 'block';
  }
  
  // Ukryj success alert jeśli jest
  if (successAlert) {
    successAlert.style.display = 'none';
  }
  
  let pollInterval;
  
  function checkStatus() {
    fetch(`/api/reports/status/${jobId}`)
      .then(response => response.json())
      .then(data => {
        // Aktualizuj progress bar
        const progress = data.progress || 0;
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        progressBar.textContent = progress + '%';
        
        // Aktualizuj opis kroku
        if (data.current_step) {
          progressStep.textContent = data.current_step;
        }
        
        // Sprawdź status
        if (data.status === 'completed') {
          clearInterval(pollInterval);
          progressBar.classList.remove('progress-bar-animated');
          progressBar.classList.remove('progress-bar-striped');
          progressBar.classList.add('bg-success');
          progressStep.textContent = 'Raport został wygenerowany!';
          
          // Pokaż URL raportu
          if (data.report_url) {
            setTimeout(() => {
              progressContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible" role="alert">
                  <strong>✓ Raport został wygenerowany pomyślnie!</strong>
                  <div class="mt-2">
                    <strong>URL raportu:</strong>
                    <div class="input-group mt-1">
                      <input type="text" class="form-control" value="${data.report_url}" id="reportUrlNew" readonly>
                      <button class="btn btn-outline-primary" type="button" onclick="copyReportUrlNew()">
                        <i class="ti ti-copy"></i> Kopiuj
                      </button>
                      <a href="${data.report_url}" target="_blank" class="btn btn-success">
                        <i class="ti ti-external-link"></i> Zobacz raport
                      </a>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              `;
            }, 1000);
          }
        } else if (data.status === 'failed') {
          clearInterval(pollInterval);
          progressBar.classList.remove('progress-bar-animated');
          progressBar.classList.add('bg-danger');
          progressStep.textContent = 'Błąd: ' + (data.error_message || 'Nieznany błąd');
          
          setTimeout(() => {
            progressContainer.innerHTML = `
              <div class="alert alert-danger alert-dismissible" role="alert">
                <strong>✗ Generowanie raportu nie powiodło się</strong>
                <p class="mb-0 mt-2">${data.error_message || 'Nieznany błąd'}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            `;
          }, 1000);
        }
      })
      .catch(error => {
        console.error('Error checking report status:', error);
        clearInterval(pollInterval);
        progressStep.textContent = 'Błąd podczas sprawdzania statusu';
      });
  }
  
  // Sprawdź status co 2 sekundy
  checkStatus();
  pollInterval = setInterval(checkStatus, 2000);
})();

function copyReportUrlNew() {
  const input = document.getElementById('reportUrlNew');
  input.select();
  document.execCommand('copy');
  
  const btn = event.target.closest('button');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="ti ti-check"></i> Skopiowano!';
  btn.classList.remove('btn-outline-primary');
  btn.classList.add('btn-success');
  
  setTimeout(() => {
    btn.innerHTML = originalText;
    btn.classList.remove('btn-success');
    btn.classList.add('btn-outline-primary');
  }, 2000);
}
@endif
</script>
@endsection
@endsection
