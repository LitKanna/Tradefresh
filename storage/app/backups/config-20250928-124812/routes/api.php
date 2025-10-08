<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;
use App\Http\Controllers\Api\ABNLookupController;
// use App\Http\Controllers\Api\BulletproofABNController; // Deleted in cleanup
// use App\Http\Controllers\Api\PricingIntelligenceController; // Deleted in cleanup
use App\Http\Middleware\ABNRateLimitMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes for the Sydney Markets B2B Platform
| Includes endpoints for mobile apps, WhatsApp, WeChat integrations
|
| NOTE: API routes are temporarily commented out until API controllers are created
| This allows the main application routes to work without errors
|
*/

// Temporary route to prevent errors
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is healthy']);
});

/*
|--------------------------------------------------------------------------
| RFQ API Routes - Real-time WebSocket Integration
|--------------------------------------------------------------------------
*/

// Buyer RFQ endpoints - moved to web.php for session-based auth

// RFQ Update endpoints
Route::prefix('rfq')->group(function () {
    Route::post('/update', [Api\RFQController::class, 'updateRFQ'])
        ->name('api.rfq.update');

    Route::post('/update-vendor', [Api\RFQController::class, 'updateForVendor'])
        ->name('api.rfq.update-vendor');
});

// Vendor Quote endpoints
Route::prefix('vendor/quote')->group(function () {
    Route::post('/submit/{rfqId}', [Api\VendorQuoteController::class, 'submitQuote'])
        ->name('api.vendor.quote.submit');

    // Test endpoint to simulate vendor quotes
    Route::post('/test-submit/{rfqId}', [Api\VendorQuoteController::class, 'testSubmitQuote'])
        ->name('api.vendor.quote.test-submit');
});

/*
|--------------------------------------------------------------------------
| ABN Lookup Routes
|--------------------------------------------------------------------------
|
| Routes for ABN validation, lookup, and verification
|
*/

// Vendor ABN routes (for registration form)
Route::prefix('vendor/abn')->group(function () {
    Route::post('/lookup', [ABNLookupController::class, 'lookup'])
        ->name('vendor.abn.lookup')
        ->middleware('throttle:60,1');
});

/*
|--------------------------------------------------------------------------
| BulkHunter - Automated Buyer Discovery System
|--------------------------------------------------------------------------
|
| Lead generation and enrichment for discovering high-volume bulk buyers
|
*/

use App\Http\Controllers\Api\BulkHunterController;

Route::prefix('bulkhunter')->group(function () {
    // Discovery endpoints
    Route::post('/discover', [BulkHunterController::class, 'discoverLeads'])
        ->name('bulkhunter.discover');
    Route::post('/bulk-process', [BulkHunterController::class, 'bulkProcess'])
        ->name('bulkhunter.bulk-process');

    // Lead management
    Route::get('/leads', [BulkHunterController::class, 'getLeads'])
        ->name('bulkhunter.leads');
    Route::get('/leads/{id}', [BulkHunterController::class, 'getLead'])
        ->name('bulkhunter.lead');
    Route::put('/leads/{id}', [BulkHunterController::class, 'updateLead'])
        ->name('bulkhunter.update');

    // Enrichment
    Route::post('/leads/{id}/enrich', [BulkHunterController::class, 'enrichLead'])
        ->name('bulkhunter.enrich');

    // Contacts and activities
    Route::post('/leads/{id}/contacts', [BulkHunterController::class, 'addContact'])
        ->name('bulkhunter.add-contact');
    Route::post('/leads/{id}/activities', [BulkHunterController::class, 'logActivity'])
        ->name('bulkhunter.log-activity');

    // Statistics
    Route::get('/statistics', [BulkHunterController::class, 'getStatistics'])
        ->name('bulkhunter.statistics');
});

/*
|--------------------------------------------------------------------------
| Master Analytics Platform Routes (30 Agents System)
|--------------------------------------------------------------------------
*/

// Master Analytics routes removed - controller references deleted services
// Route::prefix('analytics')->middleware('throttle:60,1')->group(function () { ... });

/*
|--------------------------------------------------------------------------
| Data Warehouse & Analytics API Routes
|--------------------------------------------------------------------------
|
| Comprehensive data processing and analytics endpoints
|
*/

