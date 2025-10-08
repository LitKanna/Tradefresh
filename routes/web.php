<?php

use App\Http\Controllers\BroadcastAuthController;
use Illuminate\Support\Facades\Route;

// Analytics Platform Test Page - Removed (view deleted in cleanup)
// Route::get('/analytics-test', ...);

// Smart Intelligence Test Page - Removed (view deleted in cleanup)
// Route::get('/smart-intelligence', ...);

// Customer Intelligence Demo Page - Removed (view deleted in cleanup)
// Route::get('/customer-intelligence-demo', ...);

/*
|--------------------------------------------------------------------------
| Web Routes - FIXED VERSION
|--------------------------------------------------------------------------
|
| Main web routes for the Sydney Markets B2B Platform
| Buyer routes have been fixed to match vendor/admin pattern
|
*/

// Broadcasting authentication with multi-guard support
Route::post('/broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Test login route to debug authentication
Route::get('/test-login', function () {
    return view('test-login');
})->name('test-login');

// ABN API Test Pages (Development only)
Route::get('/test-abn', function () {
    return view('test-abn');
})->name('test-abn');

// Demand Forecasting Tool - Removed (controller deleted - violates "NO COMPLEX ANALYTICS" rule)

// Vendor Navigation Test Page
Route::get('/test-vendor-navigation', function () {
    return view('test-vendor-navigation');
})->name('test-vendor-navigation');

// Vendor Flow Test Page
Route::get('/test-vendor-flow', function () {
    return view('test-vendor-flow');
})->name('test-vendor-flow');

// Vendor routes - placeholders to fix missing route errors
Route::get('/vendor/profile', function () {
    return redirect('/vendor/dashboard');
})->name('vendor.profile');

Route::get('/vendor/settings', function () {
    return redirect('/vendor/dashboard');
})->name('vendor.settings');

// Comprehensive Integration Test Dashboard
Route::get('/test-integration', function () {
    return view('test-integration');
})->name('test-integration');

// Test Buyer Registration Page
Route::get('/test-buyer-registration', function () {
    return view('test-buyer-registration');
})->name('test-buyer-registration');

// Test Buyer Login Page
Route::get('/test-buyer-login', function () {
    return view('test-buyer-login');
})->name('test-buyer-login');

// Auto-login for testing - WORKING VERSION
Route::get('/test-auto-login', function () {
    $buyer = \App\Models\Buyer::where('email', 'test@buyer.com')->first();

    if ($buyer) {
        // Ensure password is correct
        $buyer->password = bcrypt('password');
        $buyer->status = 'active';
        $buyer->save();

        // Force login
        Auth::guard('buyer')->login($buyer, true);

        // Session regenerate for security
        session()->regenerate();

        // Verify login worked
        if (Auth::guard('buyer')->check()) {
            // Success! Show direct link
            return "
            <html>
            <head><title>Login Successful!</title></head>
            <body style='font-family: Arial; text-align: center; padding: 50px;'>
                <h1 style='color: green;'>✅ Login Successful!</h1>
                <p>You are now logged in as: <strong>test@buyer.com</strong></p>
                <h2>Click below to view discovered leads:</h2>
                <a href='/buyer/discovered-leads' style='display: inline-block; padding: 15px 30px; background: #10B981; color: white; text-decoration: none; border-radius: 5px; font-size: 18px;'>
                    📧 View Discovered Leads (92 Emails)
                </a>
                <br><br>
                <a href='/buyer/dashboard' style='display: inline-block; padding: 10px 20px; background: #6B7280; color: white; text-decoration: none; border-radius: 5px;'>
                    Or Go to Dashboard
                </a>
            </body>
            </html>
            ";
        } else {
            return 'Login failed - please check auth configuration';
        }
    }

    return 'Test buyer not found. Please run: php artisan tinker and create test buyer.';
})->name('test-auto-login');

// Direct access to discovered leads (no auth for testing)
Route::get('/test-discovered-leads', function () {
    return view('buyer.discovered-leads-direct');
})->name('test-discovered-leads');

// EMERGENCY BYPASS ROUTES - NO AUTH REQUIRED
Route::get('/dashboard-now', function () {
    // Force login the test buyer
    $buyer = \App\Models\Buyer::where('email', 'test@buyer.com')->first();
    if ($buyer) {
        Auth::guard('buyer')->login($buyer, true);
    }

    return view('buyer.dashboard-direct');
})->name('dashboard-now');

Route::get('/leads-now', function () {
    // Force login the test buyer
    $buyer = \App\Models\Buyer::where('email', 'test@buyer.com')->first();
    if ($buyer) {
        Auth::guard('buyer')->login($buyer, true);
    }

    return view('buyer.discovered-leads-direct');
})->name('leads-now');

// Enterprise Analytics Dashboard
Route::get('/analytics-enterprise', function () {
    // Mock intelligence data for visualization
    $intelligence = [
        'summary' => [
            'potential_savings' => 63395,
            'total_spend' => 422632,
            'active_vendors' => 47,
            'price_alerts' => 18,
        ],
    ];

    return view('buyer.analytics-dashboard-enterprise', compact('intelligence'));
})->name('analytics-enterprise');

// BulkHunter Lead Discovery System
Route::get('/bulkhunter', \App\Livewire\Vendor\BulkHunter::class)->name('bulkhunter');

// Temporary Test Dashboard (No Auth Required)
Route::get('/test-dashboard-layout', function () {
    // Create mock dashboardData for testing without authentication
    $dashboardData = [
        'overview' => [
            'total_spent' => 125000,
            'active_suppliers' => 18,
            'credit_available' => 35000,
            'credit_utilization' => 30,
            'total_orders' => 47,
            'growth' => [
                'spending' => 12,
                'suppliers' => 3,
                'orders' => 8,
            ],
        ],
    ];
    $marketData = ['products' => []];
    $preferences = ['auto_refresh' => true];

    return view('buyer.dashboard', compact('dashboardData', 'marketData', 'preferences'));
})->name('test-dashboard-layout');

Route::get('/test-abn-real', function () {
    return view('test-abn-real');
})->name('test-abn-real');

Route::get('/test-abn-fixed', function () {
    return view('test-abn-fixed');
})->name('test-abn-fixed');

Route::get('/test-abn-enhanced', function () {
    return view('test-abn-enhanced');
})->name('test-abn-enhanced');

// Temporary Test Vendor Dashboard (No Auth Required for Development)
Route::get('/test-vendor-dashboard', function () {
    // Auto-login the first vendor for testing
    $vendor = \App\Models\Vendor::first();
    if ($vendor) {
        Auth::guard('vendor')->login($vendor);
    }

    return view('vendor.dashboard');
})->name('test-vendor-dashboard');

// Vendor Auto-Login for Testing
Route::get('/vendor-auto-login', function () {
    $vendor = \App\Models\Vendor::where('email', 'maruthi4a5@gmail.com')->first();

    if ($vendor) {
        Auth::guard('vendor')->login($vendor, true);
        session()->regenerate();

        return redirect('/vendor/dashboard')->with('success', 'Auto-logged in as '.$vendor->business_name);
    }

    return 'Vendor not found!';
})->name('vendor.auto-login');

// ABN Integration Proof-of-Concept Routes
// Temporarily disabled - controller needs to be created
// Route::get('/test-abn-integration', [\App\Http\Controllers\TestABNIntegrationController::class, 'testInterface'])->name('test-abn-integration');
// Route::post('/test-abn-integration', [\App\Http\Controllers\TestABNIntegrationController::class, 'testIntegration'])->name('test-abn-integration.post');
// Route::get('/test-abn-quick-verify', [\App\Http\Controllers\TestABNIntegrationController::class, 'quickVerify'])->name('test-abn-quick-verify');

// CSRF Token Refresh Route (for preventing 419 errors)
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web');

// Default login route (redirects to buyer login)
Route::get('/login', function () {
    return redirect('/auth/buyer/login');
})->name('login');

Route::get('/about', function () {
    return view('about');
})->name('about');

// Analytics Dashboard (Direct Access for Testing)
Route::get('/analytics-dashboard', function () {
    return view('buyer.analytics-dashboard');
})->name('analytics-dashboard');

// Test route for new dashboard architecture
Route::get('/dashboard/test', function () {
    return view('dashboard.main');
})->name('dashboard.test');

// NEW: Sales Analytics Dashboard with Real Data Processing
Route::get('/sales-analytics', function () {
    return view('buyer.sales-analytics');
})->name('sales-analytics');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Include Support System Routes - Removed in cleanup (controllers were deleted)
// require __DIR__.'/support.php';

// Include test auth routes for debugging
require __DIR__.'/test-auth.php';
require __DIR__.'/buyer-fix.php';

// Vendor ABN Validation Routes (for registration form)
Route::prefix('auth/vendor')->group(function () {
    Route::post('/abn-validate', [App\Http\Controllers\Auth\VendorAuthController::class, 'validateABN'])
        ->name('vendor.abn.validate');
    Route::post('/abn-lookup', [App\Http\Controllers\Auth\VendorAuthController::class, 'lookupABN'])
        ->name('vendor.abn.lookup');
    Route::post('/abn-search', [App\Http\Controllers\Auth\VendorAuthController::class, 'searchBusinessName'])
        ->name('vendor.abn.search');
});

Route::get('/marketplace', function () {
    return view('marketplace');
})->name('marketplace');

// PERFORMANCE OPTIMIZED DASHBOARD ROUTES - Protected by auth:buyer
Route::middleware(['web', 'auth:buyer'])->group(function () {
    // API endpoint for fetching buyer quotes (used by dashboard JavaScript)
    Route::get('/api/buyer/quotes', function () {
        $buyer = \Illuminate\Support\Facades\Auth::guard('buyer')->user();

        if (! $buyer) {
            return response()->json(['quotes' => [], 'message' => 'Not authenticated']);
        }

        $quotes = \App\Models\Quote::where('buyer_id', $buyer->id)
            ->where('status', 'submitted')
            ->with(['vendor', 'rfq'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($quote) {
                // Filter out quotes older than 30 minutes
                $expiryTime = $quote->created_at->copy()->addMinutes(30);

                return $expiryTime > now();
            })
            ->values(); // Reset array keys after filtering

        return response()->json(['quotes' => $quotes]);
    })->name('buyer.api.quotes');
    // Performance Dashboard Routes - COMMENTED OUT - Using the one below with more logic
    // Route::get('/buyer/dashboard', function() {
    //     return view('buyer.dashboard');
    // })->name('buyer.dashboard');
    // Standard dashboard route only
    // Route::get('/buyer/dashboard/updates', [\App\Http\Controllers\Buyer\PerformanceDashboardController::class, 'getRealtimeUpdates'])->name('buyer.dashboard.updates');
    // Route::get('/buyer/dashboard/prefetch', [\App\Http\Controllers\Buyer\PerformanceDashboardController::class, 'prefetch'])->name('buyer.dashboard.prefetch');
    // Route::post('/buyer/logout', [\App\Http\Controllers\Buyer\PerformanceDashboardController::class, 'logout'])->name('buyer.logout'); // Commented out - duplicate route

    // Modular Dashboard Route
    Route::get('/buyer/dashboard/modular', function () {
        return view('buyer.dashboard.simple');
    })->name('buyer.dashboard.modular');

    // Smart Intelligence Dashboard - Removed in cleanup (controller was deleted)
    // Route::get('/buyer/smart-intelligence', [\App\Http\Controllers\Buyer\SmartIntelligenceController::class, 'index'])->name('buyer.smart-intelligence');
    // Route::post('/buyer/smart-intelligence/analyze', [\App\Http\Controllers\Buyer\SmartIntelligenceController::class, 'analyzeData'])->name('buyer.smart-intelligence.analyze');
});

// Authentication routes for different user types - ALL USING SAME PATTERN
Route::prefix('auth')->middleware(['web'])->group(function () {

    // Buyer authentication - FIXED to match vendor/admin pattern with proper CSRF protection
    Route::prefix('buyer')->name('buyer.')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'login'])->name('login.post');
        Route::get('/register', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'register'])->name('register.post');
        Route::post('/logout', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'logout'])->name('logout');

        // Password reset routes
        Route::get('/password/request', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'showPasswordRequestForm'])->name('password.request');
        Route::post('/password/email', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'sendResetLink'])->name('password.email');
        Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'showResetForm'])->name('password.reset');
        Route::post('/password/reset', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'resetPassword'])->name('password.update');

        // AJAX endpoints for registration
        Route::post('/check-email', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'checkEmail'])->name('check-email');
        Route::post('/check-abn', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'checkABN'])->name('check-abn');
        Route::post('/get-business-details', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'getBusinessDetails'])->name('get-business-details');
        Route::post('/validate-rego', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'validateRego'])->name('validate-rego');

        // Vehicle and pickup management (authenticated)
        Route::middleware('auth:buyer')->group(function () {
            Route::post('/add-vehicle', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'addVehicle'])->name('add-vehicle');
            Route::post('/update-pickup-preferences', [\App\Http\Controllers\Auth\BuyerAuthController::class, 'updatePickupPreferences'])->name('update-pickup-preferences');
        });
    });

    // Vendor authentication - working pattern
    Route::prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Auth\VendorAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\VendorAuthController::class, 'login'])->name('login.post');
        Route::get('/register', [\App\Http\Controllers\Auth\VendorAuthController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [\App\Http\Controllers\Auth\VendorAuthController::class, 'register'])->name('register.post');
        Route::post('/logout', [\App\Http\Controllers\Auth\VendorAuthController::class, 'logout'])->name('logout');

        // Password reset routes
        Route::get('/password/request', [\App\Http\Controllers\Auth\VendorAuthController::class, 'showPasswordRequestForm'])->name('password.request');
        Route::post('/password/email', [\App\Http\Controllers\Auth\VendorAuthController::class, 'sendResetLink'])->name('password.email');
        Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\VendorAuthController::class, 'showResetForm'])->name('password.reset');
        Route::post('/password/reset', [\App\Http\Controllers\Auth\VendorAuthController::class, 'resetPassword'])->name('password.update');

        // ABN Lookup API endpoints - Using the Api controller
        Route::post('/abn-lookup', [\App\Http\Controllers\Api\ABNLookupController::class, 'lookup'])->name('abn-lookup');
        Route::post('/abn-validate', [\App\Http\Controllers\Api\ABNLookupController::class, 'validateAbnRequest'])->name('abn-validate');
    });

    // Admin authentication - working pattern
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'login'])->name('login.post');
        Route::post('/logout', [\App\Http\Controllers\Auth\AdminAuthController::class, 'logout'])->name('logout');
    });
});

