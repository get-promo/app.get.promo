<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\Page2;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HttpSmsController;

// Authentication routes (bez middleware auth)
Route::get('/login', [LoginBasic::class, 'index'])->name('login');
Route::post('/login', [LoginBasic::class, 'login']);
Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');

// Stare routes do zgodności wstecznej
Route::get('/auth/login-basic', function() {
    return redirect('/login');
});
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// Publiczne routes
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

// Publiczny raport (bez auth, z noindex)
Route::get('/reports/{key}', [ReportController::class, 'show'])->name('reports.show');

// Chronione routes (wymagają logowania)
Route::middleware(['auth'])->group(function () {
    // Main Page Route
    Route::get('/', [HomePage::class, 'index'])->name('pages-home');
    Route::get('/page-2', [Page2::class, 'index'])->name('pages-page-2');
    
    // Leads routes
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    
    // AJAX endpoint for Serper search
    Route::post('/api/leads/search-places', [LeadController::class, 'searchPlaces'])->name('leads.search-places');
    
    // Raporty
    Route::post('/leads/{lead}/generate-report', [ReportController::class, 'generate'])->name('leads.generate-report');
    Route::get('/api/reports/status/{jobId}', [ReportController::class, 'checkStatus'])->name('reports.check-status');
});

// httpSMS API Routes - WSZYSTKIE requesty są logowane
Route::prefix('httpSMS')->middleware(\App\Http\Middleware\LogHttpSmsRequests::class)->group(function () {
    // Test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'httpSMS API Server is running!',
            'endpoints' => [
                'POST /httpSMS/v1/devices/register' => 'Rejestracja urządzenia Android',
                'POST /httpSMS/v1/messages/send' => 'Wysyłanie SMS',
                'GET /httpSMS/v1/messages' => 'Lista wiadomości',
            ],
            'server_url' => url('/httpSMS/v1'),
            'timestamp' => now()->toISOString(),
        ]);
    });
    
    Route::prefix('v1')->group(function () {
        // Rejestracja i logowanie urządzenia (bez autentykacji)
        Route::post('/devices/register', [HttpSmsController::class, 'registerDevice']);
        Route::post('/users/login', [HttpSmsController::class, 'login']);
        
        // Wszystkie pozostałe endpointy wymagają autentykacji API key
        Route::middleware([])->group(function () {
            // Użytkownik i telefony (kompatybilność z httpSMS)
            Route::get('/users/me', [HttpSmsController::class, 'me']);
            Route::get('/phones', [HttpSmsController::class, 'phones']);
            Route::post('/phones', [HttpSmsController::class, 'registerPhone']);
            Route::put('/phones/fcm-token', [HttpSmsController::class, 'updateFcmToken']);
            
            // Wiadomości SMS
            Route::post('/messages/send', [HttpSmsController::class, 'sendMessage']);
            Route::get('/messages', [HttpSmsController::class, 'getMessages']);
            Route::get('/messages/{messageId}', [HttpSmsController::class, 'getMessage']);
            Route::post('/messages/receive', [HttpSmsController::class, 'receiveMessage']);
            Route::post('/messages/{messageId}/status', [HttpSmsController::class, 'updateMessageStatus']);
            
            // Urządzenia
            Route::get('/devices/status', [HttpSmsController::class, 'getDeviceStatus']);
            Route::post('/devices/heartbeat', [HttpSmsController::class, 'heartbeat']);
        });
    });
});

// Stary test endpoint (usunięty - teraz w grupie httpSMS)
Route::get('/httpSMS-old-test', function () {
    return response()->json([
        'message' => 'httpSMS API Server is running!',
        'endpoints' => [
            'POST /httpSMS/v1/devices/register' => 'Rejestracja urządzenia Android',
            'POST /httpSMS/v1/messages/send' => 'Wysyłanie SMS',
            'GET /httpSMS/v1/messages' => 'Lista wiadomości',
            'GET /httpSMS/v1/messages/{id}' => 'Szczegóły wiadomości',
            'POST /httpSMS/v1/messages/receive' => 'Odbieranie SMS',
            'POST /httpSMS/v1/messages/{id}/status' => 'Aktualizacja statusu',
            'GET /httpSMS/v1/devices/status' => 'Status urządzenia',
            'POST /httpSMS/v1/devices/heartbeat' => 'Heartbeat',
        ],
        'server_url' => url('/httpSMS/v1'),
        'timestamp' => now()->toISOString(),
    ]);
});