// Data Warehouse routes removed - controller references deleted services
// Route::prefix('warehouse')->middleware(['auth:sanctum'])->group(function () { ... });

/*
|--------------------------------------------------------------------------
| Analytics API Routes
|--------------------------------------------------------------------------
|
| Enhanced analytics and intelligence endpoints
|
*/

// Analytics routes removed - controllers were deleted in cleanup

/*
|--------------------------------------------------------------------------
| Customer Intelligence API Routes
|--------------------------------------------------------------------------
|
| Routes for customer behavior analysis and intelligence
|
// Customer Intelligence routes removed - controller was deleted in cleanup
/*
Route::prefix('customer-intelligence')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [Api\CustomerIntelligenceController::class, 'dashboard'])
        ->name('api.customer-intelligence.dashboard');

    Route::get('/customer-types', [Api\CustomerIntelligenceController::class, 'customerTypeBreakdown'])
        ->name('api.customer-intelligence.types');

    Route::get('/pricing', [Api\CustomerIntelligenceController::class, 'pricingAnalysis'])
        ->name('api.customer-intelligence.pricing');

    Route::get('/ordering-patterns', [Api\CustomerIntelligenceController::class, 'orderingPatterns'])
        ->name('api.customer-intelligence.patterns');

    Route::get('/product-preferences', [Api\CustomerIntelligenceController::class, 'productPreferences'])
        ->name('api.customer-intelligence.preferences');

    Route::get('/volume-analysis', [Api\CustomerIntelligenceController::class, 'volumeAnalysis'])
        ->name('api.customer-intelligence.volume');

    Route::get('/loyalty', [Api\CustomerIntelligenceController::class, 'loyaltyAnalysis'])
        ->name('api.customer-intelligence.loyalty');

    Route::get('/recommendations', [Api\CustomerIntelligenceController::class, 'recommendations'])
        ->name('api.customer-intelligence.recommendations');

    Route::get('/competitive-insights', [Api\CustomerIntelligenceController::class, 'competitiveInsights'])
        ->name('api.customer-intelligence.competitive');

    Route::get('/compare', [Api\CustomerIntelligenceController::class, 'compareCustomerTypes'])
        ->name('api.customer-intelligence.compare');

    Route::get('/summary', [Api\CustomerIntelligenceController::class, 'summary'])
        ->name('api.customer-intelligence.summary');

    Route::get('/switching-analysis', [Api\CustomerIntelligenceController::class, 'customerTypeSwitchingAnalysis'])
        ->name('api.customer-intelligence.switching');
});
*/

// BulletproofABN routes removed - controller was deleted in cleanup
/*
Route::prefix('abn')->group(function () {
    // BULLETPROOF ABN ROUTES - Cannot return fake data

    // Main lookup endpoint - returns ONLY real ABR data or errors
    Route::post('/lookup', [BulletproofABNController::class, 'lookup'])
        ->name('abn.lookup')
        ->middleware('throttle:60,1'); // 60 requests per minute

    // Quick verify for form auto-population
    Route::post('/quick-verify', [BulletproofABNController::class, 'quickVerify'])
        ->name('abn.quick-verify')
        ->middleware('throttle:60,1');

    // Checksum validation only (no API call)
    Route::post('/validate-checksum', [BulletproofABNController::class, 'validateChecksum'])
        ->name('abn.validate-checksum');

    // Health check endpoint
    Route::get('/health', [BulletproofABNController::class, 'healthCheck'])
        ->name('abn.health');

    // Clear cache (admin only)
    Route::post('/clear-cache', [BulletproofABNController::class, 'clearCache'])
        ->name('abn.clear-cache')
        ->middleware('auth:sanctum');

    // Legacy routes - redirect to bulletproof versions
    Route::get('/validate/{abn}', function($abn) {
        return redirect()->route('abn.validate-checksum', ['abn' => $abn]);
    });
});
*/