// Shared authenticated routes (accessible by all authenticated users)
Route::middleware(['auth:web,admin,vendor,buyer'])->group(function () {
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.delete');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.count');

    // Messages
    // Message routes disabled - controller was deleted in cleanup
    // Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages');
    // Route::get('/messages/create', [\App\Http\Controllers\MessageController::class, 'create'])->name('messages.create');
    // Route::post('/messages', [\App\Http\Controllers\MessageController::class, 'send'])->name('messages.send');
    // Route::get('/messages/{conversation}', [\App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
    // Route::post('/messages/{conversation}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead'])->name('messages.read');
    // Route::delete('/messages/{conversation}', [\App\Http\Controllers\MessageController::class, 'destroy'])->name('messages.delete');
    // Route::get('/messages/search', [\App\Http\Controllers\MessageController::class, 'search'])->name('messages.search');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/edit', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [\App\Http\Controllers\ProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::delete('/profile/photo', [\App\Http\Controllers\ProfileController::class, 'removePhoto'])->name('profile.photo.remove');
    Route::put('/profile/notifications', [\App\Http\Controllers\ProfileController::class, 'updateNotifications'])->name('profile.notifications');
});

// Public Document Access Routes
Route::prefix('documents')->name('documents.')->group(function () {
    // Shared document access (no authentication required)
    // DocumentAccessController removed in cleanup - routes disabled
    // Route::get('/shared/{token}', [\App\Http\Controllers\DocumentAccessController::class, 'accessShared'])->name('shared');
    // Route::post('/shared/{token}/password', [\App\Http\Controllers\DocumentAccessController::class, 'verifyPassword'])->name('shared.password');
    // Route::get('/shared/{token}/download', [\App\Http\Controllers\DocumentAccessController::class, 'downloadShared'])->name('shared.download');

    // Document signing (external access)
    Route::get('/sign/{signature}/{token}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'sign'])->name('sign');
    Route::post('/sign/{signature}/{token}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'storeSignature'])->name('sign.store');
    Route::post('/sign/{signature}/{token}/decline', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'decline'])->name('sign.decline');
    Route::get('/sign/{signature}/complete', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'complete'])->name('sign.complete');
    Route::get('/sign/{signature}/declined', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'declined'])->name('sign.declined');
});

