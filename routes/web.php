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