/* 
// TO BE IMPLEMENTED - API ROUTES BELOW ARE COMMENTED OUT UNTIL CONTROLLERS ARE CREATED

// Public API endpoints
Route::prefix('v1')->group(function () {
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/login', [Api\AuthController::class, 'login']);
        Route::post('/register', [Api\AuthController::class, 'register']);
        Route::post('/logout', [Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('/refresh', [Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
        Route::post('/forgot-password', [Api\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [Api\AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [Api\AuthController::class, 'verifyEmail']);
    });
    
    // Public marketplace endpoints
    Route::prefix('marketplace')->group(function () {
        Route::get('/products', [Api\ProductController::class, 'index']);
        Route::get('/products/{product}', [Api\ProductController::class, 'show']);
        Route::get('/categories', [Api\CategoryController::class, 'index']);
        Route::get('/categories/{category}/products', [Api\CategoryController::class, 'products']);
        Route::get('/vendors', [Api\VendorController::class, 'index']);
        Route::get('/vendors/{vendor}', [Api\VendorController::class, 'show']);
        Route::get('/vendors/{vendor}/products', [Api\VendorController::class, 'products']);
        Route::get('/search', [Api\SearchController::class, 'search']);
    });
    
    // Market information
    Route::prefix('market-info')->group(function () {
        Route::get('/prices', [Api\MarketController::class, 'prices']);
        Route::get('/availability', [Api\MarketController::class, 'availability']);
        Route::get('/operating-hours', [Api\MarketController::class, 'operatingHours']);
        Route::get('/locations', [Api\MarketController::class, 'locations']);
        Route::get('/news', [Api\MarketController::class, 'news']);
    });
});

// Protected API endpoints (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // User profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [Api\ProfileController::class, 'show']);
        Route::put('/', [Api\ProfileController::class, 'update']);
        Route::post('/avatar', [Api\ProfileController::class, 'updateAvatar']);
        Route::put('/password', [Api\ProfileController::class, 'updatePassword']);
        Route::get('/notifications', [Api\ProfileController::class, 'notifications']);
        Route::put('/notifications/settings', [Api\ProfileController::class, 'updateNotificationSettings']);
    });
    
    // Buyer endpoints
    Route::prefix('buyer')->middleware('role:buyer')->group(function () {
        // RFQs
        Route::prefix('rfqs')->group(function () {
            Route::get('/', [Api\Buyer\RfqController::class, 'index']);
            Route::post('/', [Api\Buyer\RfqController::class, 'store']);
            Route::get('/{rfq}', [Api\Buyer\RfqController::class, 'show']);
            Route::put('/{rfq}', [Api\Buyer\RfqController::class, 'update']);
            Route::delete('/{rfq}', [Api\Buyer\RfqController::class, 'destroy']);
            Route::get('/{rfq}/quotes', [Api\Buyer\RfqController::class, 'quotes']);
            Route::post('/{rfq}/close', [Api\Buyer\RfqController::class, 'close']);
        });
        
        // Quotes
        Route::prefix('quotes')->group(function () {
            Route::get('/', [Api\Buyer\QuoteController::class, 'index']);
            Route::get('/{quote}', [Api\Buyer\QuoteController::class, 'show']);
            Route::post('/{quote}/accept', [Api\Buyer\QuoteController::class, 'accept']);
            Route::post('/{quote}/reject', [Api\Buyer\QuoteController::class, 'reject']);
            Route::post('/{quote}/negotiate', [Api\Buyer\QuoteController::class, 'negotiate']);
        });
        
        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [Api\Buyer\CartController::class, 'index']);
            Route::post('/add', [Api\Buyer\CartController::class, 'add']);
            Route::put('/update/{item}', [Api\Buyer\CartController::class, 'update']);
            Route::delete('/remove/{item}', [Api\Buyer\CartController::class, 'remove']);
            Route::delete('/clear', [Api\Buyer\CartController::class, 'clear']);
            Route::post('/checkout', [Api\Buyer\CartController::class, 'checkout']);
        });
        
        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [Api\Buyer\OrderController::class, 'index']);
            Route::get('/{order}', [Api\Buyer\OrderController::class, 'show']);
            Route::post('/{order}/cancel', [Api\Buyer\OrderController::class, 'cancel']);
            Route::post('/{order}/confirm-delivery', [Api\Buyer\OrderController::class, 'confirmDelivery']);
            Route::get('/{order}/tracking', [Api\Buyer\OrderController::class, 'tracking']);
            Route::get('/{order}/invoice', [Api\Buyer\OrderController::class, 'invoice']);
        });
        
        // Favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/products', [Api\Buyer\FavoriteController::class, 'products']);
            Route::get('/vendors', [Api\Buyer\FavoriteController::class, 'vendors']);
            Route::post('/products/{product}', [Api\Buyer\FavoriteController::class, 'addProduct']);
            Route::delete('/products/{product}', [Api\Buyer\FavoriteController::class, 'removeProduct']);
            Route::post('/vendors/{vendor}', [Api\Buyer\FavoriteController::class, 'addVendor']);
            Route::delete('/vendors/{vendor}', [Api\Buyer\FavoriteController::class, 'removeVendor']);
        });
    });
    
    // Vendor endpoints
    Route::prefix('vendor')->middleware('role:vendor')->group(function () {
        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [Api\Vendor\ProductController::class, 'index']);
            Route::post('/', [Api\Vendor\ProductController::class, 'store']);
            Route::get('/{product}', [Api\Vendor\ProductController::class, 'show']);
            Route::put('/{product}', [Api\Vendor\ProductController::class, 'update']);
            Route::delete('/{product}', [Api\Vendor\ProductController::class, 'destroy']);
            Route::post('/{product}/images', [Api\Vendor\ProductController::class, 'uploadImages']);
            Route::put('/{product}/inventory', [Api\Vendor\ProductController::class, 'updateInventory']);
        });
        
        // RFQ responses
        Route::prefix('rfqs')->group(function () {
            Route::get('/available', [Api\Vendor\RfqController::class, 'available']);
            Route::get('/responded', [Api\Vendor\RfqController::class, 'responded']);
            Route::get('/{rfq}', [Api\Vendor\RfqController::class, 'show']);
            Route::post('/{rfq}/quote', [Api\Vendor\RfqController::class, 'submitQuote']);
        });
        
        // Quotes
        Route::prefix('quotes')->group(function () {
            Route::get('/', [Api\Vendor\QuoteController::class, 'index']);
            Route::get('/{quote}', [Api\Vendor\QuoteController::class, 'show']);
            Route::put('/{quote}', [Api\Vendor\QuoteController::class, 'update']);
            Route::delete('/{quote}', [Api\Vendor\QuoteController::class, 'withdraw']);
        });
        
        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [Api\Vendor\OrderController::class, 'index']);
            Route::get('/{order}', [Api\Vendor\OrderController::class, 'show']);
            Route::put('/{order}/status', [Api\Vendor\OrderController::class, 'updateStatus']);
            Route::post('/{order}/ship', [Api\Vendor\OrderController::class, 'ship']);
            Route::get('/{order}/packing-slip', [Api\Vendor\OrderController::class, 'packingSlip']);
        });
        
        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [Api\Vendor\AnalyticsController::class, 'dashboard']);
            Route::get('/sales', [Api\Vendor\AnalyticsController::class, 'sales']);
            Route::get('/products', [Api\Vendor\AnalyticsController::class, 'products']);
            Route::get('/customers', [Api\Vendor\AnalyticsController::class, 'customers']);
        });
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [Api\NotificationController::class, 'index']);
        Route::get('/unread', [Api\NotificationController::class, 'unread']);
        Route::put('/{notification}/read', [Api\NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [Api\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [Api\NotificationController::class, 'destroy']);
    });
    
    // Payment methods
    Route::prefix('payment')->group(function () {
        Route::get('/methods', [Api\PaymentController::class, 'methods']);
        Route::post('/methods', [Api\PaymentController::class, 'addMethod']);
        Route::delete('/methods/{method}', [Api\PaymentController::class, 'removeMethod']);
        Route::put('/methods/{method}/default', [Api\PaymentController::class, 'setDefault']);
    });
    
    // Delivery addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [Api\AddressController::class, 'index']);
        Route::post('/', [Api\AddressController::class, 'store']);
        Route::put('/{address}', [Api\AddressController::class, 'update']);
        Route::delete('/{address}', [Api\AddressController::class, 'destroy']);
        Route::put('/{address}/default', [Api\AddressController::class, 'setDefault']);
    });
});

// Webhook endpoints
Route::prefix('webhooks')->group(function () {
    Route::post('/stripe', [Api\StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
    Route::post('/twilio', [Api\WebhookController::class, 'twilio']);
    Route::post('/wechat', [Api\WebhookController::class, 'wechat']);
});

// WhatsApp Business API integration
Route::prefix('whatsapp')->group(function () {
    Route::post('/webhook', [Api\WhatsAppController::class, 'webhook']);
    Route::get('/webhook', [Api\WhatsAppController::class, 'verify']);
});

// WeChat integration
Route::prefix('wechat')->group(function () {
    Route::any('/', [Api\WeChatController::class, 'serve']);
});

// Admin API endpoints
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [Api\Admin\DashboardController::class, 'stats']);
        Route::get('/charts', [Api\Admin\DashboardController::class, 'charts']);
        Route::get('/recent-activity', [Api\Admin\DashboardController::class, 'recentActivity']);
    });
    
    Route::prefix('users')->group(function () {
        Route::get('/', [Api\Admin\UserController::class, 'index']);
        Route::post('/', [Api\Admin\UserController::class, 'store']);
        Route::get('/{user}', [Api\Admin\UserController::class, 'show']);
        Route::put('/{user}', [Api\Admin\UserController::class, 'update']);
        Route::delete('/{user}', [Api\Admin\UserController::class, 'destroy']);
        Route::put('/{user}/status', [Api\Admin\UserController::class, 'updateStatus']);
    });
    
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [Api\Admin\ReportController::class, 'sales']);
        Route::get('/vendors', [Api\Admin\ReportController::class, 'vendors']);
        Route::get('/buyers', [Api\Admin\ReportController::class, 'buyers']);
        Route::get('/products', [Api\Admin\ReportController::class, 'products']);
        Route::post('/export', [Api\Admin\ReportController::class, 'export']);
    });
});

*/
// ABN Integration Test API (no CSRF required for API routes)
// Temporarily disabled - controller needs to be created
// Route::post('/test-abn-integration', [\App\Http\Controllers\TestABNIntegrationController::class, 'testIntegration'])->name('api.test-abn-integration');
// Route::get('/test-abn-quick-verify', [\App\Http\Controllers\TestABNIntegrationController::class, 'quickVerify'])->name('api.test-abn-quick-verify');

