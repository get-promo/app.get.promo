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
    
    <!-- MapLibre GL CSS -->
    <link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet" />
    
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
            overflow-x: hidden;
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
            width: 250px;
            margin: 0 auto 30px;
            display: block;
        }
        
        .logo svg {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .report-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }
        
        /* Score Section */
        .scores-container {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .score-column-left {
            flex: 0 0 25%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 10;
        }
        
        .score-column-right {
            flex: 0 0 calc(75% - 15px);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            display: flex;
            gap: 15px;
            position: relative;
            z-index: 10;
        }
        
        .score-label {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
            text-align: center;
        }
        
        .score-value {
            font-size: 68px;
            font-weight: 900;
            color: #333;
        }
        
        .quality-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .pillars-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }
        
        .pillar-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            font-size: 14px;
        }
        
        .pillar-metric-name {
            color: #333;
            font-weight: 500;
            font-size: 14px;
            text-align: right;
        }
        
        .pillar-value {
            font-weight: 700;
            color: #333;
        }
        
        .pillar-progress {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .progress-bar-container {
            width: 170px;
            height: 14px;
            background: #f0f0f0;
            border-radius: 7px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar-fill {
            height: 100%;
            transition: width 0.6s ease;
            border-radius: 10px;
        }
        
        .pillar-metric-score {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            font-weight: 700;
            pointer-events: none;
        }
        
        .progress-bar-fill.green {
            background: #7fba00;
        }
        
        .progress-bar-fill.yellow {
            background: #ff8900;
        }
        
        .progress-bar-fill.red {
            background: #bd3544;
        }
        
        /* Map */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 40px;
            margin-top: -80px;
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
            color: #bd3544;
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
            border-left: 4px solid #ff8900;
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
        
        .pillar-content { flex: 1; }
        .pillar-header { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
        .pillar-title-block { display: flex; flex-direction: column; gap: 4px; }
        
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
            border: 2px solid #7fba00;
        }
        
        .pillar-yellow {
            background: #fffbf0;
            border: 2px solid #ff8900;
        }
        
        .pillar-red {
            background: #fff5f2;
            border: 2px solid #bd3544;
        }
        
        /* Expert Section */
        .expert-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .expert-main {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            align-items: flex-start;
        }
        
        .expert-photo-section {
            flex: 0 0 50%;
        }
        
        .expert-photo {
            width: 100%;
            height: auto;
            aspect-ratio: 4 / 5;
            border-radius: 12px;
            object-fit: cover;
            background: #f5f5f5;
        }
        
        .expert-text-section {
            flex: 0 0 50%;
        }
        
        .expert-description {
            font-size: 16px;
            line-height: 1.5;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* Match ranking-button style */
        .expert-phone-button {
            background-color: #FFD400;
            color: #333;
            font-weight: 700;
            padding: 14px 22px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .expert-phone-button:hover { background-color: #ffed4e; }
        
        .expert-details {
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
        }
        
        .expert-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .expert-position {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .expert-certification {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .expert-bio {
            font-size: 15px;
            line-height: 1.5;
            color: #333;
            margin: 0;
        }
        
        /* Expert badges under the card */
        .expert-badges {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            margin: 12px 0 32px;
        }
        .expert-badges img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Brand Footer */
        .brand-footer {
            background: #f3f5f6;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 24px;
        }
        .brand-left {
            display: flex;
            align-items: flex-start;
            flex: 1;
        }

        .brand-right { 
            text-align: right; 
            flex: 1; 
            color: #111;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        .brand-logo { width: 160px; min-width: 140px; margin-left: auto; }
        .brand-logo svg { width: 100%; height: auto; display: block; }
        .brand-left-text { color: #333; }
        .brand-left-text .line-1 { font-size: 16px; }
        .brand-left-text .line-2 { font-size: 20px; font-weight: 800; }
        .brand-right .company-addr { font-size: 12px; line-height: 1.4; text-align: right; }
        .brand-right .company-legal { font-size: 12px; line-height: 1.6; text-align: right; }

        @media (max-width: 768px) {
            .brand-footer { flex-direction: column; text-align: center; align-items: center; gap: 30px; }
            .brand-right { text-align: center; align-items: center; }
            .brand-left { justify-content: center; }
            .brand-left img { width: 160px !important; }
            .brand-logo { margin-left: 0; }
            .brand-left-text .line-2 { font-size: 20px; }
            .brand-right .company-addr { font-size: 11px; text-align: center; }
            .brand-right .company-legal { font-size: 11px; text-align: center; }
        }
        
        /* Ranking Section */
        .ranking-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            padding: 40px 50px;
            margin-bottom: 40px;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }
        
        .ranking-left {
            flex: 0 0 300px;
        }
        
        .ranking-right {
            flex: 1;
        }
        
        .ranking-header {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }
        
        .position-display {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .position-number {
            font-size: 80px;
            font-weight: 800;
            color: #E63946;
        }
        
        .position-total {
            font-size: 48px;
            font-weight: 700;
            color: #aaa;
        }
        
        .ranking-list {
            margin-bottom: 30px;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            gap: 10px;
        }
        
        .rank-number {
            background-color: #52b84f;
            color: white;
            font-weight: 700;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .rank-name {
            flex: 1;
            color: #333;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .rank-score {
            font-weight: 700;
            color: #333;
            flex-shrink: 0;
            min-width: 35px;
            text-align: right;
        }
        
        .your-rank {
            background-color: #f08a8a;
            border-radius: 50%;
            color: white;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .your-item .rank-name {
            color: #333;
            font-weight: 700;
        }
        
        .your-item-in-top10 {
            background: rgba(122, 193, 67, 0.1);
            border-radius: 12px;
            padding: 15px 20px;
            border: 2px solid rgba(122, 193, 67, 0.5);
        }
        
        .your-item-in-top10 .rank-name {
            color: #333;
            font-weight: 700;
        }
        
        .rank-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 270px;
            padding: 4px 8px;
            border-radius: 6px;
        }
        
        /* Kolory background tylko dla naszego lokal w zależności od oceny pozycji */
        .your-item .rank-name-score-good {
            background-color: rgba(127, 186, 0, 0.5);
        }
        
        .your-item .rank-name-score-warn {
            background-color: rgba(255, 137, 0, 0.5);
        }
        
        .your-item .rank-name-score-bad {
            background-color: rgba(189, 53, 68, 0.5);
        }
        
        .your-item-in-top10 .rank-name-score-good {
            background-color: rgba(127, 186, 0, 0.5);
        }
        
        .your-item-in-top10 .rank-name-score-warn {
            background-color: rgba(255, 137, 0, 0.5);
        }
        
        .your-item-in-top10 .rank-name-score-bad {
            background-color: rgba(189, 53, 68, 0.5);
        }
        
        .ranking-separator {
            text-align: center;
            font-size: 24px;
            color: #666;
            margin: 10px 0;
        }
        
        .ranking-footer {
            font-size: 17px;
            color: #000;
            line-height: 1.5;
            margin-bottom: 25px;
        }
        
        .ranking-footer strong {
            font-weight: 700;
        }
        
        .ranking-button {
            background-color: #FFD400;
            color: #333;
            font-weight: 700;
            padding: 14px 22px;
            border-radius: 25px;
            text-align: center;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .ranking-button:hover {
            background-color: #ffed4e;
        }
        
        /* Pillar Boxes */
        .pillar-box {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            padding: 40px 50px;
            margin-bottom: 40px;
            display: flex;
            gap: 25px;
            align-items: flex-start;
        }
        
        .pillar-box-score {
            width: 88px;
            height: 88px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 26px;
            color: #fff;
            flex-shrink: 0;
        }
        
        .pillar-content {
            flex: 1;
        }
        
        .pillar-title-line {
            display: flex;
            align-items: baseline;
            gap: 16px;
            margin-bottom: 6px;
        }
        
        .pillar-title {
            font-size: 24px;
            font-weight: 800;
            color: #333;
        }
        
        .pillar-status {
            font-size: 16px;
            font-weight: 800;
        }
        
        .pillar-subtitle {
            font-size: 14px;
            color: #333;
            line-height: 1.45;
            margin-bottom: 16px;
        }
        
        .pillar-description {
            font-size: 18px;
            line-height: 1.5;
            color: #333;
        }
        
        /* Pillar color variants */
        .pillar-good .pillar-box-score { background: #7fba00; }
        .pillar-good .pillar-status { color: #7fba00; }
        .pillar-warn .pillar-box-score { background: #ff8900; }
        .pillar-warn .pillar-status { color: #ff8900; }
        .pillar-bad .pillar-box-score { background: #bd3544; }
        .pillar-bad .pillar-status { color: #bd3544; }
        
        /* Optimization Bar */
        .optimization-bar {
            color: white;
            font-weight: 700;
            font-size: 28px;
            text-align: center;
            border-radius: 25px;
            padding: 30px;
            margin-top: 40px;
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
        @media (max-width: 992px) {
            .score-value { font-size: 56px; }
            .position-number { font-size: 68px; }
            .position-total { font-size: 42px; }
            .ranking-left { flex: 0 0 260px; }
            .rank-name { max-width: 240px; }
        }

        @media (max-width: 768px) {
            .scores-container { flex-direction: column; gap: 16px; }
            .score-column-left, .score-column-right { flex: 1; }
            .score-value { font-size: 52px; }
            .score-label { font-size: 18px; }

            .progress-bar-container { width: 150px; }
            .pillars-section { gap: 8px; }

            .ranking-card { flex-direction: column; gap: 16px; }
            .ranking-left, .ranking-right { flex: 0 0 100%; }
            .position-number { font-size: 60px; }
            .position-total { font-size: 38px; }
            .rank-name { max-width: 220px; }
            .ranking-item { gap: 10px; }

            #map { height: 360px; }

            .expert-main { flex-direction: column; }
            .expert-photo-section, .expert-text-section { flex: 0 0 100%; }
            .expert-description { font-size: 15px; }
        }

        @media (max-width: 576px) {
            .score-value { font-size: 48px; }
            .position-number { font-size: 52px; }
            .position-total { font-size: 32px; }
            .rank-name { max-width: 200px; }
            .ranking-item { gap: 8px; }
            .rank-score { min-width: 32px; }
            .rank-number, .your-rank { width: 22px; height: 22px; font-size: 11px; }
            #map { height: 340px; }
 
             /* Pillar cards - mobile layout */
             .pillar-box { padding: 20px; gap: 12px; align-items: flex-start; }
             .pillar-box-score { width: 64px; height: 64px; font-size: 20px; border-radius: 10px; }
             .pillar-header { gap: 10px; margin-bottom: 8px; }
             .pillar-title-block { width: 100%; }
             .pillar-title { font-size: 18px; }
             .pillar-status { font-size: 14px; }
             .pillar-subtitle { font-size: 13px; margin-bottom: 10px; }
             .pillar-description { font-size: 14px; }
         }
 
         @media (max-width: 430px) {
             .score-value { font-size: 44px; }
             .score-label { font-size: 16px; }
             .progress-bar-container { width: 140px; }
             .pillar-metric-name { font-size: 13px; }
             .rank-name { max-width: 180px; }
             .ranking-card { padding: 24px; }
 
             /* Pillar cards - very small screens */
             .pillar-box { padding: 16px; gap: 10px; }
             .pillar-box-score { width: 56px; height: 56px; font-size: 18px; }
             .pillar-title { font-size: 17px; }
             .pillar-status { font-size: 13px; }
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
        <div class="scores-container">
            <div class="score-column-left">
                <div class="score-label">Ocena pozycji</div>
                @php
                    $positionColor = $report->position_score >= 4.0 ? '#7fba00' : ($report->position_score >= 3.0 ? '#ff8900' : '#bd3544');
                @endphp
                <div class="score-value" style="color: {{ $positionColor }};">{{ number_format($report->position_score, 1) }}</div>
            </div>
            
            <div class="score-column-right">
                <div class="quality-section">
                    <div class="score-label">Jakość profilu</div>
                    @php
                        $qualityColor = $report->profile_quality_score >= 4.0 ? '#7fba00' : ($report->profile_quality_score >= 3.0 ? '#ff8900' : '#bd3544');
                    @endphp
                    <div class="score-value" style="color: {{ $qualityColor }};">{{ number_format($report->profile_quality_score, 1) }}</div>
                </div>
                
                <div class="pillars-section">
                    @foreach($publicData['pillars'] as $pillar)
                    <div class="pillar-row">
                        <span class="pillar-metric-name">{{ $pillar['name'] }}</span>
                        <div class="progress-bar-container">
                            @php
                                $percentage = ($pillar['score'] / 5.0) * 100;
                                $colorClass = $pillar['score'] >= 4.0 ? 'green' : ($pillar['score'] >= 3.0 ? 'yellow' : 'red');
                                $textColor = $pillar['score'] < 3.0 ? '#333' : '#fff';
                            @endphp
                            <div class="progress-bar-fill {{ $colorClass }}" style="width: {{ $percentage }}%;"></div>
                            <span class="pillar-metric-score" style="color: {{ $textColor }};">{{ number_format($pillar['score'], 1) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Map -->
        <div id="map"></div>

        <!-- Ranking Section -->
        <div class="ranking-card">
            <div class="ranking-left">
                <div class="ranking-header">Twoja pozycja<br>na tle konkurencji:</div>
                <div class="position-display">
                    @php
                        $positionDisplayColor = $report->position_score >= 4.0 ? '#7fba00' : ($report->position_score >= 3.0 ? '#ff8900' : '#bd3544');
                    @endphp
                    <span class="position-number" style="color: {{ $positionDisplayColor }};">{{ $report->position ?? 'N/A' }}</span>
                    <span class="position-total">/{{ $report->total_results ?? 'N/A' }}</span>
                </div>
                
                <div class="ranking-footer">
                    @php
                        // Komunikat A - w rankingu
                        if ($report->position <= 3) {
                            $rankingMessage = 'Twój lokal jest w TOP 3!<br>To świetny wynik — Twoja widoczność jest znakomita.<br>Z kampanią Google Ads możesz utrwalić tę pozycję i zdobyć jeszcze więcej klientów.';
                        } elseif ($report->position <= 10) {
                            $rankingMessage = 'Twój lokal jest w TOP 10 — jesteś o krok od ścisłej czołówki.<br>Z naszym wsparciem możesz awansować do TOP 3 i przyciągnąć jeszcze więcej klientów.';
                        } else {
                            $rankingMessage = 'Twój lokal znajduje się poza TOP 10 wyników.<br>Z pomocą naszych ekspertów możesz poprawić widoczność i dotrzeć do większej liczby klientów w Google.';
                        }
                    @endphp
                    {!! $rankingMessage !!}<br><br>
                    Chcesz poprawić profil i zdobyć wyższą pozycję w rankingu?
                </div>
                <a href="tel:+48123456789" class="ranking-button">Dowiedz się więcej</a>
            </div>
            
            <div class="ranking-right">
                <div class="ranking-list">
                    @php
                        // Połącz lokale: dodaj konkurentów + swój lokal
                        $allPlaces = collect();
                        
                        // Dodaj konkurentów z score
                        foreach($report->competitors as $comp) {
                            $allPlaces->push([
                                'position' => $comp->position,
                                'name' => $comp->name,
                                'score' => $comp->position_score ?? 0,
                                'is_yours' => false
                            ]);
                        }
                        
                        // Dodaj swój lokal
                        $yourPlace = [
                            'position' => $report->position,
                            'name' => $report->business_name,
                            'score' => $report->position_score ?? 0,
                            'is_yours' => true
                        ];
                        $allPlaces->push($yourPlace);
                        
                        // Sortuj po pozycji
                        $sortedPlaces = $allPlaces->sortBy('position');
                        
                        // Sprawdź czy nasz lokal jest w TOP 10
                        $isInTop10 = $report->position <= 10;
                        
                        // Jeśli jest w TOP 10, pokaż TOP 10 z zaznaczonym lokalem
                        if ($isInTop10) {
                            $top10 = $sortedPlaces->take(10);
                        } else {
                            // Jeśli nie, pokaż TOP 10 bez naszego lokal + separator + nasz lokal
                            $top10 = $sortedPlaces->where('is_yours', false)->take(10);
                        }
                    @endphp
                    
                    @foreach($top10 as $place)
                        @php
                            $scoreClass = $place['is_yours'] ? 
                                ($place['score'] >= 4.0 ? 'rank-name-score-good' : ($place['score'] >= 3.0 ? 'rank-name-score-warn' : 'rank-name-score-bad')) : 
                                '';
                        @endphp
                        <div class="ranking-item {{ $place['is_yours'] ? ($isInTop10 ? 'your-item-in-top10' : 'your-item') : '' }}">
                            <span class="rank-score">{{ number_format($place['score'], 1) }}</span>
                            @if($place['is_yours'])
                                @php
                                    $rankColor = $place['score'] >= 4.0 ? '#7fba00' : ($place['score'] >= 3.0 ? '#ff8900' : '#bd3544');
                                @endphp
                                <span class="your-rank" style="background-color: {{ $rankColor }};">{{ $place['position'] }}</span>
                            @else
                                <span class="rank-number">{{ $place['position'] }}</span>
                            @endif
                            <span class="rank-name {{ $scoreClass }}">
                                @if($place['is_yours'])
                                    {{ $report->business_name }}
                                @else
                                    {{ $place['name'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                    
                    @if(!$isInTop10)
                        @php
                            $yourScoreClass = $yourPlace['score'] >= 4.0 ? 'rank-name-score-good' : ($yourPlace['score'] >= 3.0 ? 'rank-name-score-warn' : 'rank-name-score-bad');
                        @endphp
                        <div class="ranking-separator">...</div>
                        <div class="ranking-item your-item">
                            <span class="rank-score">{{ number_format($yourPlace['score'], 1) }}</span>
                            @php
                                $yourRankColor = $yourPlace['score'] >= 4.0 ? '#7fba00' : ($yourPlace['score'] >= 3.0 ? '#ff8900' : '#bd3544');
                            @endphp
                            <span class="your-rank" style="background-color: {{ $yourRankColor }};">{{ $yourPlace['position'] }}</span>
                            <span class="rank-name {{ $yourScoreClass }}">{{ $report->business_name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>


        <!-- Chcesz poznać profil i zbudować skalę? -->
        <div class="summary-box" style="border-left-color: #4A90E2; margin-bottom: 30px;">
            <div class="summary-text">
                {{ $publicData['header']['subtitle'] }}
            </div>
        </div>

        <!-- Pillar Boxes -->
        @foreach($publicData['pillars'] as $pillar)
        @php
            $pillarClass = $pillar['score'] >= 4.0 ? 'pillar-good' : ($pillar['score'] >= 3.0 ? 'pillar-warn' : 'pillar-bad');
        @endphp
        <div class="pillar-box {{ $pillarClass }}">
            <div class="pillar-box-score">{{ number_format($pillar['score'], 1) }}</div>
            <div class="pillar-content">
                <div class="pillar-header">
                    <div class="pillar-title-block">
                        <div class="pillar-title">{{ $pillar['name'] }}</div>
                        <div class="pillar-status">{{ $pillar['status'] }}</div>
                    </div>
                </div>
                <div class="pillar-subtitle">{{ $pillar['description'] }}</div>
                <div class="pillar-description">{{ $pillar['insight'] }}</div>
            </div>
        </div>
        @endforeach

        <!-- Optimization Bar -->
        @php
            // Komunikat B - pod Spójnością nad Mateuszem
            if ($report->profile_quality_score < 4.0) {
                $optimizationMessage = 'Twój profil wymaga optymalizacji';
                $optimizationColor = '#bd3544'; // czerwony
            } elseif ($report->profile_quality_score >= 4.0 && $report->position > 10) {
                $optimizationMessage = 'Twój profil jest solidny, ale jego pozycja mogłaby być znacznie wyższa.';
                $optimizationColor = '#ff8900'; // pomarańczowy
            } else {
                $optimizationMessage = 'Twój profil prezentuje się świetnie — wzmocnij go dodatkowo reklamą Google Ads, by przyciągnąć jeszcze więcej klientów.';
                $optimizationColor = '#7fba00'; // zielony
            }
        @endphp
        <div class="optimization-bar" style="background: {{ $optimizationColor }};">{{ $optimizationMessage }}</div>

        <!-- Expert Section -->
        <div class="expert-card">
            <div class="expert-main">
                <div class="expert-photo-section">
                    <img src="/assets/img/mateusz-chojnowski.jpg" alt="Mateusz Chojnowski" class="expert-photo">
                </div>
                <div class="expert-text-section">
                    <p class="expert-description">
                        Skorzystaj z <strong style="color: #7fba00;">bezpłatnej konsultacji</strong> z naszym certyfikowanym ekspertem Google Ads / Google AI, który pokaże Ci, jak poprawić pozycję i przyciągnąć więcej klientów.
                    </p>
                    <a href="tel:+48788733337" class="expert-phone-button">
                        📞 788 733 337
                    </a>
                </div>
            </div>
            <div class="expert-details">
                <div class="expert-name">Mateusz Chojnowski</div>
                <div class="expert-position">Członek Zarządu</div>
                <div class="expert-certification">Certyfikowany Ekspert Google Ads / Google AI</div>
                <p class="expert-bio">
                    Od blisko 10 lat pomaga firmom zwiększać widoczność w internecie i skutecznie pozyskiwać klientów dzięki kampaniom Google Ads. Współpracował z setkami lokalnych biznesów, optymalizując ich wyniki krok po kroku.
                </p>
            </div>
        </div>

        <!-- Expert badges (outside rounded card) -->
        <div class="expert-badges">
            <img src="/assets/img/mateusz-google.png" alt="Google Certified">
            <img src="/assets/img/mateusz-google-ai.jpg" alt="Google AI Certified">
        </div>

        <!-- Brand Footer block -->
        <div class="brand-footer">
            <div class="brand-left">
                <img src="/assets/img/powered-by-ai.png" alt="Powered by AI" style="width: 200px;">
            </div>
            <div class="brand-right">
                <div class="brand-logo">
                    <!-- get.promo logo SVG, width 160px -->
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
                <div class="brand-left-text">
                    <div class="line-1">is a proud member of</div>
                    <div class="line-2">Keen Group Ltd</div>
                </div>
                <div class="company-addr">Al. Jerozolimskie 89/43<br>02-001 Warszawa</div>
                <div class="company-legal">
                    NIP: 7011202113<br>
                    KRS: 0001101019<br>
                    REGON: 528453939
                </div>
            </div>
        </div>
        

        <!-- Footer removed per request -->
    </div>

    <!-- MapLibre GL JS -->
    <script src="https://unpkg.com/maplibre-gl/dist/maplibre-gl.js"></script>
    
    <script>
        // Function to create custom marker element
        function createCustomMarker(position, color, size = 30) {
            const el = document.createElement('div');
            el.className = 'custom-marker';
            el.style.backgroundColor = color;
            el.style.width = size + 'px';
            el.style.height = size + 'px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid #fff';
            el.style.display = 'flex';
            el.style.alignItems = 'center';
            el.style.justifyContent = 'center';
            el.style.fontWeight = 'bold';
            el.style.fontSize = size > 30 ? '16px' : '12px';
            el.style.color = '#fff';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.textContent = `#${position}`;
            return el;
        }

        // Function to get marker color based on score
        function getMarkerColor(score) {
            if (score >= 4.0) return '#7fba00';
            if (score >= 3.0) return '#ff8900';
            return '#bd3544';
        }

        // Get main business coordinates (our lead)
        let leadCoordinates = [21.0122, 52.2297]; // Default to Warsaw
        
        @if(isset($report->places_data) && isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
        leadCoordinates = [{{ $report->places_data['longitude'] }}, {{ $report->places_data['latitude'] }}];
        @elseif(isset($report->lead) && $report->lead->latitude && $report->lead->longitude)
        leadCoordinates = [{{ $report->lead->longitude }}, {{ $report->lead->latitude }}];
        @endif
        
        // Get competitors data
        const competitors = @json($report->competitors);

        // Function to calculate distance between two coordinates (in kilometers)
        function calculateDistance(coord1, coord2) {
            const R = 6371; // Earth's radius in kilometers
            const dLat = (coord2[1] - coord1[1]) * Math.PI / 180;
            const dLon = (coord2[0] - coord1[0]) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(coord1[1] * Math.PI / 180) * Math.cos(coord2[1] * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Calculate optimal zoom based on furthest competitor
        let optimalZoom = 13; // Default zoom
        let maxDistance = 0;
        
        competitors.forEach(comp => {
            if (comp.latitude && comp.longitude) {
                const distance = calculateDistance(leadCoordinates, [comp.longitude, comp.latitude]);
                if (distance > maxDistance) {
                    maxDistance = distance;
                }
            }
        });
        
        // Adjust zoom based on maximum distance
        if (maxDistance > 0) {
            if (maxDistance > 50) {
                optimalZoom = 9; // Very wide view for distant competitors
            } else if (maxDistance > 20) {
                optimalZoom = 10; // Wide view
            } else if (maxDistance > 10) {
                optimalZoom = 11; // Medium-wide view
            } else if (maxDistance > 5) {
                optimalZoom = 12; // Medium view
            } else if (maxDistance > 2) {
                optimalZoom = 13; // Close view
            } else {
                optimalZoom = 14; // Very close view
            }
        }

        // Initialize map centered on our lead
        const map = new maplibregl.Map({
            style: 'https://tiles.openfreemap.org/styles/liberty',
            center: leadCoordinates,
            zoom: optimalZoom,
            container: 'map'
        });

        // Sort competitors by position (worst to best, excluding our business)
        const sortedCompetitors = competitors
            .filter(comp => comp.latitude && comp.longitude)
            .sort((a, b) => {
                const posA = parseInt(a.position) || 999;
                const posB = parseInt(b.position) || 999;
                return posB - posA; // Sort from worst (highest number) to best (lowest number)
            });

        // Check if animate parameter is set
        const urlParams = new URLSearchParams(window.location.search);
        const shouldAnimate = urlParams.get('animate') === '1';
        
        // Add competitors markers first (from worst to best position)
        if (shouldAnimate) {
            // Animowana wersja - markery pojawiają się jeden po drugim
            sortedCompetitors.forEach((comp, index) => {
                setTimeout(() => {
                    const positionScore = comp.position_score ? parseFloat(comp.position_score) : 0;
                    
                    const markerEl = createCustomMarker(comp.position || 'N/A', getMarkerColor(positionScore));
                    const marker = new maplibregl.Marker({ element: markerEl })
                    .setLngLat([comp.longitude, comp.latitude])
                    .setPopup(new maplibregl.Popup().setHTML(`
                        <strong>${comp.name}</strong><br>
                        Pozycja: ${comp.position || 'N/A'}
                    `))
                    .addTo(map);
                }, index * 300); // 300ms przerwy między markerami
            });
        } else {
            // Normalna wersja - wszystkie markery od razu
            sortedCompetitors.forEach(comp => {
                const positionScore = comp.position_score ? parseFloat(comp.position_score) : 0;
                
                const markerEl = createCustomMarker(comp.position || 'N/A', getMarkerColor(positionScore));
                const marker = new maplibregl.Marker({ element: markerEl })
                .setLngLat([comp.longitude, comp.latitude])
                .setPopup(new maplibregl.Popup().setHTML(`
                    <strong>${comp.name}</strong><br>
                    Pozycja: ${comp.position || 'N/A'}
                `))
                .addTo(map);
            });
        }

        // Add main business marker last (so it appears on top) - 2x bigger
        const addMainMarker = () => {
            @if(isset($report->places_data) && isset($report->places_data['latitude']) && isset($report->places_data['longitude']))
            const mainMarkerEl = createCustomMarker({{ $report->position ?? 'N/A' }}, getMarkerColor({{ $report->position_score }}), 60);
            const mainMarker = new maplibregl.Marker({ element: mainMarkerEl })
            .setLngLat([{{ $report->places_data['longitude'] }}, {{ $report->places_data['latitude'] }}])
            .setPopup(new maplibregl.Popup().setHTML(`
                <strong>{{ $report->business_name }}</strong><br>
                Pozycja: {{ $report->position ?? 'N/A' }}<br>
                Score: {{ number_format($report->position_score, 1) }}
            `))
            .addTo(map);
            @elseif(isset($report->lead) && $report->lead->latitude && $report->lead->longitude)
            const mainMarkerEl = createCustomMarker({{ $report->position ?? 'N/A' }}, getMarkerColor({{ $report->position_score }}), 60);
            const mainMarker = new maplibregl.Marker({ element: mainMarkerEl })
            .setLngLat([{{ $report->lead->longitude }}, {{ $report->lead->latitude }}])
            .setPopup(new maplibregl.Popup().setHTML(`
                <strong>{{ $report->business_name }}</strong><br>
                Pozycja: {{ $report->position ?? 'N/A' }}<br>
                Score: {{ number_format($report->position_score, 1) }}
            `))
            .addTo(map);
            @endif
        };
        
        // Dodaj główny marker z opóźnieniem jeśli animacja włączona
        if (shouldAnimate) {
            setTimeout(addMainMarker, sortedCompetitors.length * 300);
        } else {
            addMainMarker();
        }
    </script>
</body>
</html>
