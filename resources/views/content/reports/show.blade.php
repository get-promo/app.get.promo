<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="Raport widoczności - {{ $report->business_name }}">
    <meta property="og:type" content="website">
    <title>Raport widoczności - {{ $report->business_name }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #333333;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Header */
        .report-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .logo-get {
            color: #4A90E2;
        }
        
        .logo-dot {
            color: #F5A623;
        }
        
        .logo-promo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        /* Score Section */
        .scores-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            gap: 30px;
        }
        
        .main-scores {
            flex: 1;
        }
        
        .score-big {
            margin-bottom: 25px;
        }
        
        .score-label-small {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .score-number {
            font-size: 56px;
            font-weight: 800;
            line-height: 1;
        }
        
        .score-green {
            color: #7eba01;
        }
        
        .score-yellow {
            color: #ffb900;
        }
        
        .score-red {
            color: #f35023;
        }
        
        /* Breakdown Pills */
        .breakdown-pills {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
        }
        
        .pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        
        .pill-text {
            flex: 1;
        }
        
        .pill-score {
            font-size: 13px;
            margin-left: 8px;
        }
        
        .pill-green {
            background: #7eba01;
        }
        
        .pill-yellow {
            background: #ffb900;
        }
        
        .pill-orange {
            background: #ff9500;
        }
        
        .pill-red {
            background: #f35023;
        }
        
        /* Map */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Top 10 Section */
        .top10-section {
            margin-bottom: 40px;
        }
        
        .top10-header {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .top10-content {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        
        .top10-list {
            flex: 1;
        }
        
        .top10-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .top10-item:last-child {
            border-bottom: none;
        }
        
        .top10-position {
            width: 30px;
            font-weight: 600;
            color: #666;
        }
        
        .top10-name {
            flex: 1;
            color: #333;
        }
        
        .top10-highlight {
            background: #fffacd;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .top10-counter {
            text-align: center;
            min-width: 120px;
        }
        
        .counter-big {
            font-size: 48px;
            font-weight: 800;
            color: #f35023;
            line-height: 1;
        }
        
        .counter-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Summary Text */
        .summary-box {
            background: #f9f9f9;
            border-left: 4px solid #ffb900;
            padding: 20px;
            margin-bottom: 40px;
            border-radius: 8px;
        }
        
        .summary-text {
            font-size: 14px;
            line-height: 1.8;
            color: #333;
        }
        
        .highlight-yellow {
            background: #fff9e6;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        /* Pillar Cards */
        .pillar-card {
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .pillar-icon {
            font-size: 48px;
            line-height: 1;
            flex-shrink: 0;
        }
        
        .pillar-content {
            flex: 1;
        }
        
        .pillar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .pillar-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .pillar-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .pillar-description {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-bottom: 12px;
        }
        
        .pillar-insight {
            font-size: 14px;
            line-height: 1.7;
            color: #333;
        }
        
        /* Pillar color themes */
        .pillar-green {
            background: #f0f9e8;
            border: 2px solid #7eba01;
        }
        
        .pillar-yellow {
            background: #fffbf0;
            border: 2px solid #ffb900;
        }
        
        .pillar-red {
            background: #fff5f2;
            border: 2px solid #f35023;
        }
        
        /* Expert Section */
        .expert-section {
            background: #f9f9f9;
            border-radius: 16px;
            padding: 30px;
            margin: 40px 0;
            text-align: center;
        }
        
        .expert-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }
        
        .expert-content {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 25px;
            text-align: left;
        }
        
        .expert-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .expert-text {
            flex: 1;
            font-size: 13px;
            line-height: 1.7;
            color: #333;
        }
        
        .expert-name {
            font-weight: 700;
            color: #333;
            margin-top: 8px;
        }
        
        .expert-title-small {
            font-size: 11px;
            color: #666;
        }
        
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #ffd700;
            color: #000;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .cta-button:hover {
            background: #ffed4e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }
        
        /* Footer */
        .report-footer {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .certificates {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .certificate-item {
            text-align: center;
        }
        
        .certificate-badge {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .certificate-text {
            font-size: 11px;
            color: #666;
        }
        
        .qr-section {
            margin: 30px 0;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto 10px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }
        
        .footer-text {
            font-size: 11px;
            color: #999;
            margin-top: 20px;
        }
        
        .company-info {
            font-size: 10px;
            color: #aaa;
            margin-top: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .scores-section {
                flex-direction: column;
            }
            
            .breakdown-pills {
                width: 100%;
            }
            
            .top10-content {
                flex-direction: column;
            }
            
            .expert-content {
                flex-direction: column;
                text-align: center;
            }
            
            .expert-text {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <div class="logo">
                <span class="logo-get">get</span><span class="logo-dot">.</span><span class="logo-promo">promo</span>
            </div>
            <h1 class="report-title">Raport pozycji i potencjału Twojego lokalu</h1>
        </div>

        <!-- Scores Section -->
        <div class="scores-section">
            <div class="main-scores">
                <div class="score-big">
                    <div class="score-label-small">Ocena pozycji</div>
                    <div class="score-number score-{{ $report->position_score >= 4.0 ? 'green' : ($report->position_score >= 3.0 ? 'yellow' : 'red') }}">
                        {{ number_format($report->position_score, 1) }}
                    </div>
                </div>
                <div class="score-big">
                    <div class="score-label-small">Jakość profilu</div>
                    <div class="score-number score-{{ $report->profile_quality_score >= 4.0 ? 'green' : ($report->profile_quality_score >= 3.0 ? 'yellow' : 'red') }}">
                        {{ number_format($report->profile_quality_score, 1) }}
                    </div>
                </div>
            </div>
            
            <div class="breakdown-pills">
                @foreach($publicData['pillars'] as $pillar)
                <div class="pill pill-{{ $pillar['score'] >= 4.0 ? 'green' : ($pillar['score'] >= 3.0 ? ($pillar['score'] >= 3.5 ? 'yellow' : 'orange') : 'red') }}">
                    <span class="pill-text">{{ $pillar['name'] }}</span>
                    <span class="pill-score">{{ number_format($pillar['score'], 1) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Map -->
        <div id="map"></div>

        <!-- Top 10 Section -->
        <div class="top10-section">
            <div class="top10-header">Twoja pozycja na tle innych</div>
            <div class="top10-content">
                <div class="top10-list">
                    @php
                        // Połącz lokale: dodaj swój lokal + konkurenci
                        $allPlaces = collect();
                        
                        // Dodaj konkurentów
                        foreach($report->competitors as $comp) {
                            $allPlaces->push([
                                'position' => $comp->position,
                                'name' => $comp->name,
                                'is_yours' => false
                            ]);
                        }
                        
                        // Dodaj swój lokal
                        $allPlaces->push([
                            'position' => $report->position,
                            'name' => $report->business_name,
                            'is_yours' => true
                        ]);
                        
                        // Sortuj po pozycji i weź TOP 10
                        $top10 = $allPlaces->sortBy('position')->take(10);
                    @endphp
                    @foreach($top10 as $place)
                        <div class="top10-item {{ $place['is_yours'] ? 'top10-highlight' : '' }}">
                            <span class="top10-position">{{ $place['position'] }}.</span>
                            <span class="top10-name">
                                @if($place['is_yours'])
                                    <strong>{{ $place['name'] }}</strong> (Twój lokal)
                                @else
                                    {{ $place['name'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
                
                <div class="top10-counter">
                    <div class="counter-big">{{ $report->position }}/{{ $report->total_results }}</div>
                    <div class="counter-label">Twoja pozycja</div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-text">
                <strong>Twój lokal jest {{ $report->position <= 10 ? 'w TOP 10' : 'poza TOP 10' }} wyników</strong> – z naszym wsparciem możesz osiągnąć <span class="highlight-yellow">pozycje 1-3 i przyciągać więcej klientów</span>.
            </div>
        </div>

        <!-- Chcesz poznać profil i zbudować skalę? -->
        <div class="summary-box" style="border-left-color: #4A90E2; margin-bottom: 30px;">
            <div class="summary-text">
                {{ $publicData['header']['subtitle'] }}
            </div>
        </div>

        <!-- Pillar Cards -->
        @foreach($publicData['pillars'] as $pillar)
        <div class="pillar-card pillar-{{ $pillar['score'] >= 4.0 ? 'green' : ($pillar['score'] >= 3.0 ? 'yellow' : 'red') }}">
            <div class="pillar-icon">
                @if($pillar['name'] == 'Zaufanie')
                    🛡️
                @elseif($pillar['name'] == 'Dopasowanie')
                    🎯
                @elseif($pillar['name'] == 'Aktywność')
                    ⚡
                @elseif($pillar['name'] == 'Prezentacja')
                    🎨
                @elseif($pillar['name'] == 'Spójność')
                    🔗
                @endif
            </div>
            <div class="pillar-content">
                <div class="pillar-header">
                    <span class="pillar-name">{{ $pillar['name'] }}</span>
                    <span class="pillar-badge pill-{{ $pillar['score'] >= 4.0 ? 'green' : ($pillar['score'] >= 3.0 ? 'yellow' : 'red') }}">
                        {{ number_format($pillar['score'], 1) }} {{ $pillar['status'] }}
                    </span>
                </div>
                <div class="pillar-description">{{ $pillar['description'] }}</div>
                <div class="pillar-insight">{{ $pillar['insight'] }}</div>
            </div>
        </div>
        @endforeach

        <!-- Expert Section -->
        <div class="expert-section">
            <div class="expert-title">Twój profil wymaga wzmocnienia!</div>
            <div class="expert-content">
                <img src="https://via.placeholder.com/100" alt="Ekspert" class="expert-photo">
                <div class="expert-text">
                    <strong>Skorzystaj z bezpłatnej konsultacji</strong> z naszym ekspertem Google Ads / Google My Business. 
                    Porozmawiamy o konkretnych działaniach i przygotujemy plan poprawy widoczności.
                    <div class="expert-name">Mateusz Chojnowski</div>
                    <div class="expert-title-small">Certyfikat Google Ads / Google My Business</div>
                </div>
            </div>
            <a href="tel:+48123456789" class="cta-button">
                📞 Zadzwoń teraz: +48 123 456 789
            </a>
        </div>

        <!-- Footer -->
        <div class="report-footer">
            <div class="certificates">
                <div class="certificate-item">
                    <div style="font-size: 48px; line-height: 1;">🏆</div>
                    <div class="certificate-text">Certyfikat<br>Google Partner</div>
                </div>
                <div class="certificate-item">
                    <div style="font-size: 48px; line-height: 1;">✓</div>
                    <div class="certificate-text">Undisputed AI<br>Agency Partner</div>
                </div>
            </div>
            
            <div class="qr-section">
                <div class="qr-code">📱</div>
                <div class="certificate-text">Zeskanuj i skontaktuj się</div>
            </div>
            
            <div class="footer-text">
                <strong>get.promo</strong><br>
                Raport wygenerowany automatycznie • {{ $report->generated_at->format('d.m.Y H:i') }}
            </div>
            
            <div class="company-info">
                Krown Group Ltd<br>
                NIP: 1234567890 • REGON: 123456789
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize map
        const map = L.map('map').setView([{{ $report->places_data['latitude'] ?? 52.2297 }}, {{ $report->places_data['longitude'] ?? 21.0122 }}], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Function to get marker color based on score
        function getMarkerColor(score) {
            if (score >= 4.0) return '#7eba01';
            if (score >= 3.0) return '#ffb900';
            return '#f35023';
        }

        // Add main business marker
        @if(isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
        const mainMarker = L.circleMarker([{{ $report->places_data['latitude'] }}, {{ $report->places_data['longitude'] }}], {
            radius: 12,
            fillColor: getMarkerColor({{ $report->position_score }}),
            color: '#000',
            weight: 3,
            opacity: 1,
            fillOpacity: 0.9,
            zIndexOffset: 1000
        }).addTo(map);
        
        mainMarker.bindPopup(`
            <strong>{{ $report->business_name }}</strong><br>
            Pozycja: {{ $report->position ?? 'N/A' }}<br>
            Score: {{ number_format($report->position_score, 1) }}
        `);
        @endif

        // Add competitors markers
        const competitors = @json($report->competitors);
        const bounds = [];
        
        @if(isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
        bounds.push([{{ $report->places_data['latitude'] }}, {{ $report->places_data['longitude'] }}]);
        @endif
        
        competitors.forEach(comp => {
            if (comp.latitude && comp.longitude) {
                const positionScore = comp.position_score ? parseFloat(comp.position_score) : 0;
                
                const marker = L.circleMarker([comp.latitude, comp.longitude], {
                    radius: 6,
                    fillColor: getMarkerColor(positionScore),
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.7
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong>${comp.name}</strong><br>
                    Pozycja: ${comp.position || 'N/A'}
                `);
                
                bounds.push([comp.latitude, comp.longitude]);
            }
        });
        
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    </script>
</body>
</html>