// Public marketplace and catalog routes
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductController::class, 'index'])->name('index');
    Route::get('/categories', [\App\Http\Controllers\ProductController::class, 'categories'])->name('categories');
    Route::get('/category/{slug}', [\App\Http\Controllers\ProductController::class, 'category'])->name('category');
    Route::get('/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('show');
    Route::get('/{id}/quick-view', [\App\Http\Controllers\ProductController::class, 'quickView'])->name('quick-view');
    Route::get('/{id}/price-history', [\App\Http\Controllers\ProductController::class, 'priceHistory'])->name('price-history');
    Route::post('/{id}/favorite', [\App\Http\Controllers\ProductController::class, 'toggleFavorite'])->name('favorite')->middleware('auth:buyer');
});

Route::prefix('vendors')->name('vendors.')->group(function () {
    Route::get('/', [\App\Http\Controllers\VendorController::class, 'index'])->name('index');
    Route::get('/search', [\App\Http\Controllers\VendorController::class, 'search'])->name('search');
    Route::get('/{id}', [\App\Http\Controllers\VendorController::class, 'show'])->name('show');
    Route::get('/{id}/products', [\App\Http\Controllers\VendorController::class, 'products'])->name('products');
    Route::get('/{id}/ratings', [\App\Http\Controllers\VendorController::class, 'ratings'])->name('ratings');
    Route::post('/{id}/favorite', [\App\Http\Controllers\VendorController::class, 'toggleFavorite'])->name('favorite')->middleware('auth:buyer');
    Route::post('/{id}/rate', [\App\Http\Controllers\VendorController::class, 'rate'])->name('rate')->middleware('auth:buyer');
    Route::post('/{id}/contact', [\App\Http\Controllers\VendorController::class, 'contact'])->name('contact')->middleware('auth:buyer');
});