// Buyer quotes endpoint - using session auth
Route::get('/buyer/quotes', function(\Illuminate\Http\Request $request) {
    // Try to get buyer from session auth (not API auth)
    $buyer = \Illuminate\Support\Facades\Auth::guard('buyer')->user();

    if (!$buyer) {
        // If no auth, check if there's a buyer_id in session
        $buyerId = session('buyer_id');
        if ($buyerId) {
            $buyer = \App\Models\Buyer::find($buyerId);
        }
    }

    if (!$buyer) {
        return response()->json(['quotes' => [], 'message' => 'Not authenticated']);
    }

    $quotes = \App\Models\Quote::where('buyer_id', $buyer->id)
        ->where('status', 'submitted')
        ->with(['vendor', 'rfq'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json(['quotes' => $quotes]);
})->name('api.buyer.quotes');

// Messaging - Real-time chat for buyer-vendor communication (using session auth)
Route::prefix('messages')->group(function () {
    Route::post('/send', [Api\MessageController::class, 'sendMessage']);
    Route::get('/quote/{quoteId}', [Api\MessageController::class, 'getMessages']);
    Route::put('/{messageId}/read', [Api\MessageController::class, 'markAsRead']);
});

// Email availability check endpoint (no CSRF required for API routes)
Route::post('/buyer/check-email', function(\Illuminate\Http\Request $request) {
    $email = $request->input('email');
    
    if (!$email) {
        return response()->json(['available' => false, 'message' => 'Email is required'], 400);
    }
    
    try {
        $exists = \App\Models\Buyer::where('email', strtolower(trim($email)))->exists();
        
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Email is already registered' : 'Email is available'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'available' => true,
            'message' => 'Unable to verify'
        ]);
    }
})->name('api.buyer.check-email');

