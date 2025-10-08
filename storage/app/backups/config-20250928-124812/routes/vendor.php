<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Livewire\Vendor; // Temporarily disabled

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
|
| Routes for vendor panel and vendor-specific functionality
|
*/

// Vendor verification page (accessible without auth)
Route::get('/vendor/verification', function() {
    return view('vendor.verification');
})->name('vendor.verification');

// Temporarily remove auth requirement for testing RFQ flow
Route::get('/vendor/dashboard', function() {
    return view('vendor.dashboard');
})->name('vendor.dashboard');

Route::prefix('vendor')->name('vendor.')->middleware(['auth:vendor'])->group(function () {

    // Dashboard route - New architecture following buyer pattern (protected)
    // Route::get('/dashboard', function() {
    //     return view('vendor.dashboard');
    // })->name('dashboard');
    
    // Product Management - Temporarily disabled Livewire
    // Route::get('/product-catalog', Vendor\ProductCatalog::class)->name('product-catalog');
    
    // RFQ Management - Temporarily disabled Livewire
    // Route::get('/rfq-browser', Vendor\RFQBrowser::class)->name('rfq-browser');
    // Route::get('/quote-form', Vendor\QuoteForm::class)->name('quote-form');
    
    // Order Management - Temporarily disabled Livewire
    // Route::get('/orders', Vendor\OrderList::class)->name('orders');
    
    // Financial - Temporarily disabled Livewire
    // Route::get('/earnings', Vendor\EarningsOverview::class)->name('earnings');
    
    // Stripe Connect
    Route::prefix('stripe')->name('stripe.')->group(function () {
        Route::get('/onboarding', [\App\Http\Controllers\Vendor\StripeController::class, 'onboarding'])->name('onboarding');
        Route::get('/refresh', [\App\Http\Controllers\Vendor\StripeController::class, 'refresh'])->name('refresh');
        Route::get('/return', [\App\Http\Controllers\Vendor\StripeController::class, 'return'])->name('return');
        Route::get('/dashboard', [\App\Http\Controllers\Vendor\StripeController::class, 'dashboard'])->name('dashboard');
        Route::get('/status', [\App\Http\Controllers\Vendor\StripeController::class, 'status'])->name('status');
        Route::post('/disconnect', [\App\Http\Controllers\Vendor\StripeController::class, 'disconnect'])->name('disconnect');
        Route::get('/payouts', [\App\Http\Controllers\Vendor\StripeController::class, 'payouts'])->name('payouts');
        Route::get('/balance', [\App\Http\Controllers\Vendor\StripeController::class, 'balance'])->name('balance');
    });
    
    /* TEMPORARILY COMMENTED OUT - These Livewire components need to be created
    
    // Extended Product Management
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [Vendor\Products\Index::class, 'render'])->name('index');
        Route::get('/create', [Vendor\Products\Create::class, 'render'])->name('create');
        Route::get('/{product}/edit', [Vendor\Products\Edit::class, 'render'])->name('edit');
        Route::get('/inventory', [Vendor\Products\Inventory::class, 'render'])->name('inventory');
        Route::get('/import', [Vendor\Products\Import::class, 'render'])->name('import');
        Route::get('/export', [Vendor\Products\Export::class, 'render'])->name('export');
    });
    
    // Extended RFQ Management
    Route::prefix('rfqs')->name('rfqs.')->group(function () {
        Route::get('/', [Vendor\RFQ\Index::class, 'render'])->name('index');
        Route::get('/available', [Vendor\RFQ\Available::class, 'render'])->name('available');
        Route::get('/my-quotes', [Vendor\RFQ\MyQuotes::class, 'render'])->name('quotes');
        Route::get('/{rfq}/respond', [Vendor\RFQ\Respond::class, 'render'])->name('respond');
        Route::get('/quote/{quote}/edit', [Vendor\RFQ\EditQuote::class, 'render'])->name('quote.edit');
    });
    
    // Extended Quote Management
    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/', [Vendor\Quotes\Index::class, 'render'])->name('index');
        Route::get('/pending', [Vendor\Quotes\Pending::class, 'render'])->name('pending');
        Route::get('/accepted', [Vendor\Quotes\Accepted::class, 'render'])->name('accepted');
        Route::get('/rejected', [Vendor\Quotes\Rejected::class, 'render'])->name('rejected');
        Route::get('/expired', [Vendor\Quotes\Expired::class, 'render'])->name('expired');
        Route::get('/create', [Vendor\Quotes\Create::class, 'render'])->name('create');
        Route::get('/{quote}/details', [Vendor\Quotes\Details::class, 'render'])->name('details');
    });
    
    // Extended Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [Vendor\Orders\Index::class, 'render'])->name('index');
        Route::get('/pending', [Vendor\Orders\Pending::class, 'render'])->name('pending');
        Route::get('/processing', [Vendor\Orders\Processing::class, 'render'])->name('processing');
        Route::get('/ready', [Vendor\Orders\Ready::class, 'render'])->name('ready');
        Route::get('/completed', [Vendor\Orders\Completed::class, 'render'])->name('completed');
        Route::get('/cancelled', [Vendor\Orders\Cancelled::class, 'render'])->name('cancelled');
        Route::get('/{order}/details', [Vendor\Orders\Details::class, 'render'])->name('details');
        Route::get('/{order}/invoice', [Vendor\Orders\Invoice::class, 'render'])->name('invoice');
        Route::get('/{order}/packing-slip', [Vendor\Orders\PackingSlip::class, 'render'])->name('packing-slip');
    });
    
    // Delivery Management
    Route::prefix('delivery')->name('delivery.')->group(function () {
        Route::get('/', [Vendor\Delivery\Index::class, 'render'])->name('index');
        Route::get('/schedule', [Vendor\Delivery\Schedule::class, 'render'])->name('schedule');
        Route::get('/tracking', [Vendor\Delivery\Tracking::class, 'render'])->name('tracking');
        Route::get('/partners', [Vendor\Delivery\Partners::class, 'render'])->name('partners');
    });
    
    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [Vendor\Customers\Index::class, 'render'])->name('index');
        Route::get('/{customer}/profile', [Vendor\Customers\Profile::class, 'render'])->name('profile');
        Route::get('/{customer}/orders', [Vendor\Customers\Orders::class, 'render'])->name('orders');
    });
    
    // Messaging
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [Vendor\Messages\Index::class, 'render'])->name('index');
        Route::get('/compose', [Vendor\Messages\Compose::class, 'render'])->name('compose');
        Route::get('/{conversation}', [Vendor\Messages\Conversation::class, 'render'])->name('conversation');
    });
    
    // Analytics & Reports
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [Vendor\Analytics\Index::class, 'render'])->name('index');
        Route::get('/sales', [Vendor\Analytics\Sales::class, 'render'])->name('sales');
        Route::get('/products', [Vendor\Analytics\Products::class, 'render'])->name('products');
        Route::get('/customers', [Vendor\Analytics\Customers::class, 'render'])->name('customers');
        Route::get('/rfq-performance', [Vendor\Analytics\RfqPerformance::class, 'render'])->name('rfq-performance');
    });
    
    // Extended Financial
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [Vendor\Financial\Index::class, 'render'])->name('index');
        Route::get('/payouts', [Vendor\Financial\Payouts::class, 'render'])->name('payouts');
        Route::get('/invoices', [Vendor\Financial\Invoices::class, 'render'])->name('invoices');
        Route::get('/statements', [Vendor\Financial\Statements::class, 'render'])->name('statements');
    });
    
    // Promotions
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [Vendor\Promotions\Index::class, 'render'])->name('index');
        Route::get('/discounts', [Vendor\Promotions\Discounts::class, 'render'])->name('discounts');
        Route::get('/bulk-pricing', [Vendor\Promotions\BulkPricing::class, 'render'])->name('bulk-pricing');
        Route::get('/featured-products', [Vendor\Promotions\FeaturedProducts::class, 'render'])->name('featured');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Vendor\Settings\Index::class, 'render'])->name('index');
        Route::get('/business', [Vendor\Settings\Business::class, 'render'])->name('business');
        Route::get('/bank-details', [Vendor\Settings\BankDetails::class, 'render'])->name('bank');
        Route::get('/shipping', [Vendor\Settings\Shipping::class, 'render'])->name('shipping');
        Route::get('/tax', [Vendor\Settings\Tax::class, 'render'])->name('tax');
        Route::get('/notifications', [Vendor\Settings\Notifications::class, 'render'])->name('notifications');
        Route::get('/api', [Vendor\Settings\Api::class, 'render'])->name('api');
        Route::get('/subscription', [Vendor\Settings\Subscription::class, 'render'])->name('subscription');
    });
    
    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [Vendor\Support\Index::class, 'render'])->name('index');
        Route::get('/tickets', [Vendor\Support\Tickets::class, 'render'])->name('tickets');
        Route::get('/knowledge-base', [Vendor\Support\KnowledgeBase::class, 'render'])->name('knowledge');
        Route::get('/contact', [Vendor\Support\Contact::class, 'render'])->name('contact');
    });
    
    */
});