// DISABLED: RFQController doesn't exist - RFQ functionality handled by RFQService + Livewire components
// Route::prefix('rfqs')->name('rfqs.')->group(function () {
//     Route::get('/', [\App\Http\Controllers\RFQController::class, 'index'])->name('index');
//     Route::get('/search', [\App\Http\Controllers\RFQController::class, 'search'])->name('search');
//     Route::get('/create', [\App\Http\Controllers\RFQController::class, 'create'])->name('create')->middleware('auth:buyer');
//     Route::post('/', [\App\Http\Controllers\RFQController::class, 'store'])->name('store')->middleware('auth:buyer');
//     Route::get('/{id}', [\App\Http\Controllers\RFQController::class, 'show'])->name('show');
//     Route::get('/{id}/quotes', [\App\Http\Controllers\RFQController::class, 'quotes'])->name('quotes')->middleware('auth:buyer');
//     Route::get('/{id}/statistics', [\App\Http\Controllers\RFQController::class, 'statistics'])->name('statistics');
//     Route::get('/{id}/attachments/{index}', [\App\Http\Controllers\RFQController::class, 'downloadAttachment'])->name('attachment');
//     Route::post('/{id}/track-view', [\App\Http\Controllers\RFQController::class, 'trackView'])->name('track-view');
//     Route::put('/{id}', [\App\Http\Controllers\RFQController::class, 'update'])->name('update')->middleware('auth:buyer');
//     Route::post('/{id}/cancel', [\App\Http\Controllers\RFQController::class, 'cancel'])->name('cancel')->middleware('auth:buyer');
//     Route::post('/{id}/close', [\App\Http\Controllers\RFQController::class, 'close'])->name('close')->middleware('auth:buyer');
//     Route::post('/{id}/duplicate', [\App\Http\Controllers\RFQController::class, 'duplicate'])->name('duplicate')->middleware('auth:buyer');
//     Route::post('/{id}/extend', [\App\Http\Controllers\RFQController::class, 'extend'])->name('extend')->middleware('auth:buyer');
// });

