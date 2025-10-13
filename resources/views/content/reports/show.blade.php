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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f4f4;
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
            background: #ffffff;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .logo {
            max-width: 396px;
            width: 100%;
            margin: 0 auto 30px;
            display: block;
        }
        
        .logo svg {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .report-title {
            font-size: 44px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
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
                <svg viewBox="0 0 897 187" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M847.052 145.618C820.066 145.618 798.23 123.782 798.23 96.7961C798.23 69.8101 820.066 47.7681 847.052 47.7681C874.038 47.7681 896.081 69.8101 896.081 96.7961C896.081 123.782 874.038 145.618 847.052 145.618ZM847.052 130.168C865.592 130.168 880.63 115.13 880.63 96.7961C880.63 78.2561 865.592 63.2181 847.052 63.2181C828.718 63.2181 813.68 78.2561 813.68 96.7961C813.68 115.13 828.718 130.168 847.052 130.168Z" fill="#7FBA00"></path>
                    <path d="M746.187 47.562C767.817 47.562 785.533 65.072 785.533 86.908V137.172C785.533 141.498 782.031 145 777.705 145C773.173 145 769.671 141.498 769.671 137.172V86.908C769.671 73.518 758.959 63.012 746.187 63.012C733.003 63.012 722.497 73.518 722.497 86.908V137.172C722.497 141.498 718.995 145 714.669 145C710.137 145 706.841 141.498 706.841 137.172V86.908C706.841 73.518 696.129 63.012 683.151 63.012C670.173 63.012 659.667 73.518 659.667 86.908V137.172C659.667 141.498 655.959 145 651.633 145C647.307 145 643.805 141.498 643.805 137.172V86.908C643.805 65.072 661.315 47.562 683.151 47.562C695.923 47.562 707.459 53.742 714.669 63.218C721.879 53.742 733.209 47.562 746.187 47.562Z" fill="#00A4EF"></path>
                    <path d="M579.896 145.618C552.91 145.618 531.074 123.782 531.074 96.7961C531.074 69.8101 552.91 47.7681 579.896 47.7681C606.882 47.7681 628.924 69.8101 628.924 96.7961C628.924 123.782 606.882 145.618 579.896 145.618ZM579.896 130.168C598.436 130.168 613.474 115.13 613.474 96.7961C613.474 78.2561 598.436 63.2181 579.896 63.2181C561.562 63.2181 546.524 78.2561 546.524 96.7961C546.524 115.13 561.562 130.168 579.896 130.168Z" fill="#FFB900"></path>
                    <path d="M447.445 145C443.325 145 439.617 141.498 439.617 137.172V89.7919C439.617 66.3079 458.775 47.1499 482.259 47.1499C500.387 47.1499 516.455 58.6859 522.429 75.9899C523.871 80.1099 521.811 84.6419 517.691 85.8779C513.571 87.3199 509.039 85.0539 507.597 80.9339C503.889 70.0159 493.795 62.8059 482.259 62.8059C467.221 62.8059 455.273 74.7539 455.273 89.7919V137.172C455.273 141.498 451.771 145 447.445 145Z" fill="#F25022"></path>
                    <path d="M376.974 47.356C404.166 47.356 426.002 69.398 426.002 96.178C426.002 123.37 404.166 145.206 376.974 145.206C363.584 145.206 351.43 139.85 342.572 130.992V178.784C342.572 183.11 339.276 186.406 335.362 186.406C331.448 186.406 328.152 183.11 328.152 178.784V97.62V97.208C328.152 96.796 328.152 96.59 328.152 96.178C328.152 69.398 350.194 47.356 376.974 47.356ZM376.974 129.756C395.514 129.756 410.346 114.718 410.346 96.178C410.346 77.844 395.514 62.806 376.974 62.806C358.64 62.806 343.602 77.844 343.602 96.178C343.602 114.718 358.64 129.756 376.974 129.756Z" fill="#00A4EF"></path>
                    <path d="M301.409 145.206C296.053 145.206 291.727 140.674 291.727 135.112C291.727 129.962 296.053 125.636 301.409 125.636C306.765 125.636 311.091 129.962 311.091 135.112C311.091 140.674 306.765 145.206 301.409 145.206Z" fill="#737373"></path>
                    <path d="M270.381 129.344C274.707 129.344 278.209 132.846 278.209 137.172C278.209 141.498 274.707 145 270.381 145H267.291C245.455 145 220.941 130.992 220.941 95.5598V8.6278C220.941 4.30181 224.443 0.799805 228.769 0.799805C233.095 0.799805 236.597 4.30181 236.597 8.6278V47.9738H265.231C269.145 47.9738 272.441 51.2698 272.441 55.1838C272.441 59.3038 269.145 62.5998 265.231 62.5998H236.597V95.5598C236.597 117.19 249.575 129.344 267.291 129.344H270.381Z" fill="#737373"></path>
                    <path d="M202.201 90.5852C201.857 90.8908 201.459 90.9974 201.061 91.1041L131.073 110.071C137.459 124.354 151.773 131.609 168.37 129.081C177.79 127.623 189.37 123.881 198.046 110.893C201.254 108.54 205.539 107.818 208.091 110.974C210.788 113.876 210.064 118.336 207.015 121.285C200.315 132.891 187.758 140.947 170.166 143.742C143.857 148.232 120.763 132.88 114.205 108.405C107.274 82.538 122.569 56.0468 148.635 49.0623C174.105 42.2377 199.732 56.6975 206.45 81.7691C207.41 85.3507 205.424 89.0819 202.201 90.5852ZM152.314 62.7919C136.396 67.0573 126.05 81.7724 128.087 98.9276L190.368 82.2395C184.564 66.9466 168.631 58.42 152.314 62.7919Z" fill="#737373"></path>
                    <path d="M98.4409 96.5901V96.7961V137.378C98.4409 162.922 79.2829 183.522 52.9149 186.2C50.6489 186.406 48.1769 186.406 45.4989 186.406C36.6409 186.406 26.9589 184.758 17.6889 178.99C13.5689 177.136 12.1269 172.604 13.5689 168.69C15.4229 164.982 19.9549 162.922 23.8689 164.776C34.1689 171.368 44.4689 171.368 51.4729 170.544C69.3949 168.896 82.7849 155.712 82.7849 137.378V132.64C74.1329 140.674 62.3909 145.412 49.6189 145.412C22.8389 145.412 0.796875 123.576 0.796875 96.5901C0.796875 69.8101 22.8389 47.9741 49.6189 47.9741C76.3989 47.9741 98.4409 69.8101 98.4409 96.5901ZM49.6189 129.962C67.9529 129.962 82.7849 115.13 82.7849 96.5901C82.7849 78.2561 67.9529 63.4241 49.6189 63.4241C31.4909 63.4241 16.4529 78.2561 16.4529 96.5901C16.4529 115.13 31.4909 129.962 49.6189 129.962Z" fill="#737373"></path>
                </svg>
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
