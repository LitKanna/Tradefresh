<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Buyer Routes - MINIMAL VERSION
|--------------------------------------------------------------------------
|
| Most buyer controllers don't exist. This file contains only working routes.
| Buyer functionality is primarily handled by Livewire components in:
| - resources/views/livewire/buyer/dashboard.blade.php
| - app/Livewire/Buyer/Dashboard.php
|
| Buyer controllers need to be created before expanding these routes.
|
*/

Route::prefix('buyer')->name('buyer.')->middleware(['auth:buyer'])->group(function () {

    // Dashboard - Working (uses Livewire)
    Route::get('/dashboard', function() {
        return view('buyer.dashboard');
    })->name('dashboard');

    // Document Signatures - Working (controller exists)
    Route::prefix('signatures')->name('signatures.')->group(function () {
        Route::get('/sign/{signature}/{token}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'sign'])->name('sign');
        Route::post('/sign/{signature}/{token}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'storeSignature'])->name('sign.store');
        Route::post('/sign/{signature}/{token}/decline', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'decline'])->name('sign.decline');
    });

    // TODO: Add more buyer routes when controllers are created
    // - ProductController
    // - OrderController
    // - ProfileController
    // - CartController
    // - QuoteController
    // - RFQController
    // - BillingController
});