// DISABLED: QuoteController doesn't exist - Quote functionality handled by QuoteService + Livewire components
// Route::prefix('quotes')->name('quotes.')->group(function () {
//     Route::get('/', [\App\Http\Controllers\QuoteController::class, 'index'])->name('index');
//     Route::get('/create/{rfq}', [\App\Http\Controllers\QuoteController::class, 'create'])->name('create')->middleware('auth:vendor');
//     Route::post('/rfq/{rfq}', [\App\Http\Controllers\QuoteController::class, 'store'])->name('store')->middleware('auth:vendor');
//     Route::get('/{id}', [\App\Http\Controllers\QuoteController::class, 'show'])->name('show');
//     Route::get('/{id}/edit', [\App\Http\Controllers\QuoteController::class, 'edit'])->name('edit')->middleware('auth:vendor');
//     Route::put('/{id}', [\App\Http\Controllers\QuoteController::class, 'update'])->name('update')->middleware('auth:vendor');
//     Route::post('/{id}/accept', [\App\Http\Controllers\QuoteController::class, 'accept'])->name('accept')->middleware('auth:buyer');
//     Route::post('/{id}/reject', [\App\Http\Controllers\QuoteController::class, 'reject'])->name('reject')->middleware('auth:buyer');
//     Route::post('/{id}/withdraw', [\App\Http\Controllers\QuoteController::class, 'withdraw'])->name('withdraw')->middleware('auth:vendor');
//     Route::get('/{id}/download', [\App\Http\Controllers\QuoteController::class, 'download'])->name('download');
//     Route::get('/{id}/attachments/{index}', [\App\Http\Controllers\QuoteController::class, 'downloadAttachment'])->name('attachment');
//     Route::get('/compare', [\App\Http\Controllers\QuoteController::class, 'compare'])->name('compare')->middleware('auth:buyer');
// });

