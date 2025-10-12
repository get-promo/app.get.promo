@extends('layouts/layoutMaster')

@section('title', 'Leady')

<!-- Vendor Styles -->
@section('vendor-style')
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Wszystkie leady</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $leads->total() }}</h4>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="ti ti-users ti-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Nowe</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $leads->where('status', 'new')->count() }}</h4>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="ti ti-user-plus ti-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Skontaktowane</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $leads->whereIn('status', ['contacted', 'qualified'])->count() }}</h4>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="ti ti-phone ti-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Skonwertowane</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $leads->where('status', 'converted')->count() }}</h4>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="ti ti-check ti-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Lista leadów</h5>
    <a href="{{ route('leads.create') }}" class="btn btn-primary">
      <i class="ti ti-plus me-1"></i> Dodaj lead
    </a>
  </div>
  
  <div class="card-body">
    <!-- Filtry i wyszukiwanie -->
    <form method="GET" action="{{ route('leads.index') }}" class="mb-4">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="search">Wyszukaj</label>
          <input type="text" name="search" id="search" class="form-control" 
                 placeholder="Nazwa lokalu, osoba, email..." 
                 value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="status">Status</label>
          <select name="status" id="status" class="form-select">
            <option value="">Wszystkie</option>
            <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Nowe</option>
            <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Skontaktowane</option>
            <option value="qualified" {{ request('status') == 'qualified' ? 'selected' : '' }}>Zakwalifikowane</option>
            <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Skonwertowane</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Odrzucone</option>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-search me-1"></i> Szukaj
          </button>
        </div>
      </div>
    </form>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Tabela leadów -->
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Lokal</th>
            <th>Osoba kontaktowa</th>
            <th>Kontakt</th>
            <th>Frazy</th>
            <th>Status</th>
            <th>Data dodania</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          @forelse($leads as $lead)
          <tr>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-medium">{{ $lead->title }}</span>
                @if($lead->address)
                  <small class="text-muted">
                    <i class="ti ti-map-pin ti-xs"></i> {{ \Illuminate\Support\Str::limit($lead->address, 40) }}
                  </small>
                @endif
                @if($lead->rating)
                  <small class="text-warning">
                    <i class="ti ti-star-filled ti-xs"></i> {{ $lead->rating }} 
                    @if($lead->rating_count)
                      ({{ $lead->rating_count }})
                    @endif
                  </small>
                @endif
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span>{{ $lead->contact_first_name }} {{ $lead->contact_last_name }}</span>
                <small class="text-muted">{{ ucfirst($lead->contact_position) }}</small>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <small>
                  <i class="ti ti-phone ti-xs"></i> {{ $lead->contact_phone }}
                </small>
                <small>
                  <i class="ti ti-mail ti-xs"></i> {{ $lead->contact_email }}
                </small>
              </div>
            </td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                @forelse($lead->phrases as $phrase)
                  <span class="badge bg-label-primary">{{ $phrase->phrase }}</span>
                @empty
                  <small class="text-muted">Brak fraz</small>
                @endforelse
              </div>
            </td>
            <td>
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
            </td>
            <td>
              <small>{{ $lead->created_at->format('d.m.Y H:i') }}</small>
            </td>
            <td>
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="ti ti-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('leads.show', $lead) }}">
                    <i class="ti ti-eye me-1"></i> Zobacz
                  </a>
                  <a class="dropdown-item" href="{{ route('leads.edit', $lead) }}">
                    <i class="ti ti-pencil me-1"></i> Edytuj
                  </a>
                  <div class="dropdown-divider"></div>
                  <form action="{{ route('leads.destroy', $lead) }}" method="POST" 
                        onsubmit="return confirm('Czy na pewno chcesz usunąć tego leada?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">
                      <i class="ti ti-trash me-1"></i> Usuń
                    </button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-4">
              <div class="mb-3">
                <i class="ti ti-users-off ti-xl text-muted"></i>
              </div>
              <p class="text-muted mb-0">Brak leadów do wyświetlenia</p>
              <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary mt-2">
                <i class="ti ti-plus me-1"></i> Dodaj pierwszy lead
              </a>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Paginacja -->
    @if($leads->hasPages())
    <div class="mt-4">
      {{ $leads->links() }}
    </div>
    @endif
  </div>
</div>
@endsection