/*
|--------------------------------------------------------------------------
| Real-time Trading System API Routes
|--------------------------------------------------------------------------
|
| WebSocket-powered real-time trading routes for Sydney Markets
| Revolutionary Bloomberg Terminal-style interface for fresh produce
|
*/

// Real-time trading routes removed - controller was deleted in cleanup
/*
Route::prefix('realtime')->middleware('auth:buyer')->group(function () {
    // RFQ Management
    Route::post('/rfq/create', [App\Http\Controllers\Api\RealtimeTradingController::class, 'createRfq'])
        ->name('api.realtime.rfq.create');

    // Quote Management
    Route::post('/quotes/purchase', [App\Http\Controllers\Api\RealtimeTradingController::class, 'purchaseQuote'])
        ->name('api.realtime.quotes.purchase');

    // Chat/Messaging
    Route::get('/chat/messages', [App\Http\Controllers\Api\RealtimeTradingController::class, 'getChatMessages'])
        ->name('api.realtime.chat.messages');

    // Vendor Status
    Route::get('/vendors/online-count', [App\Http\Controllers\Api\RealtimeTradingController::class, 'getOnlineVendorCount'])
        ->name('api.realtime.vendors.online-count');

    Route::post('/vendors/availability', [App\Http\Controllers\Api\RealtimeTradingController::class, 'getVendorAvailability'])
        ->name('api.realtime.vendors.availability');
});

// Vendor real-time status routes
Route::prefix('vendor/realtime')->middleware('auth:vendor')->group(function () {
    Route::post('/status', [App\Http\Controllers\Api\RealtimeTradingController::class, 'updateVendorStatus'])
        ->name('api.vendor.realtime.status');
});
*/