// TEMPORARILY COMMENTED OUT - OrderController doesn't exist yet
// Route::prefix('orders')->name('orders.')->group(function () {
//     Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('index');
//     Route::post('/', [\App\Http\Controllers\OrderController::class, 'store'])->name('store')->middleware('auth:buyer');
//     Route::get('/{id}', [\App\Http\Controllers\OrderController::class, 'show'])->name('show');
//     Route::get('/{id}/invoice', [\App\Http\Controllers\OrderController::class, 'invoice'])->name('invoice');
//     Route::get('/{id}/invoice/download', [\App\Http\Controllers\OrderController::class, 'downloadInvoice'])->name('invoice.download');
//     Route::get('/{id}/packing-slip', [\App\Http\Controllers\OrderController::class, 'packingSlip'])->name('packing-slip')->middleware('auth:vendor');
//     Route::get('/{id}/tracking', [\App\Http\Controllers\OrderController::class, 'tracking'])->name('tracking');
//     Route::post('/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('status')->middleware('auth:vendor');
//     Route::post('/{id}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('cancel');
//     Route::post('/{id}/duplicate', [\App\Http\Controllers\OrderController::class, 'duplicate'])->name('duplicate')->middleware('auth:buyer');
//     Route::post('/{id}/confirm-delivery', [\App\Http\Controllers\OrderController::class, 'confirmDelivery'])->name('confirm-delivery');
// });

// Cart routes - Removed (controller deleted - cart functionality in buyer.php using Buyer\CartController)

// Include role-specific routes
require __DIR__.'/admin.php';
require __DIR__.'/vendor.php';
require __DIR__.'/buyer.php';

