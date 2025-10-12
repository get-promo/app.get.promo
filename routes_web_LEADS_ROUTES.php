<?php

// DODAJ TE ROUTE'Y DO routes/web.php

use App\Http\Controllers\LeadController;

// Leads routes
Route::middleware(['web'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    
    // AJAX endpoint for Serper search
    Route::post('/api/leads/search-places', [LeadController::class, 'searchPlaces'])->name('leads.search-places');
});