/*
|--------------------------------------------------------------------------
| Advanced Analytics API Routes
|--------------------------------------------------------------------------
|
| Enterprise-level analytics endpoints for deep business intelligence,
| price analysis, vendor intelligence, and AI-powered recommendations
|
*/

// Analytics routes removed - controllers were deleted in cleanup

/*
|--------------------------------------------------------------------------
| File Upload and Data Processing API Routes
|--------------------------------------------------------------------------
|
| REAL data processing endpoints that actually read and analyze uploaded files
| NO FAKE DATA - processes actual sales transactions from user uploads
|
*/

// File upload routes removed - controller was deleted in cleanup
// Route::prefix('file-upload')->group(function () {
//     Route::post('/process', ...)
//     Route::get('/analytics-dashboard', ...)
// });

/*
|--------------------------------------------------------------------------
| Comprehensive Data Processing Engine API Routes
|--------------------------------------------------------------------------
|
| Advanced data processing engine that extracts EVERY piece of intelligence
| from real sales data including vendor analysis, price discrimination,
| customer patterns, and optimization opportunities
|
*/

// Data processing routes removed - controller was deleted in cleanup

/*
|--------------------------------------------------------------------------
| Pricing Intelligence API Routes - EXPOSING UNFAIR PRICING
|--------------------------------------------------------------------------
|
| MISSION: Detect price discrimination, expose vendor pricing games,
| identify immediate savings opportunities, and protect buyers from manipulation
|
*/

// Pricing Intelligence routes removed - controller was deleted in cleanup
/*
Route::prefix('pricing-intelligence')->middleware('auth:sanctum')->group(function () {
    // CRITICAL: Expose unfair pricing and discrimination
    Route::get('/unfair-pricing', [PricingIntelligenceController::class, 'getUnfairPricingExposure'])
        ->name('api.pricing-intelligence.unfair');

    // Real-time price tracking and alerts
    Route::get('/price-tracking', [PricingIntelligenceController::class, 'getPriceTracking'])
        ->name('api.pricing-intelligence.tracking');

    // Price variance and discrimination analysis
    Route::get('/price-variance', [PricingIntelligenceController::class, 'getPriceVarianceAnalysis'])
        ->name('api.pricing-intelligence.variance');

    // Market efficiency scoring
    Route::get('/market-efficiency', [PricingIntelligenceController::class, 'getMarketEfficiency'])
        ->name('api.pricing-intelligence.efficiency');

    // Competitive positioning analysis
    Route::get('/competitive-positioning', [PricingIntelligenceController::class, 'getCompetitivePositioning'])
        ->name('api.pricing-intelligence.positioning');

    // AI-powered price predictions
    Route::get('/price-predictions', [PricingIntelligenceController::class, 'getPricePredictions'])
        ->name('api.pricing-intelligence.predictions');

    // Price elasticity analysis
    Route::get('/price-elasticity', [PricingIntelligenceController::class, 'getPriceElasticity'])
        ->name('api.pricing-intelligence.elasticity');

    // Vendor comparison matrix
    Route::get('/vendor-comparison', [PricingIntelligenceController::class, 'getVendorComparison'])
        ->name('api.pricing-intelligence.vendor-comparison');
});
*/