// CLEAN BUYER DASHBOARD ROUTES
Route::middleware(['web', 'auth:buyer'])->prefix('buyer')->name('buyer.')->group(function () {
    // Main dashboard
    Route::get('/dashboard', function () {
        $buyer = auth()->guard('buyer')->user();
        $dateRange = request()->get('date_range', 'last_30_days');
        $dashboardData = [
            'overview' => [
                'total_orders' => 24,
                'total_spent' => 87000,
                'average_order_value' => 350,
                'credit_available' => 12500,
                'credit_utilization' => 45,
                'pending_orders' => 8,
                'processing_orders' => 12,
                'shipped_orders' => 3,
                'delivered_orders' => 1,
                'growth' => [
                    'orders' => 12.5,
                    'spending' => 8.3,
                    'avg_order' => 5.2,
                ],
            ],
            'orders' => [
                'recent_orders' => [],
            ],
            'suppliers' => [
                'top_suppliers' => [],
            ],
            'activities' => [],
            'insights' => [],
            'comparisons' => [
                'orders' => ['current' => 24, 'previous' => 20, 'trend' => 'up', 'change' => 20],
                'spending' => ['current' => 87000, 'previous' => 75000, 'trend' => 'up', 'change' => 16],
                'suppliers' => ['current' => 12, 'previous' => 10, 'trend' => 'up', 'change' => 20],
                'average_order' => ['current' => 350, 'previous' => 320, 'trend' => 'up', 'change' => 9.4],
            ],
            'charts' => [
                'spending_trend' => [],
                'category_distribution' => [],
                'order_volume_trend' => [],
            ],
        ];
        $preferences = ['auto_refresh' => true];

        return view('buyer.dashboard');
    })->name('dashboard');

    // Basic navigation routes
    Route::get('/profile', function () {
        return view('buyer.profile');
    })->name('profile');
    Route::get('/orders', function () {
        return view('buyer.orders.index');
    })->name('orders');

    // Add .index versions and all missing navigation routes
    Route::get('/orders', [App\Http\Controllers\Buyer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/quotes', [App\Http\Controllers\Buyer\QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/rfqs', [App\Http\Controllers\Buyer\RFQController::class, 'index'])->name('rfqs.index');
    // REMOVED: Conflicting analytics routes - using simple buyer route instead
    Route::get('/vendors/favorites', [App\Http\Controllers\Buyer\VendorController::class, 'favorites'])->name('vendors.favorites');
    Route::get('/settings', [App\Http\Controllers\Buyer\SettingsController::class, 'index'])->name('settings.index');

    // Navigation routes
    Route::get('/vendors/performance', [App\Http\Controllers\Buyer\VendorController::class, 'performance'])->name('vendors.performance');
    // Route::get('/vendors/reviews', [App\Http\Controllers\Buyer\VendorController::class, 'reviews'])->name('vendors.reviews'); // Commented out - duplicate route in buyer.php
    Route::get('/vendors/directory', [App\Http\Controllers\Buyer\VendorController::class, 'directory'])->name('vendors.directory');
    Route::get('/vendors/compare', [App\Http\Controllers\Buyer\VendorController::class, 'compare'])->name('vendors.compare');
    Route::get('/rfqs/create', [App\Http\Controllers\Buyer\RFQController::class, 'create'])->name('rfqs.create');
    Route::get('/rfqs/manage', [App\Http\Controllers\Buyer\RFQController::class, 'manage'])->name('rfqs.manage');

    // API endpoint for creating RFQ from weekly planner (AJAX)
    Route::post('/rfq/create-from-planner', [\App\Http\Controllers\Api\RFQController::class, 'createFromPlanner'])
        ->name('rfq.create-from-planner');
    Route::get('/quotes', [App\Http\Controllers\Buyer\QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/review', [App\Http\Controllers\Buyer\QuoteController::class, 'review'])->name('quotes.review');
    Route::post('/quotes/compare', [App\Http\Controllers\Buyer\QuoteController::class, 'compare'])->name('quotes.compare');
    Route::get('/quotes/updates', [App\Http\Controllers\Buyer\QuoteController::class, 'updates'])->name('quotes.updates');
    Route::get('/quotes/{id}', [App\Http\Controllers\Buyer\QuoteController::class, 'show'])->name('quotes.show');
    Route::post('/quotes/{id}/accept', [App\Http\Controllers\Buyer\QuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('/quotes/{id}/reject', [App\Http\Controllers\Buyer\QuoteController::class, 'reject'])->name('quotes.reject');
    Route::post('/quotes/{id}/negotiate', [App\Http\Controllers\Buyer\QuoteController::class, 'negotiate'])->name('quotes.negotiate');
    Route::get('/orders/active', [App\Http\Controllers\Buyer\OrderController::class, 'active'])->name('orders.active');
    Route::get('/orders/history', [App\Http\Controllers\Buyer\OrderController::class, 'history'])->name('orders.history');
    Route::get('/orders/track', [App\Http\Controllers\Buyer\OrderController::class, 'track'])->name('orders.track');
    Route::get('/products/favorites', [App\Http\Controllers\Buyer\ProductController::class, 'favorites'])->name('products.favorites');
    Route::get('/products/search', [App\Http\Controllers\Buyer\ProductController::class, 'search'])->name('products.search');
    Route::get('/cart', [App\Http\Controllers\Buyer\CartController::class, 'index'])->name('cart.index');
    Route::get('/delivery/track', [App\Http\Controllers\Buyer\DeliveryController::class, 'track'])->name('delivery.track');
    Route::get('/financial/spending', [App\Http\Controllers\Buyer\AnalyticsController::class, 'spending'])->name('financial.spending');
    Route::get('/financial/invoices', [App\Http\Controllers\Buyer\BillingController::class, 'invoices'])->name('financial.invoices');
    Route::get('/financial/payments', [App\Http\Controllers\Buyer\BillingController::class, 'payments'])->name('financial.payments');
    Route::get('/financial/budgets', [App\Http\Controllers\Buyer\BillingController::class, 'budgets'])->name('financial.budgets');
    Route::get('/help/documentation', [App\Http\Controllers\Buyer\HelpController::class, 'documentation'])->name('help.documentation');
    Route::get('/help/faq', [App\Http\Controllers\Buyer\HelpController::class, 'faq'])->name('help.faq');
    Route::get('/help/contact', [App\Http\Controllers\Buyer\HelpController::class, 'contact'])->name('help.contact');
    Route::get('/notifications', [App\Http\Controllers\Buyer\NotificationController::class, 'index'])->name('notifications.index');

    // Sales Intelligence System Routes - Complete Feature Set
    Route::prefix('sales-intelligence')->name('sales-intelligence.')->group(function () {
        Route::get('/', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'index'])->name('index');
        Route::get('/product-intelligence', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'productIntelligence'])->name('product-intelligence');
        Route::get('/price-alerts', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getPriceAlerts'])->name('price-alerts');
        Route::get('/vendor-recommendations', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getVendorRecommendations'])->name('vendor-recommendations');
        Route::get('/procurement-timing', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getProcurementTiming'])->name('procurement-timing');
        Route::get('/group-buying', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getGroupBuying'])->name('group-buying');
        Route::post('/join-group-buy', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'joinGroupBuy'])->name('join-group-buy');
        Route::get('/live-market', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getLiveMarket'])->name('live-market');
        Route::post('/switch-vendor', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'switchVendor'])->name('switch-vendor');
        Route::post('/upload-sales-data', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'uploadSalesData'])->name('upload-sales-data');
        Route::get('/cost-leaks', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getCostLeaks'])->name('cost-leaks');
        Route::get('/export', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'exportReport'])->name('export');
        Route::get('/optimization-score', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getOptimizationScore'])->name('optimization-score');
        Route::post('/set-price-alert', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'setPriceAlert'])->name('set-price-alert');
        Route::get('/savings-dashboard', [App\Http\Controllers\Buyer\SalesIntelligenceController::class, 'getSavingsDashboard'])->name('savings-dashboard');
    });

    // Vendor Analytics - Real-time vendor data display
    Route::get('/vendor-analytics', function () {
        return view('buyer.vendor-analytics');
    })->name('vendor-analytics');
    Route::get('/vendor-analysis/{vendorId}', function ($vendorId) {
        return view('buyer.vendor-analytics', ['vendorId' => $vendorId]);
    })->name('vendor-analysis');

    // Discovered Leads Management
    Route::get('/discovered-leads', \App\Livewire\Buyer\DiscoveredLeads::class)->name('discovered-leads');
});

// Additional profile route - Disabled (Buyer\ProfileController doesn't exist)
// Route::middleware(['web', 'auth:buyer'])->group(function () {
//     Route::get('/buyer/profile', [App\Http\Controllers\Buyer\ProfileController::class, 'index'])->name('buyer.profile');
// });

// OPTIMAL FIX: Direct email check route with validation and error handling
Route::post('/auth/buyer/check-email', function (\Illuminate\Http\Request $request) {
    $email = trim($request->input('email', ''));

    // Check if email is provided
    if (! $email) {
        return response()->json(['available' => false, 'message' => 'Email is required']);
    }

    // Validate email format
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['available' => false, 'message' => 'Please enter a valid email address']);
    }

    try {
        // Check if email exists in database (case-insensitive)
        $exists = \App\Models\Buyer::where('email', strtolower($email))->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'This email is already registered' : 'Email is available',
        ]);
    } catch (\Exception $e) {
        // Log error for debugging
        \Log::error('Email check error: '.$e->getMessage());

        // Return safe response that allows registration to continue
        return response()->json([
            'available' => true,
            'message' => 'Email validation in progress',
        ]);
    }
})->name('buyer.check-email')->withoutMiddleware(['web', 'csrf']);

// DISABLED: buyer-auth.php routes - now included directly in web.php above
// The auth/buyer routes are defined above with proper middleware
// VENDOR ROUTES - Now defined in vendor.php to avoid duplication
// The vendor routes are automatically loaded by RouteServiceProvider

// ADMIN ROUTES - Protected by auth:admin
Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

// Email verification routes (moved from duplicate section)
Route::prefix('auth/vendor')->group(function () {
    Route::get('/verify/{token}', [\App\Http\Controllers\Auth\VendorAuthController::class, 'verifyEmail'])->name('vendor.verify');
    Route::post('/verify/resend', [\App\Http\Controllers\Auth\VendorAuthController::class, 'resendVerification'])->name('vendor.verify.resend');
    // Route removed - vendor.verification is already defined in vendor.php
});

// ADMIN AUTH ROUTES
Route::get('/auth/admin/login', function () {
    return view('auth.admin.login');
})->name('admin.login');

// Real-Time Messaging Routes (Web-based for session auth)
Route::prefix('api/messages')->name('messages.')->group(function () {
    Route::post('/send', function (Illuminate\Http\Request $request) {
        $user = null;
        $userType = null;

        if (auth('buyer')->check()) {
            $user = auth('buyer')->user();
            $userType = 'buyer';
        } elseif (auth('vendor')->check()) {
            $user = auth('vendor')->user();
            $userType = 'vendor';
        }

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'recipient_id' => 'required|integer',
            'recipient_type' => 'required|in:buyer,vendor',
            'message' => 'required|string|max:5000',
            'quote_id' => 'nullable|integer',
        ]);

        $message = \App\Models\Message::create([
            'sender_id' => $user->id,
            'sender_type' => $userType,
            'recipient_id' => $validated['recipient_id'],
            'recipient_type' => $validated['recipient_type'],
            'message' => $validated['message'],
            'quote_id' => $validated['quote_id'] ?? null,
            'is_read' => false,
        ]);

        event(new \App\Events\MessageSent($message));

        return response()->json(['success' => true, 'message' => $message]);
    })->name('send');

    Route::get('/quote/{quoteId}', function ($quoteId) {
        $user = null;
        $userType = null;

        if (auth('buyer')->check()) {
            $user = auth('buyer')->user();
            $userType = 'buyer';
        } elseif (auth('vendor')->check()) {
            $user = auth('vendor')->user();
            $userType = 'vendor';
        }

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $messages = \App\Models\Message::where('quote_id', $quoteId)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user, $userType) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_type' => $msg->sender_type,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender ? $msg->sender->business_name : 'Unknown',
                    'created_at' => $msg->created_at->toISOString(),
                    'is_own' => $msg->sender_id === $user->id && $msg->sender_type === $userType,
                ];
            });

        return response()->json(['success' => true, 'messages' => $messages]);
    })->name('quote');
});

// if (file_exists(__DIR__.'/buyer-auth.php')) {
//     require __DIR__.'/buyer-auth.php';
// }
