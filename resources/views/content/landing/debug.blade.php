@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Debug - Facebook In-App Browser')

@section('page-style')
<style>
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #f5f5f5;
    padding: 20px;
  }
  
  .debug-container {
    max-width: 800px;
    margin: 0 auto;
  }
  
  .debug-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .debug-card h2 {
    margin-top: 0;
    color: #333;
    font-size: 18px;
    border-bottom: 2px solid #4285f4;
    padding-bottom: 10px;
  }
  
  .debug-info {
    margin: 10px 0;
  }
  
  .debug-label {
    font-weight: 600;
    color: #666;
    display: inline-block;
    width: 150px;
  }
  
  .debug-value {
    color: #333;
    word-break: break-all;
  }
  
  .status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
  }
  
  .status-success {
    background-color: #34a853;
  }
  
  .status-error {
    background-color: #ea4335;
  }
  
  .status-warning {
    background-color: #fbbc04;
  }
  
  .test-button {
    background-color: #4285f4;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    margin: 5px;
  }
  
  .test-button:hover {
    background-color: #357ae8;
  }
  
  .test-button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
  }
  
  .result-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 400px;
    overflow-y: auto;
  }
  
  .success-box {
    border-color: #34a853;
    background-color: #e6f4ea;
  }
  
  .error-box {
    border-color: #ea4335;
    background-color: #fce8e6;
  }
</style>
@endsection

@section('content')
<div class="debug-container">
  <div class="debug-card">
    <h2>🔍 Informacje o przeglądarce</h2>
    <div class="debug-info">
      <span class="debug-label">User Agent:</span>
      <span class="debug-value" id="userAgent"></span>
    </div>
    <div class="debug-info">
      <span class="debug-label">Platform:</span>
      <span class="debug-value" id="platform"></span>
    </div>
    <div class="debug-info">
      <span class="debug-label">Cookies włączone:</span>
      <span class="debug-value" id="cookiesEnabled"></span>
    </div>
    <div class="debug-info">
      <span class="debug-label">LocalStorage:</span>
      <span class="debug-value" id="localStorage"></span>
    </div>
    <div class="debug-info">
      <span class="debug-label">Screen Size:</span>
      <span class="debug-value" id="screenSize"></span>
    </div>
  </div>

  <div class="debug-card">
    <h2>🧪 Test połączenia</h2>
    <button class="test-button" onclick="testDebugEndpoint()">Test Endpoint</button>
    <button class="test-button" onclick="testSearchAPI()">Test Search API</button>
    <button class="test-button" onclick="clearResults()">Wyczyść wyniki</button>
    
    <div id="testResults"></div>
  </div>

  <div class="debug-card">
    <h2>📋 Logi konsoli</h2>
    <div id="consoleLogs" class="result-box"></div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  // Zbierz informacje o przeglądarce
  document.getElementById('userAgent').textContent = navigator.userAgent;
  document.getElementById('platform').textContent = navigator.platform;
  document.getElementById('cookiesEnabled').innerHTML = navigator.cookieEnabled ? 
    '<span class="status-indicator status-success"></span>TAK' : 
    '<span class="status-indicator status-error"></span>NIE';
  
  // Test LocalStorage
  let localStorageWorks = false;
  try {
    localStorage.setItem('test', 'test');
    localStorage.removeItem('test');
    localStorageWorks = true;
  } catch (e) {
    console.error('LocalStorage error:', e);
  }
  document.getElementById('localStorage').innerHTML = localStorageWorks ? 
    '<span class="status-indicator status-success"></span>Działa' : 
    '<span class="status-indicator status-error"></span>Nie działa';
  
  document.getElementById('screenSize').textContent = `${window.screen.width}x${window.screen.height}`;

  // Override console.log do wyświetlania w UI
  const consoleBox = document.getElementById('consoleLogs');
  const originalLog = console.log;
  const originalError = console.error;
  const originalWarn = console.warn;
  
  function addToConsole(type, args) {
    const timestamp = new Date().toLocaleTimeString();
    const message = Array.from(args).map(arg => 
      typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
    ).join(' ');
    
    consoleBox.textContent += `[${timestamp}] [${type}] ${message}\n`;
    consoleBox.scrollTop = consoleBox.scrollHeight;
  }
  
  console.log = function(...args) {
    addToConsole('LOG', args);
    originalLog.apply(console, args);
  };
  
  console.error = function(...args) {
    addToConsole('ERROR', args);
    originalError.apply(console, args);
  };
  
  console.warn = function(...args) {
    addToConsole('WARN', args);
    originalWarn.apply(console, args);
  };

  console.log('🚀 Debug page loaded');
  console.log('User Agent:', navigator.userAgent);

  // Test debug endpoint
  async function testDebugEndpoint() {
    const resultsDiv = document.getElementById('testResults');
    resultsDiv.innerHTML = '<div class="result-box">⏳ Testowanie endpoint...</div>';
    
    try {
      const response = await fetch('/api/public/debug-request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          test: 'debug-endpoint',
          timestamp: new Date().toISOString()
        })
      });
      
      console.log('Debug endpoint response status:', response.status);
      
      const data = await response.json();
      console.log('Debug endpoint response:', data);
      
      resultsDiv.innerHTML = `
        <div class="result-box success-box">
          ✅ SUCCESS (${response.status})
          
          ${JSON.stringify(data, null, 2)}
        </div>
      `;
    } catch (error) {
      console.error('Debug endpoint error:', error);
      resultsDiv.innerHTML = `
        <div class="result-box error-box">
          ❌ ERROR
          
          ${error.message}
          ${error.stack || ''}
        </div>
      `;
    }
  }

  // Test search API
  async function testSearchAPI() {
    const resultsDiv = document.getElementById('testResults');
    resultsDiv.innerHTML = '<div class="result-box">⏳ Testowanie wyszukiwania...</div>';
    
    try {
      const response = await fetch('/api/public/search-places', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          query: 'test cafe warszawa'
        })
      });
      
      console.log('Search API response status:', response.status);
      console.log('Search API response headers:', response.headers);
      
      const data = await response.json();
      console.log('Search API response:', data);
      
      resultsDiv.innerHTML = `
        <div class="result-box ${response.ok ? 'success-box' : 'error-box'}">
          ${response.ok ? '✅' : '❌'} ${response.ok ? 'SUCCESS' : 'ERROR'} (${response.status})
          
          ${JSON.stringify(data, null, 2)}
        </div>
      `;
    } catch (error) {
      console.error('Search API error:', error);
      resultsDiv.innerHTML = `
        <div class="result-box error-box">
          ❌ ERROR
          
          ${error.message}
          ${error.stack || ''}
        </div>
      `;
    }
  }

  function clearResults() {
    document.getElementById('testResults').innerHTML = '';
    document.getElementById('consoleLogs').textContent = '';
    console.log('🧹 Wyniki wyczyszczone');
  }
</script>
@endsection