/*
|--------------------------------------------------------------------------
| Product Intelligence API Routes
|--------------------------------------------------------------------------
|
| REAL PRODUCT INTELLIGENCE based on actual Sydney Markets data
| Analyzes SALAD MIX, CHILLI THAI GREEN, CHILLI SHISHITO, ICEBERG LETTUCE,
| TOMATO, and EGGS for revenue, price discrimination, margins, and opportunities
|
*/

// Product Intelligence routes removed - controller was deleted in cleanup
/*
Route::prefix('product-intelligence')->group(function () {
    // Comprehensive Product Intelligence Analysis
    Route::get('/', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getProductIntelligence'])
        ->name('api.product-intelligence.full');

    // Individual Analysis Components
    Route::get('/revenue-analysis', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getRevenueAnalysis'])
        ->name('api.product-intelligence.revenue');

    Route::get('/price-discrimination', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getPriceDiscriminationAnalysis'])
        ->name('api.product-intelligence.price-discrimination');

    Route::get('/purchasing-opportunities', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getPurchasingOpportunities'])
        ->name('api.product-intelligence.opportunities');

    Route::get('/performance-matrix', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getProductPerformanceMatrix'])
        ->name('api.product-intelligence.performance');

    Route::get('/margin-intelligence', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getMarginIntelligence'])
        ->name('api.product-intelligence.margins');

    Route::get('/pack-type-analysis', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getPackTypeAnalysis'])
        ->name('api.product-intelligence.pack-types');

    Route::get('/volume-analysis', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getVolumeAnalysis'])
        ->name('api.product-intelligence.volume');

    Route::get('/market-positioning', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getMarketPositioning'])
        ->name('api.product-intelligence.positioning');

    // Executive Summary for Business Intelligence
    Route::get('/executive-summary', [App\Http\Controllers\Api\ProductIntelligenceApiController::class, 'getExecutiveSummary'])
        ->name('api.product-intelligence.executive-summary');
});
*/

/*
|--------------------------------------------------------------------------
| Master Analytics Integration Routes - REAL-TIME DATA PROCESSING
|--------------------------------------------------------------------------
|
| Unified analytics platform integrating all specialized services:
| Real-Time Dashboard, Savings Detection, Market Timing, Group Buying, Competitive Intelligence
|
*/

// Master Analytics routes removed - controller references deleted services
// use App\Http\Controllers\Api\MasterAnalyticsController;
// Route::prefix('master-analytics')->middleware('auth:sanctum')->group(function () { ... });

/*
|--------------------------------------------------------------------------
| Real-Time Vendor Data API Routes
|--------------------------------------------------------------------------
|
| Serves actual vendor data from analytics engine to replace fake dashboard data
|
*/

// Real-time data routes removed - controller was deleted in cleanup
/*
Route::prefix('realtime-data')->group(function () {
    Route::get('/vendor-data', [App\Http\Controllers\Api\RealTimeDataController::class, 'getRealVendorData'])
        ->name('api.realtime-data.vendors');
    Route::get('/price-analysis', [App\Http\Controllers\Api\RealTimeDataController::class, 'getRealPriceAnalysis'])
        ->name('api.realtime-data.price');
    Route::get('/market-intelligence', [App\Http\Controllers\Api\RealTimeDataController::class, 'getRealMarketIntelligence'])
        ->name('api.realtime-data.market');
});
*/

