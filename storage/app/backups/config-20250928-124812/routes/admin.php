<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Livewire\Admin; // Temporarily disabled

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for admin panel and admin-specific functionality
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth:admin'])->group(function () {
    
    // Dashboard - Temporarily disabled Livewire
    // Route::get('/dashboard', Admin\Dashboard::class)->name('dashboard');
    
    // Broadcast Notifications Management
    Route::prefix('broadcasts')->name('broadcasts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\BroadcastController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\BroadcastController::class, 'store'])->name('store');
        Route::get('/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'show'])->name('show');
        Route::get('/{broadcast}/edit', [\App\Http\Controllers\Admin\BroadcastController::class, 'edit'])->name('edit');
        Route::put('/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'update'])->name('update');
        Route::post('/{broadcast}/send', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])->name('send');
        Route::post('/{broadcast}/cancel', [\App\Http\Controllers\Admin\BroadcastController::class, 'cancel'])->name('cancel');
        Route::delete('/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'destroy'])->name('destroy');
        Route::post('/preview-recipients', [\App\Http\Controllers\Admin\BroadcastController::class, 'previewRecipients'])->name('preview-recipients');
        Route::get('/{broadcast}/statistics', [\App\Http\Controllers\Admin\BroadcastController::class, 'statistics'])->name('statistics');
    });
    
    /* TEMPORARILY COMMENTED OUT - These Livewire components need to be created
    
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [Admin\Users\Index::class, 'render'])->name('index');
        Route::get('/buyers', [Admin\Users\Buyers::class, 'render'])->name('buyers');
        Route::get('/vendors', [Admin\Users\Vendors::class, 'render'])->name('vendors');
        Route::get('/admins', [Admin\Users\Admins::class, 'render'])->name('admins');
        Route::get('/{user}/edit', [Admin\Users\Edit::class, 'render'])->name('edit');
        Route::get('/create', [Admin\Users\Create::class, 'render'])->name('create');
    });
    
    // Vendor Management
    Route::prefix('vendor-management')->name('vendors.')->group(function () {
        Route::get('/applications', [Admin\Vendors\Applications::class, 'render'])->name('applications');
        Route::get('/verification', [Admin\Vendors\Verification::class, 'render'])->name('verification');
        Route::get('/subscriptions', [Admin\Vendors\Subscriptions::class, 'render'])->name('subscriptions');
        Route::get('/{vendor}/profile', [Admin\Vendors\Profile::class, 'render'])->name('profile');
    });
    
    // RFQ Management
    Route::prefix('rfqs')->name('rfqs.')->group(function () {
        Route::get('/', [Admin\RFQ\Index::class, 'render'])->name('index');
        Route::get('/pending', [Admin\RFQ\Pending::class, 'render'])->name('pending');
        Route::get('/{rfq}/details', [Admin\RFQ\Details::class, 'render'])->name('details');
    });
    
    // Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [Admin\Orders\Index::class, 'render'])->name('index');
        Route::get('/pending', [Admin\Orders\Pending::class, 'render'])->name('pending');
        Route::get('/processing', [Admin\Orders\Processing::class, 'render'])->name('processing');
        Route::get('/completed', [Admin\Orders\Completed::class, 'render'])->name('completed');
        Route::get('/cancelled', [Admin\Orders\Cancelled::class, 'render'])->name('cancelled');
        Route::get('/{order}/details', [Admin\Orders\Details::class, 'render'])->name('details');
    });
    
    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [Admin\Payments\Index::class, 'render'])->name('index');
        Route::get('/pending', [Admin\Payments\Pending::class, 'render'])->name('pending');
        Route::get('/settlements', [Admin\Payments\Settlements::class, 'render'])->name('settlements');
        Route::get('/refunds', [Admin\Payments\Refunds::class, 'render'])->name('refunds');
        Route::get('/commissions', [Admin\Payments\Commissions::class, 'render'])->name('commissions');
    });
    
    // Product Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [Admin\Categories\Index::class, 'render'])->name('index');
        Route::get('/create', [Admin\Categories\Create::class, 'render'])->name('create');
        Route::get('/{category}/edit', [Admin\Categories\Edit::class, 'render'])->name('edit');
    });
    
    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [Admin\Reports\Index::class, 'render'])->name('index');
        Route::get('/sales', [Admin\Reports\Sales::class, 'render'])->name('sales');
        Route::get('/vendors', [Admin\Reports\Vendors::class, 'render'])->name('vendors');
        Route::get('/buyers', [Admin\Reports\Buyers::class, 'render'])->name('buyers');
        Route::get('/products', [Admin\Reports\Products::class, 'render'])->name('products');
        Route::get('/financial', [Admin\Reports\Financial::class, 'render'])->name('financial');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Admin\Settings\Index::class, 'render'])->name('index');
        Route::get('/general', [Admin\Settings\General::class, 'render'])->name('general');
        Route::get('/marketplace', [Admin\Settings\Marketplace::class, 'render'])->name('marketplace');
        Route::get('/payment', [Admin\Settings\Payment::class, 'render'])->name('payment');
        Route::get('/email', [Admin\Settings\Email::class, 'render'])->name('email');
        Route::get('/notifications', [Admin\Settings\Notifications::class, 'render'])->name('notifications');
        Route::get('/delivery', [Admin\Settings\Delivery::class, 'render'])->name('delivery');
        Route::get('/api', [Admin\Settings\Api::class, 'render'])->name('api');
    });
    
    // Support & Disputes
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/tickets', [Admin\Support\Tickets::class, 'render'])->name('tickets');
        Route::get('/disputes', [Admin\Support\Disputes::class, 'render'])->name('disputes');
        Route::get('/feedback', [Admin\Support\Feedback::class, 'render'])->name('feedback');
    });
    
    // System
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/logs', [Admin\System\Logs::class, 'render'])->name('logs');
        Route::get('/activity', [Admin\System\Activity::class, 'render'])->name('activity');
        Route::get('/backup', [Admin\System\Backup::class, 'render'])->name('backup');
        Route::get('/maintenance', [Admin\System\Maintenance::class, 'render'])->name('maintenance');
        Route::get('/cache', [Admin\System\Cache::class, 'render'])->name('cache');
    });
    
    */
});