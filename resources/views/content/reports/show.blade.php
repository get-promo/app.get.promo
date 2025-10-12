<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="Raport widoczności - {{ $report->business_name }}">
    <meta property="og:type" content="website">
    <title>Raport widoczności - {{ $report->business_name }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
        }
        .score-card {
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .score-value {
            font-size: 4rem;
            font-weight: 700;
            margin: 0;
        }
        .score-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .score-0-1-9 { color: #dc3545; }
        .score-2-0-2-9 { color: #fd7e14; }
        .score-3-0-3-9 { color: #ffc107; }
        .score-4-0-4-4 { color: #20c997; }
        .score-4-5-5-0 { color: #28a745; }
        
        #map {
            height: 500px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .breakdown-item:last-child {
            border-bottom: none;
        }
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 3rem;
        }
        .cta-btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            margin: 0.5rem;
        }
        .badge-score {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-2">{{ $report->business_name }}</h1>
            <p class="text-muted">
                Raport widoczności wygenerowany: {{ $report->generated_at->format('d.m.Y H:i') }}
            </p>
            @if($report->search_query)
                <p class="text-muted">Zapytanie: <strong>{{ $report->search_query }}</strong></p>
            @endif
        </div>

        <!-- Scores -->
        <div class="row mb-5">
            <div class="col-md-6 mb-4">
                <div class="score-card">
                    <p class="score-label">Position Score</p>
                    <h2 class="score-value {{ 'score-' . str_replace('.', '-', number_format($report->position_score, 1, '-', '')) }}">
                        {{ number_format($report->position_score, 1) }}
                    </h2>
                    <p class="text-muted">na 5.0</p>
                    @if($report->position)
                        <small class="text-muted">Pozycja: {{ $report->position }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="score-card">
                    <p class="score-label">Profile Quality Score</p>
                    <h2 class="score-value {{ 'score-' . str_replace('.', '-', number_format($report->profile_quality_score, 1, '-', '')) }}">
                        {{ number_format($report->profile_quality_score, 1) }}
                    </h2>
                    <p class="text-muted">na 5.0</p>
                </div>
            </div>
        </div>

        <!-- Comparison with competitors -->
        @if($report->avg_competitor_quality_score)
        <div class="card mb-5">
            <div class="card-body">
                <h3 class="card-title mb-4">Porównanie z konkurencją</h3>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Twój Position Score:</strong> {{ number_format($report->position_score, 1) }}</p>
                        <p><strong>Średnia konkurencji:</strong> {{ number_format($report->avg_competitor_position_score, 1) }}</p>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: {{ ($report->position_score / 5) * 100 }}%">
                                {{ number_format($report->position_score, 1) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Twój Profile Quality Score:</strong> {{ number_format($report->profile_quality_score, 1) }}</p>
                        <p class="text-muted"><small>(Ocena jakości profilu jest obliczana tylko dla Twojego biznesu)</small></p>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-info" style="width: {{ ($report->profile_quality_score / 5) * 100 }}%">
                                {{ number_format($report->profile_quality_score, 1) }}
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-3">Liczba konkurentów w analizie: {{ $report->competitors_count }}</p>
            </div>
        </div>
        @endif

        <!-- Map -->
        <div class="card mb-5">
            <div class="card-body">
                <h3 class="card-title mb-3">Mapa konkurencji</h3>
                <p class="text-muted mb-4"><small>Kolory punktów na mapie odpowiadają <strong>Position Score</strong> (pozycja w wynikach wyszukiwania). 🎯 <strong>Większy punkt z czarnym borderem = Twój biznes</strong></small></p>
                <div id="map"></div>
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Legenda:</strong>
                        <span class="badge bg-danger">0-1.9</span>
                        <span class="badge bg-warning">2.0-2.9</span>
                        <span class="badge" style="background-color: #ffc107;">3.0-3.9</span>
                        <span class="badge" style="background-color: #20c997;">4.0-4.4</span>
                        <span class="badge bg-success">4.5-5.0</span>
                    </small>
                </div>
            </div>
        </div>

        <!-- Breakdown -->
        <div class="card mb-5">
            <div class="card-body">
                <h3 class="card-title mb-4">Szczegółowa analiza profilu</h3>
                @foreach($report->score_breakdown as $key => $component)
                    @if(!($component['unknown'] ?? false))
                    <div class="breakdown-item">
                        <div>
                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong>
                            <br>
                            <small class="text-muted">{{ $component['note'] ?? '' }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge-score {{ 'bg-' . ($component['score'] >= 4.0 ? 'success' : ($component['score'] >= 3.0 ? 'warning' : 'danger')) }}">
                                {{ number_format($component['score'], 1) }} / 5.0
                            </span>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <h2 class="mb-4">Chcesz poprawić swoją widoczność?</h2>
            <p class="lead mb-4">Skorzystaj z naszych usług SEO i SEM</p>
            <button class="cta-btn btn btn-light">SEO - 399 zł/mies</button>
            <button class="cta-btn btn btn-outline-light">SEM + SEO - 799 zł/mies</button>
        </div>

        <div class="text-center mt-5">
            <p class="text-muted">
                <small>© {{ date('Y') }} get.promo - Raport wygenerowany automatycznie</small>
            </p>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize map
        const map = L.map('map').setView([{{ $report->places_data['latitude'] ?? 52.2297 }}, {{ $report->places_data['longitude'] ?? 21.0122 }}], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Function to get marker color based on score
        function getMarkerColor(score) {
            if (score >= 4.5) return '#28a745';
            if (score >= 4.0) return '#20c997';
            if (score >= 3.0) return '#ffc107';
            if (score >= 2.0) return '#fd7e14';
            return '#dc3545';
        }

        // Add main business marker (TWÓJ BIZNES - większy i zawsze na wierzchu)
        @if(isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
        const mainMarker = L.circleMarker([{{ $report->places_data['latitude'] }}, {{ $report->places_data['longitude'] }}], {
            radius: 14,  // Większy niż konkurenci (8)
            fillColor: getMarkerColor({{ $report->position_score }}),
            color: '#000',  // Czarny border dla lepszej widoczności
            weight: 3,      // Grubszy border
            opacity: 1,
            fillOpacity: 0.9,
            zIndexOffset: 1000  // Zawsze na wierzchu
        }).addTo(map);
        
        mainMarker.bindPopup(`
            <strong>🎯 {{ $report->business_name }}</strong> (TWÓJ BIZNES)<br>
            Pozycja: {{ $report->position ?? 'N/A' }}<br>
            Position Score: {{ number_format($report->position_score, 1) }}<br>
            Profile Quality Score: {{ number_format($report->profile_quality_score, 1) }}
        `);
        @endif

        // Add competitors markers
        const competitors = @json($report->competitors);
        competitors.forEach(comp => {
            if (comp.latitude && comp.longitude) {
                const positionScore = comp.position_score ? parseFloat(comp.position_score) : 0;
                
                const marker = L.circleMarker([comp.latitude, comp.longitude], {
                    radius: 8,
                    fillColor: getMarkerColor(positionScore),
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.6
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong>${comp.name}</strong><br>
                    Pozycja: ${comp.position || 'N/A'}<br>
                    Position Score: ${positionScore > 0 ? positionScore.toFixed(1) : 'N/A'}
                `);
            }
        });

        // Fit bounds to show all markers
        const bounds = [];
        @if(isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
        bounds.push([{{ $report->places_data['latitude'] }}, {{ $report->places_data['longitude'] }}]);
        @endif
        competitors.forEach(comp => {
            if (comp.latitude && comp.longitude) {
                bounds.push([comp.latitude, comp.longitude]);
            }
        });
        
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    </script>
</body>
</html>