/*
|--------------------------------------------------------------------------
| Advanced Intelligence API Routes
|--------------------------------------------------------------------------
|
| Next-generation predictive analytics, risk assessment, procurement strategy,
| market intelligence, and comprehensive business intelligence endpoints
|
*/

// use App\Http\Controllers\Api\AdvancedAnalyticsController; // Deleted in cleanup

// Advanced Analytics routes removed - controller was deleted in cleanup
/*
Route::prefix('advanced-analytics')->middleware('auth:sanctum')->group(function () {

    // Predictive Analytics
    Route::prefix('predictive')->group(function () {
        Route::get('/demand-forecast', [AdvancedAnalyticsController::class, 'getDemandForecast'])
            ->name('api.advanced.demand-forecast');
        Route::get('/price-predictions', [AdvancedAnalyticsController::class, 'getPricePredictions'])
            ->name('api.advanced.price-predictions');
        Route::get('/seasonal-analysis', [AdvancedAnalyticsController::class, 'getSeasonalAnalysis'])
            ->name('api.advanced.seasonal-analysis');
        Route::get('/procurement-optimization', [AdvancedAnalyticsController::class, 'getProcurementOptimization'])
            ->name('api.advanced.procurement-optimization');
    });

    // Risk Assessment
    Route::prefix('risk')->group(function () {
        Route::get('/vendor-concentration', [AdvancedAnalyticsController::class, 'getVendorRiskAnalysis'])
            ->name('api.advanced.vendor-risk');
        Route::get('/supply-chain-model', [AdvancedAnalyticsController::class, 'getSupplyChainRiskModel'])
            ->name('api.advanced.supply-chain-risk');
        Route::get('/financial-assessment', [AdvancedAnalyticsController::class, 'getFinancialRiskAssessment'])
            ->name('api.advanced.financial-risk');
        Route::get('/mitigation-strategies', [AdvancedAnalyticsController::class, 'getRiskMitigationStrategies'])
            ->name('api.advanced.risk-mitigation');
    });

    // Procurement Strategy
    Route::prefix('procurement-strategy')->group(function () {
        Route::get('/strategic-sourcing', [AdvancedAnalyticsController::class, 'getStrategicSourcing'])
            ->name('api.advanced.strategic-sourcing');
        Route::get('/negotiation-intelligence', [AdvancedAnalyticsController::class, 'getNegotiationIntelligence'])
            ->name('api.advanced.negotiation-intelligence');
        Route::get('/contract-optimization', [AdvancedAnalyticsController::class, 'getContractOptimization'])
            ->name('api.advanced.contract-optimization');
        Route::get('/scorecard', [AdvancedAnalyticsController::class, 'getProcurementScorecard'])
            ->name('api.advanced.procurement-scorecard');
    });

    // Market Intelligence
    Route::prefix('market-intelligence')->group(function () {
        Route::get('/trends', [AdvancedAnalyticsController::class, 'getMarketTrends'])
            ->name('api.advanced.market-trends');
        Route::get('/competitive-benchmarking', [AdvancedAnalyticsController::class, 'getCompetitiveBenchmarking'])
            ->name('api.advanced.competitive-benchmarking');
    });

    // Business Intelligence
    Route::prefix('business-intelligence')->group(function () {
        Route::get('/bi-architecture', [AdvancedAnalyticsController::class, 'getBIArchitecture'])
            ->name('api.advanced.bi-architecture');
        Route::get('/executive-dashboard', [AdvancedAnalyticsController::class, 'getExecutiveDashboard'])
            ->name('api.advanced.executive-dashboard');
        Route::get('/business-performance', [AdvancedAnalyticsController::class, 'getBusinessPerformance'])
            ->name('api.advanced.business-performance');
        Route::get('/strategic-planning', [AdvancedAnalyticsController::class, 'getStrategicPlanning'])
            ->name('api.advanced.strategic-planning');
    });

    // Comprehensive Summary
    Route::get('/comprehensive-summary', [AdvancedAnalyticsController::class, 'getComprehensiveSummary'])
        ->name('api.advanced.comprehensive-summary');
});
*/
