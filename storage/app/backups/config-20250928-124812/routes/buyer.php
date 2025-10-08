<?php

use Illuminate\Support\Facades\Route;
// Dashboard now uses Livewire components directly
use App\Buyer\Controllers\ProductController;
use App\Buyer\Controllers\OrderController;
use App\Buyer\Controllers\ProfileController;
use App\Buyer\Controllers\CartController;
use App\Buyer\Controllers\QuoteController;
use App\Buyer\Controllers\RFQController;
use App\Buyer\Controllers\BillingController;
// use App\Http\Controllers\Buyer\PaymentMethodController; // Removed
use App\Buyer\Controllers\InvoiceController;
// use App\Http\Controllers\Buyer\ScheduledPaymentController; // Removed
// use App\Http\Controllers\Buyer\AccountStatementController; // Removed
// use App\Http\Controllers\Buyer\PaymentDisputeController; // Removed
// use App\Http\Controllers\Buyer\FinancialReportController; // Deleted in cleanup
// use App\Http\Controllers\Buyer\DocumentController; // Deleted in cleanup
// use App\Http\Controllers\Buyer\DocumentFolderController; // Deleted in cleanup
// use App\Http\Controllers\Buyer\ContractController; // Removed
// use App\Http\Controllers\Buyer\DocumentSignatureController; // Removed in cleanup
// use App\Http\Controllers\Buyer\DocumentTemplateController; // Removed in cleanup
// use App\Http\Controllers\Buyer\DocumentApprovalController; // Removed in cleanup
// use App\Http\Controllers\Buyer\DocumentShareController; // Removed in cleanup
// use App\Http\Controllers\Buyer\DocumentTagController; // Removed in cleanup
// use App\Http\Controllers\Buyer\FinancialController; // Removed
// use App\Http\Controllers\Buyer\NotificationController; // Removed
// use App\Http\Controllers\Buyer\HelpController; // Removed
// use App\Http\Controllers\Buyer\CustomerIntelligenceController; // Deleted in cleanup
// use App\Http\Controllers\Buyer\SalesIntelligenceController; // Deleted in cleanup

/*
|--------------------------------------------------------------------------
| Buyer Routes - ESSENTIAL ONLY
|--------------------------------------------------------------------------
|
| Only the essential buyer routes for basic functionality.
| Keep it simple and functional.
|
*/

Route::prefix('buyer')->name('buyer.')->middleware(['auth:buyer'])->group(function () {

    // 1. Dashboard - Main buyer dashboard with Livewire architecture
    Route::get('/dashboard', function() {
        return view('buyer.dashboard');
    })->name('dashboard');

    // Dashboard API endpoints (can be converted to Livewire actions if needed)
    // Route::get('/dashboard/updates', [DashboardController::class, 'getUpdates'])->name('dashboard.updates');
    // Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    // Route::post('/dashboard/preferences', [DashboardController::class, 'updatePreferences'])->name('dashboard.preferences');
    // Route::get('/dashboard/chart/{chartType}', [DashboardController::class, 'getChartData'])->name('dashboard.chart');
    
    // Customer Intelligence - Deleted in cleanup
    /*
    Route::prefix('customer-intelligence')->name('customer-intelligence.')->group(function () {
        Route::get('/', [CustomerIntelligenceController::class, 'index'])->name('index');
        Route::get('/profile', [CustomerIntelligenceController::class, 'getCustomerProfile'])->name('profile');
        Route::get('/timeline', [CustomerIntelligenceController::class, 'getPriceTimeline'])->name('timeline');
    });
    */
    
    // Sales Intelligence - Deleted in cleanup
    /*
    Route::prefix('sales-intelligence')->name('sales-intelligence.')->group(function () {
        Route::get('/', [SalesIntelligenceController::class, 'index'])->name('index');
        Route::get('/product-intelligence', [SalesIntelligenceController::class, 'productIntelligence'])->name('product');
        Route::get('/price-alerts', [SalesIntelligenceController::class, 'getPriceAlerts'])->name('price-alerts');
        Route::get('/vendor-recommendations', [SalesIntelligenceController::class, 'getVendorRecommendations'])->name('vendor-recommendations');
        Route::post('/upload', [SalesIntelligenceController::class, 'uploadSalesData'])->name('upload');
        Route::get('/export', [SalesIntelligenceController::class, 'exportReport'])->name('export');
    });
    */
    
    // 2. Products - Browse products  
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    
    // 3. Orders - View orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    
    // 4. Profile - User profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    
    // 5. Settings - Account settings (CONTROLLER REMOVED)
    // Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    
    // 6. Logout - Logout functionality (handled by buyer-auth.php to avoid route conflicts)
    // Route removed to prevent duplicate route names with buyer-auth.php
    
    // 7. Update Activity - For session management
    Route::post('/update-activity', function () {
        $buyer = Auth::guard('buyer')->user();
        if ($buyer) {
            $buyer->last_activity_at = now();
            $buyer->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 401);
    })->name('update-activity');
    // Subscriptions temporarily disabled for deployment validation
    
    /* CONTROLLER REMOVED - OrderTemplateController
    // Order Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [OrderTemplateController::class, 'index'])->name('index');
        Route::get('/create', [OrderTemplateController::class, 'create'])->name('create');
        Route::post('/', [OrderTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [OrderTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [OrderTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [OrderTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [OrderTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/use', [OrderTemplateController::class, 'useTemplate'])->name('use');
    });
    */
    
    /* CONTROLLER REMOVED - ReturnController
    // Returns & Refunds
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [ReturnController::class, 'index'])->name('index');
        Route::get('/create', [ReturnController::class, 'create'])->name('create');
        Route::post('/', [ReturnController::class, 'store'])->name('store');
        Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
        Route::post('/{return}/cancel', [ReturnController::class, 'cancel'])->name('cancel');
        Route::post('/{return}/ship', [ReturnController::class, 'shipReturn'])->name('ship');
        Route::post('/{return}/images', [ReturnController::class, 'uploadImages'])->name('images');
    });
    */
    
    /* CONTROLLER REMOVED - CatalogController
    // Catalog System - Product Browsing and Search
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\CatalogController::class, 'index'])->name('index');
        Route::get('/search', [\App\Http\Controllers\Buyer\CatalogController::class, 'search'])->name('search');
        Route::get('/category/{category}', [\App\Http\Controllers\Buyer\CatalogController::class, 'category'])->name('category');

        // Product specific routes
        Route::prefix('product')->name('product.')->group(function () {
            Route::get('/{product}', [\App\Http\Controllers\Buyer\CatalogController::class, 'show'])->name('show');
            Route::get('/{product}/bulk-pricing', [\App\Http\Controllers\Buyer\CatalogController::class, 'bulkPricing'])->name('bulk-pricing');
            Route::post('/{product}/favorite', [\App\Http\Controllers\Buyer\CatalogController::class, 'toggleFavorite'])->name('favorite');

            // Wishlist management
            Route::prefix('wishlist')->name('wishlist.')->group(function () {
                Route::post('/{product}/add', [\App\Http\Controllers\Buyer\CatalogController::class, 'addToWishlist'])->name('add');
                Route::post('/{product}/remove', [\App\Http\Controllers\Buyer\CatalogController::class, 'removeFromWishlist'])->name('remove');
            });

            // Compare functionality
            Route::prefix('compare')->name('compare.')->group(function () {
                Route::post('/{product}/add', [\App\Http\Controllers\Buyer\CatalogController::class, 'addToCompare'])->name('add');
                Route::post('/{product}/remove', [\App\Http\Controllers\Buyer\CatalogController::class, 'removeFromCompare'])->name('remove');
            });
        });
    });
    */
    
    /* CONTROLLER REMOVED - CheckoutController
    // Checkout System
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\CheckoutController::class, 'index'])->name('index');
        Route::post('/process', [\App\Http\Controllers\Buyer\CheckoutController::class, 'process'])->name('process');
        Route::get('/success', [\App\Http\Controllers\Buyer\CheckoutController::class, 'success'])->name('success');
        Route::get('/cancel', [\App\Http\Controllers\Buyer\CheckoutController::class, 'cancel'])->name('cancel');
    });
    */
    
    /* CONTROLLER REMOVED - OrderAnalyticsController
    // Order Analytics - Legacy routes for backward compatibility
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/orders', [OrderAnalyticsController::class, 'index'])->name('orders.index');
        Route::get('/orders/export', [OrderAnalyticsController::class, 'export'])->name('orders.export');
    });
    */

    /* CONTROLLER REMOVED - AnalyticsController, AlertController, ReportScheduleController
    // Comprehensive Analytics System
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Main analytics dashboard
        Route::get('/', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'index'])->name('index');

        // Business Intelligence
        Route::get('/business-intelligence', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'businessIntelligence'])->name('business-intelligence');

        // Spending Analysis
        Route::get('/spending', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'spendingAnalysis'])->name('spending');

        // Vendor Performance
        Route::get('/vendor-performance', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'vendorPerformance'])->name('vendor-performance');

        // Order Trends
        Route::get('/order-trends', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'orderTrends'])->name('order-trends');

        // Product Performance
        Route::get('/product-performance', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'productPerformance'])->name('product-performance');

        // Forecasting
        Route::get('/forecasting', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'forecasting'])->name('forecasting');

        // Real-time Monitoring
        Route::get('/monitoring', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'monitoring'])->name('monitoring');

        // Insights
        Route::get('/insights', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'insights'])->name('insights');
        Route::patch('/insights/{uuid}/read', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'markInsightAsRead'])->name('insights.read');
        Route::patch('/insights/{uuid}/acted', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'markInsightAsActed'])->name('insights.acted');

        // Custom Report Builder
        Route::get('/report-builder', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'reportBuilder'])->name('report-builder');
        Route::post('/reports/generate', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'generateReport'])->name('reports.generate');

        // Reports Management
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'listReports'])->name('index');
            Route::get('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'showReport'])->name('show');
            Route::get('/{uuid}/export', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportReport'])->name('export');
            Route::post('/{uuid}/duplicate', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'duplicateReport'])->name('duplicate');
            Route::delete('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'deleteReport'])->name('delete');

            // Report Scheduling
            Route::post('/{uuid}/schedule', [\App\Http\Controllers\Buyer\ReportScheduleController::class, 'scheduleReport'])->name('schedule');
            Route::patch('/{uuid}/schedule', [\App\Http\Controllers\Buyer\ReportScheduleController::class, 'updateSchedule'])->name('schedule.update');
            Route::delete('/{uuid}/schedule', [\App\Http\Controllers\Buyer\ReportScheduleController::class, 'cancelSchedule'])->name('schedule.cancel');
        });

        // Data Export Routes - Removed (controller deleted in cleanup)
        // Route::prefix('export')->name('export.')->group(function () { ... });

        // Data Import Routes - Removed (controller deleted in cleanup)
        // Route::prefix('import')->name('import.')->group(function () { ... });

        // Alerts Management
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\AlertController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Buyer\AlertController::class, 'store'])->name('store');
            Route::get('/create', [\App\Http\Controllers\Buyer\AlertController::class, 'create'])->name('create');
            Route::get('/{uuid}', [\App\Http\Controllers\Buyer\AlertController::class, 'show'])->name('show');
            Route::get('/{uuid}/edit', [\App\Http\Controllers\Buyer\AlertController::class, 'edit'])->name('edit');
            Route::patch('/{uuid}', [\App\Http\Controllers\Buyer\AlertController::class, 'update'])->name('update');
            Route::delete('/{uuid}', [\App\Http\Controllers\Buyer\AlertController::class, 'destroy'])->name('destroy');
            Route::patch('/{uuid}/toggle', [\App\Http\Controllers\Buyer\AlertController::class, 'toggle'])->name('toggle');
        });

        // Widget API endpoints
        Route::prefix('widgets')->name('widgets.')->group(function () {
            Route::get('/data/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'widgetData'])->name('data');
            Route::post('/', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'createWidget'])->name('create');
            Route::patch('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'updateWidget'])->name('update');
            Route::delete('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'deleteWidget'])->name('delete');
            Route::patch('/{uuid}/position', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'updateWidgetPosition'])->name('position');
        });

        // Dashboard Management
        Route::prefix('dashboards')->name('dashboards.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'listDashboards'])->name('index');
            Route::post('/', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'createDashboard'])->name('create');
            Route::get('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'showDashboard'])->name('show');
            Route::patch('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'updateDashboard'])->name('update');
            Route::delete('/{uuid}', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'deleteDashboard'])->name('delete');
            Route::post('/{uuid}/duplicate', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'duplicateDashboard'])->name('duplicate');
            Route::patch('/{uuid}/default', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'setDefaultDashboard'])->name('default');
        });

        // Data Export endpoints
        Route::prefix('export')->name('export.')->group(function () {
            Route::post('/spending', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportSpending'])->name('spending');
            Route::post('/vendors', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportVendors'])->name('vendors');
            Route::post('/orders', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportOrders'])->name('orders');
            Route::post('/products', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportProducts'])->name('products');
            Route::post('/custom', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'exportCustom'])->name('custom');
        });

        // API endpoints for real-time data
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/metrics/current', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getCurrentMetrics'])->name('metrics.current');
            Route::get('/spending/trend', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getSpendingTrend'])->name('spending.trend');
            Route::get('/orders/volume', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getOrderVolume'])->name('orders.volume');
            Route::get('/vendors/performance', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getVendorPerformanceData'])->name('vendors.performance');
            Route::get('/forecasts/demand', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getDemandForecastData'])->name('forecasts.demand');
            Route::get('/alerts/active', [\App\Http\Controllers\Buyer\AnalyticsController::class, 'getActiveAlerts'])->name('alerts.active');
        });
    });
    */
    
    // RFQ (Request for Quote) Management
    Route::prefix('rfqs')->name('rfqs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\RFQController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Buyer\RFQController::class, 'create'])->name('create');
        Route::get('/manage', [\App\Http\Controllers\Buyer\RFQController::class, 'manage'])->name('manage'); // Added for sidebar navigation
        Route::post('/', [\App\Http\Controllers\Buyer\RFQController::class, 'store'])->name('store');
        Route::post('/quick', [\App\Http\Controllers\Buyer\RFQController::class, 'quickCreate'])->name('quick'); // Quick RFQ creation
        Route::get('/{rfq}', [\App\Http\Controllers\Buyer\RFQController::class, 'show'])->name('show');
        Route::get('/{rfq}/edit', [\App\Http\Controllers\Buyer\RFQController::class, 'edit'])->name('edit');
        Route::put('/{rfq}', [\App\Http\Controllers\Buyer\RFQController::class, 'update'])->name('update');
        Route::post('/{rfq}/close', [\App\Http\Controllers\Buyer\RFQController::class, 'close'])->name('close');
        Route::post('/{rfq}/extend', [\App\Http\Controllers\Buyer\RFQController::class, 'extend'])->name('extend');
        Route::get('/{rfq}/download', [\App\Http\Controllers\Buyer\RFQController::class, 'download'])->name('download');
        Route::post('/{rfq}/quotes/{quoteId}/accept', [\App\Http\Controllers\Buyer\RFQController::class, 'acceptQuote'])->name('quote.accept');
    });
    
    // Quotes Management (separate from RFQ for navigation)
    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/review', [QuoteController::class, 'review'])->name('review'); // For sidebar navigation
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::get('/{id}', [QuoteController::class, 'show'])->name('show');
        Route::post('/{id}/accept', [QuoteController::class, 'accept'])->name('accept');
        Route::post('/{id}/reject', [QuoteController::class, 'reject'])->name('reject');
        Route::get('/compare', [QuoteController::class, 'compare'])->name('compare-quotes');
        
        /* CONTROLLER REMOVED - QuoteAcceptanceController
        // Quote Acceptance with Billing Integration
        Route::get('/{quote}/accept-billing', [QuoteAcceptanceController::class, 'show'])->name('accept-billing');
        Route::post('/{quote}/accept-billing', [QuoteAcceptanceController::class, 'accept'])->name('accept-billing.process');
        Route::get('/{quote}/preview-invoice', [QuoteAcceptanceController::class, 'previewInvoice'])->name('preview-invoice');
        Route::get('/{quote}/download', [QuoteAcceptanceController::class, 'downloadQuote'])->name('download');
        */
    });
    
    /* CONTROLLER REMOVED - QuoteAcceptanceController
    // Billing Management
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/confirm/{order}', [QuoteAcceptanceController::class, 'confirmBilling'])->name('confirm');
        Route::get('/return/{order}', [QuoteAcceptanceController::class, 'paymentReturn'])->name('return');
        Route::post('/cancel/{order}', [QuoteAcceptanceController::class, 'cancelPayment'])->name('cancel');
    });
    */
    
    // Product Browsing
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/favorites', [ProductController::class, 'favorites'])->name('favorites');
        Route::get('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::post('/{product}/compare', [ProductController::class, 'compare'])->name('compare-products');
        Route::post('/{product}/favorite', [ProductController::class, 'favorite'])->name('favorite');
    });
    
    /* CONTROLLER REMOVED - CatalogController
    // Catalog Management
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/browse', [CatalogController::class, 'browse'])->name('browse');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/compare', [CatalogController::class, 'compare'])->name('compare');
        Route::post('/compare/add/{product}', [CatalogController::class, 'addToCompare'])->name('compare.add');
        Route::post('/compare/remove/{product}', [CatalogController::class, 'removeFromCompare'])->name('compare.remove');
        Route::post('/compare/clear', [CatalogController::class, 'clearCompare'])->name('compare.clear');
    });
    */
    
    /* CONTROLLER REMOVED - WishlistController
    // Wishlist Management
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/add/{product}', [WishlistController::class, 'add'])->name('add');
        Route::delete('/remove/{product}', [WishlistController::class, 'remove'])->name('remove');
        Route::post('/move-to-cart/{wishlistItem}', [WishlistController::class, 'moveToCart'])->name('move-to-cart');
        Route::post('/move-all-to-cart', [WishlistController::class, 'moveAllToCart'])->name('move-all-to-cart');
        Route::post('/share', [WishlistController::class, 'share'])->name('share');
        Route::get('/shared/{token}', [WishlistController::class, 'viewShared'])->name('shared');
        Route::post('/price-alert/{product}', [WishlistController::class, 'setPriceAlert'])->name('price-alert');
    });
    */
    
    // Shopping Cart - Enhanced B2B Features
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add', [CartController::class, 'add'])->name('add'); // Quick add to cart
        Route::patch('/update/{item}', [CartController::class, 'update'])->name('update'); // Update cart item
        Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout'); // Proceed to checkout
        Route::get('/count', [CartController::class, 'count'])->name('count'); // Get cart item count
        Route::post('/items', [CartController::class, 'addItem'])->name('add-item');
        Route::patch('/items/{item}', [CartController::class, 'updateItem'])->name('update-item');
        Route::delete('/items/{item}', [CartController::class, 'removeItem'])->name('remove-item');
        Route::post('/clear', [CartController::class, 'clearCart'])->name('clear');
        
        // Coupons and Promotions
        Route::post('/coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
        Route::delete('/coupon/{coupon}', [CartController::class, 'removeCoupon'])->name('remove-coupon');
        
        // Shipping
        Route::post('/shipping/calculate', [CartController::class, 'getShippingOptions'])->name('shipping-calculate');
        Route::post('/shipping/set', [CartController::class, 'setShippingMethod'])->name('shipping-set');
        
        // Save and Share
        Route::post('/save', [CartController::class, 'saveForLater'])->name('save');
        Route::get('/saved/{savedCart}/load', [CartController::class, 'loadSavedCart'])->name('load-saved');
        Route::post('/share', [CartController::class, 'shareCart'])->name('share');
        Route::get('/shared/{token}', [CartController::class, 'accessSharedCart'])->name('shared');
        
        // Quick Actions
        Route::post('/reorder/{order}', [CartController::class, 'quickReorder'])->name('reorder');
        Route::get('/quick-checkout', [CartController::class, 'quickCheckout'])->name('quick-checkout');
        
        // API Endpoints
        Route::get('/summary', [CartController::class, 'mobileSummary'])->name('summary');
        Route::get('/stats', [CartController::class, 'getStats'])->name('stats');
    });
    
    /* CONTROLLER REMOVED - VendorManagementController, VendorAnalyticsController, VendorOnboardingController
    // Vendor Management System - Complete Routes
    Route::prefix('vendors')->name('vendors.')->group(function () {
        // Main vendor routes - ALL FIXED
        Route::get('/', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'index'])->name('index');
        Route::get('/directory', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'directory'])->name('directory'); // FIXED: For sidebar navigation
        Route::get('/favorites', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'favorites'])->name('favorites'); // FIXED: Favorite vendors list
        Route::get('/compare', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'compare'])->name('compare');
        Route::post('/compare/save', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'saveComparison'])->name('compare.save');

        // Individual vendor routes - SHOW ROUTE FIXED
        Route::get('/{id}', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'show'])->name('show'); // FIXED: View specific vendor
        Route::post('/{id}/favorite', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'toggleFavorite'])->name('favorite');
        Route::get('/{id}/products', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'products'])->name('products');
        Route::get('/{id}/reviews', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'reviews'])->name('reviews');
        Route::post('/{id}/reviews', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'submitReview'])->name('reviews.submit');
        Route::get('/{id}/certifications', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'certifications'])->name('certifications');
        Route::get('/{id}/compliance', [\App\Http\Controllers\Buyer\VendorManagementController::class, 'compliance'])->name('compliance');

        // Analytics routes
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\VendorAnalyticsController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Buyer\VendorAnalyticsController::class, 'show'])->name('show');
            Route::get('/compare', [\App\Http\Controllers\Buyer\VendorAnalyticsController::class, 'compare'])->name('compare');
            Route::get('/export', [\App\Http\Controllers\Buyer\VendorAnalyticsController::class, 'export'])->name('export');
        });

        // Onboarding routes
        Route::prefix('onboarding')->name('onboarding.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'store'])->name('store');
            Route::get('/{id}/status', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'status'])->name('status');
            Route::put('/{id}', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Buyer\VendorOnboardingController::class, 'cancel'])->name('cancel');
        });
    });
    */
    
    /* CONTROLLER REMOVED - SupplierController
    // Supplier Management System - NEW COMPLETE ROUTES
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        // Main supplier routes - ALL IMPLEMENTED
        Route::get('/browse', [\App\Http\Controllers\Buyer\SupplierController::class, 'browse'])->name('browse'); // FIXED: Browse suppliers
        Route::get('/compare', [\App\Http\Controllers\Buyer\SupplierController::class, 'compare'])->name('compare'); // FIXED: Compare suppliers
        Route::post('/compare/save', [\App\Http\Controllers\Buyer\SupplierController::class, 'saveComparison'])->name('compare.save');

        // Individual supplier routes
        Route::get('/{id}', [\App\Http\Controllers\Buyer\SupplierController::class, 'show'])->name('show');
        Route::post('/{id}/favorite', [\App\Http\Controllers\Buyer\SupplierController::class, 'favorite'])->name('favorite'); // FIXED: Add to favorites
        Route::get('/{id}/reviews', [\App\Http\Controllers\Buyer\SupplierController::class, 'reviews'])->name('reviews'); // FIXED: View reviews
        Route::post('/{id}/reviews', [\App\Http\Controllers\Buyer\SupplierController::class, 'submitReview'])->name('reviews.submit');
        Route::get('/{id}/catalog', [\App\Http\Controllers\Buyer\SupplierController::class, 'catalog'])->name('catalog');
    });
    */
    
    /* CONTROLLER REMOVED - VendorMessagingController
    // Vendor Messaging System
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'reply'])->name('reply');
        Route::post('/{id}/close', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'close'])->name('close');
        Route::post('/{id}/read', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/{messageId}/attachment/{index}', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'downloadAttachment'])->name('attachment');
        Route::delete('/{id}', [\App\Http\Controllers\Buyer\VendorMessagingController::class, 'destroy'])->name('destroy');
    });
    */
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::get('/addresses', [ProfileController::class, 'addresses'])->name('addresses');
        Route::post('/addresses', [ProfileController::class, 'addAddress'])->name('addresses.add');
        Route::put('/addresses/{address}', [ProfileController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{address}', [ProfileController::class, 'deleteAddress'])->name('addresses.delete');
        Route::get('/payment-methods', [ProfileController::class, 'paymentMethods'])->name('payment');
        Route::post('/payment-methods', [ProfileController::class, 'addPaymentMethod'])->name('payment.add');
        Route::delete('/payment-methods/{method}', [ProfileController::class, 'deletePaymentMethod'])->name('payment.delete');
    });
    
    // Order Approval Workflow (for buyers with approval requirements)
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [OrderController::class, 'pendingApprovals'])->name('index');
        Route::post('/{approval}/approve', [OrderController::class, 'approveOrder'])->name('approve');
        Route::post('/{approval}/reject', [OrderController::class, 'rejectOrder'])->name('reject');
    });

    // Billing and Payment System
    Route::prefix('billing')->name('billing.')->group(function () {
        // Main billing dashboard
        Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'index'])->name('index');
        
        /* CONTROLLER REMOVED - PaymentMethodController
        // Payment Methods
        Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'store'])->name('store');
            Route::get('/{paymentMethod}', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'show'])->name('show');
            Route::get('/{paymentMethod}/edit', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'edit'])->name('edit');
            Route::put('/{paymentMethod}', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'update'])->name('update');
            Route::delete('/{paymentMethod}', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'destroy'])->name('destroy');
            Route::patch('/{paymentMethod}/set-default', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'setDefault'])->name('set-default');
            Route::patch('/{paymentMethod}/verify', [\App\Http\Controllers\Buyer\PaymentMethodController::class, 'verify'])->name('verify');
        });
        */
        
        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'invoices'])->name('index');
            Route::get('/{invoice}', [\App\Http\Controllers\Buyer\InvoiceController::class, 'show'])->name('show');
            Route::post('/{invoice}/pay', [\App\Http\Controllers\Buyer\BillingController::class, 'processPayment'])->name('pay');
            Route::get('/{invoice}/download', [\App\Http\Controllers\Buyer\BillingController::class, 'downloadInvoice'])->name('download');
        });
        
        // Payment History & Transactions
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'payments'])->name('index');
            Route::get('/{transaction}/receipt', [\App\Http\Controllers\Buyer\BillingController::class, 'downloadReceipt'])->name('receipt');
        });
        
        /* CONTROLLER REMOVED - ScheduledPaymentController
        // Scheduled Payments
        Route::prefix('scheduled-payments')->name('scheduled-payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'store'])->name('store');
            Route::get('/{scheduledPayment}', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'show'])->name('show');
            Route::get('/{scheduledPayment}/edit', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'edit'])->name('edit');
            Route::put('/{scheduledPayment}', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'update'])->name('update');
            Route::patch('/{scheduledPayment}/pause', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'pause'])->name('pause');
            Route::patch('/{scheduledPayment}/resume', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'resume'])->name('resume');
            Route::patch('/{scheduledPayment}/cancel', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'cancel'])->name('cancel');
            Route::delete('/{scheduledPayment}', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'destroy'])->name('destroy');
            Route::post('/preview', [\App\Http\Controllers\Buyer\ScheduledPaymentController::class, 'preview'])->name('preview');
        });
        */
        
        /* CONTROLLER REMOVED - AccountStatementController
        // Account Statements
        Route::prefix('statements')->name('statements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'statements'])->name('index');
            Route::get('/{statement}', [\App\Http\Controllers\Buyer\AccountStatementController::class, 'show'])->name('show');
            Route::get('/{statement}/download', [\App\Http\Controllers\Buyer\AccountStatementController::class, 'download'])->name('download');
        });
        */
        
        /* CONTROLLER REMOVED - PaymentDisputeController
        // Payment Disputes
        Route::prefix('disputes')->name('disputes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'disputes'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Buyer\PaymentDisputeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Buyer\PaymentDisputeController::class, 'store'])->name('store');
            Route::get('/{dispute}', [\App\Http\Controllers\Buyer\PaymentDisputeController::class, 'show'])->name('show');
        });
        */
        
        /* CONTROLLER REMOVED - FinancialReportController
        // Financial Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\BillingController::class, 'reports'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Buyer\FinancialReportController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Buyer\FinancialReportController::class, 'store'])->name('store');
            Route::get('/{report}', [\App\Http\Controllers\Buyer\FinancialReportController::class, 'show'])->name('show');
            Route::get('/{report}/download', [\App\Http\Controllers\Buyer\FinancialReportController::class, 'download'])->name('download');
        });
        */
    });
    
    /* CONTROLLER REMOVED - CommunicationController
    // Communication System
    Route::prefix('communication')->name('communication.')->group(function () {
        // Main communication center
        Route::get('/', [CommunicationController::class, 'index'])->name('index');

        // Direct Messaging
        Route::get('/messages', [CommunicationController::class, 'directMessages'])->name('direct-messages');
        Route::post('/messages/start', [CommunicationController::class, 'startVendorChat'])->name('start-vendor-chat');

        // Live Chat Support
        Route::get('/support', [CommunicationController::class, 'supportChat'])->name('support');
        Route::post('/support/start', [CommunicationController::class, 'startSupportChat'])->name('start-support-chat');

        // Video Calls
        Route::get('/video-calls', [CommunicationController::class, 'videoCalls'])->name('video-calls');
        Route::post('/video-calls/schedule', [CommunicationController::class, 'scheduleVendorMeeting'])->name('schedule-meeting');

        // Forums
        Route::get('/forums', [CommunicationController::class, 'forums'])->name('forums');

        // Document Sharing
        Route::get('/documents', [CommunicationController::class, 'documents'])->name('documents');
        Route::post('/documents/upload', [CommunicationController::class, 'uploadDocument'])->name('upload-document');

        // Message Templates
        Route::get('/templates', [CommunicationController::class, 'templates'])->name('templates');

        // Quick Replies
        Route::get('/quick-replies', [CommunicationController::class, 'quickReplies'])->name('quick-replies');

        // Analytics
        Route::get('/analytics', [CommunicationController::class, 'analytics'])->name('analytics');

        // Chat Interface
        Route::get('/chat/{channel}', function ($channelId) {
            return view('buyer.communication.chat', compact('channelId'));
        })->name('chat');

        // Message Management APIs
        Route::get('/messages/{channel}', [CommunicationController::class, 'getMessages'])->name('get-messages');
        Route::post('/messages/send', [CommunicationController::class, 'sendMessage'])->name('send-message');
        Route::post('/messages/react', [CommunicationController::class, 'reactToMessage'])->name('react-message');
        Route::get('/messages/search', [CommunicationController::class, 'searchMessages'])->name('search-messages');
    });
    */
    
    // Document Management System - Removed (controller deleted in cleanup)
    // Route::prefix('documents')->name('documents.')->group(function () { ... });
    
    // Document Folders - Removed (controller deleted in cleanup)
    // Route::prefix('folders')->name('folders.')->group(function () { ... });
    
    /* CONTROLLER REMOVED - ContractController
    // Contract Management
    Route::prefix('contracts')->name('contracts.')->group(function () {
        // Contract CRUD
        Route::get('/', [\App\Http\Controllers\Buyer\ContractController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Buyer\ContractController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Buyer\ContractController::class, 'store'])->name('store');
        Route::get('/{contract}', [\App\Http\Controllers\Buyer\ContractController::class, 'show'])->name('show');
        Route::get('/{contract}/edit', [\App\Http\Controllers\Buyer\ContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [\App\Http\Controllers\Buyer\ContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [\App\Http\Controllers\Buyer\ContractController::class, 'destroy'])->name('destroy');

        // Contract actions
        Route::get('/{contract}/sign', [\App\Http\Controllers\Buyer\ContractController::class, 'sign'])->name('sign');
        Route::post('/{contract}/sign', [\App\Http\Controllers\Buyer\ContractController::class, 'storeSignature'])->name('store-signature');
        Route::get('/{contract}/renew', [\App\Http\Controllers\Buyer\ContractController::class, 'renew'])->name('renew');
        Route::post('/{contract}/renew', [\App\Http\Controllers\Buyer\ContractController::class, 'storeRenewal'])->name('store-renewal');
        Route::post('/{contract}/terminate', [\App\Http\Controllers\Buyer\ContractController::class, 'terminate'])->name('terminate');

        // Contract performance
        Route::get('/{contract}/performance', [\App\Http\Controllers\Buyer\ContractController::class, 'performance'])->name('performance');
        Route::post('/{contract}/performance/metrics', [\App\Http\Controllers\Buyer\ContractController::class, 'addPerformanceMetric'])->name('performance.metrics');
    });
    */
    
    // Document Signatures - Temporarily disabled (controller removed)
    /*
    Route::prefix('signatures')->name('signatures.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'index'])->name('index');
        Route::get('/analytics', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'analytics'])->name('analytics');
        Route::get('/{document}/create', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'create'])->name('create');
        Route::post('/{document}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'store'])->name('store');
        Route::get('/{signature}', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'show'])->name('show');

        // Signature actions
        Route::post('/{signature}/send', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'send'])->name('send');
        Route::post('/{signature}/remind', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'remind'])->name('remind');
        Route::post('/{signature}/extend', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'extend'])->name('extend');
        Route::post('/{signature}/cancel', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'cancel'])->name('cancel');
        Route::get('/{signature}/download', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'download'])->name('download');

        // Bulk actions
        Route::post('/bulk-remind', [\App\Http\Controllers\Buyer\DocumentSignatureController::class, 'bulkRemind'])->name('bulk-remind');
    });
    */
    
    /* CONTROLLER REMOVED - DocumentTemplateController
    // Document Templates
    Route::prefix('document-templates')->name('document-templates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'destroy'])->name('destroy');

        // Template actions
        Route::post('/{template}/generate', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'generateDocument'])->name('generate');
        Route::post('/{template}/duplicate', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'duplicate'])->name('duplicate');
        Route::get('/{template}/preview', [\App\Http\Controllers\Buyer\DocumentTemplateController::class, 'preview'])->name('preview');
    });
    */
    
    /* CONTROLLER REMOVED - DocumentApprovalController
    // Document Approvals
    Route::prefix('document-approvals')->name('document-approvals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'index'])->name('index');
        Route::get('/{document}/create', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'create'])->name('create');
        Route::post('/{document}', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'store'])->name('store');
        Route::get('/{approval}', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'show'])->name('show');

        // Approval actions
        Route::post('/{approval}/approve', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'approve'])->name('approve');
        Route::post('/{approval}/reject', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'reject'])->name('reject');
        Route::post('/{approval}/delegate', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'delegate'])->name('delegate');
        Route::post('/{approval}/remind', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'remind'])->name('remind');
        Route::post('/{approval}/extend', [\App\Http\Controllers\Buyer\DocumentApprovalController::class, 'extend'])->name('extend');
    });
    */
    
    /* CONTROLLER REMOVED - DocumentShareController
    // Document Sharing
    Route::prefix('shares')->name('shares.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'index'])->name('index');
        Route::get('/{document}/create', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'create'])->name('create');
        Route::post('/{document}', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'store'])->name('store');
        Route::get('/{share}', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'show'])->name('show');
        Route::put('/{share}', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'update'])->name('update');
        Route::delete('/{share}', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'destroy'])->name('destroy');

        // Share actions
        Route::post('/{share}/extend', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'extend'])->name('extend');
        Route::post('/{share}/revoke', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'revoke'])->name('revoke');
        Route::post('/{share}/regenerate-token', [\App\Http\Controllers\Buyer\DocumentShareController::class, 'regenerateToken'])->name('regenerate-token');
    });
    */
    
    /* CONTROLLER REMOVED - DocumentTagController
    // Document Tags
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\DocumentTagController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Buyer\DocumentTagController::class, 'store'])->name('store');
        Route::put('/{tag}', [\App\Http\Controllers\Buyer\DocumentTagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [\App\Http\Controllers\Buyer\DocumentTagController::class, 'destroy'])->name('destroy');
        Route::get('/suggestions', [\App\Http\Controllers\Buyer\DocumentTagController::class, 'suggestions'])->name('suggestions');
    });
    */
    
    /* CONTROLLER REMOVED - FinancialController
    // Financial Management (for sidebar navigation)
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/spending', [\App\Http\Controllers\Buyer\FinancialController::class, 'spending'])->name('spending');
        Route::get('/invoices', [\App\Http\Controllers\Buyer\FinancialController::class, 'invoices'])->name('invoices');
        Route::get('/payments', [\App\Http\Controllers\Buyer\FinancialController::class, 'payments'])->name('payments');
    });
    */
    
    /* CONTROLLER REMOVED - SettingsController
    // Settings Management
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\SettingsController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Buyer\SettingsController::class, 'update'])->name('update');
    });
    */
    
    // Account Management
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        Route::put('/security', [ProfileController::class, 'updateSecurity'])->name('security.update');
        Route::get('/preferences', [ProfileController::class, 'preferences'])->name('preferences');
        Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/notifications', [ProfileController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [ProfileController::class, 'updateNotifications'])->name('notifications.update');
    });
    
    // Legacy route redirects for backward compatibility
    Route::get('/settings', function() {
        return redirect()->route('buyer.settings.index');
    })->name('settings');
    Route::get('/profile', function() {
        return redirect()->route('buyer.profile.index');
    })->name('profile');
    // Note: logout route is already defined in web.php under auth/buyer routes
    
    /* CONTROLLER REMOVED - NotificationController
    // Notifications System
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buyer\NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [\App\Http\Controllers\Buyer\NotificationController::class, 'markAllRead'])->name('mark-all-read');

        // Preferences
        Route::prefix('preferences')->name('preferences.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Buyer\NotificationController::class, 'preferences'])->name('index');
            Route::post('/update', [\App\Http\Controllers\Buyer\NotificationController::class, 'updatePreferences'])->name('update');
            Route::post('/reset', [\App\Http\Controllers\Buyer\NotificationController::class, 'resetPreferences'])->name('reset');
        });

        // API endpoints
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/count', [\App\Http\Controllers\Buyer\NotificationController::class, 'getCount'])->name('count');
            Route::get('/recent', [\App\Http\Controllers\Buyer\NotificationController::class, 'getRecent'])->name('recent');
        });
    });
    */
    
    /* CONTROLLER REMOVED - HelpController
    // Help & Support System
    Route::prefix('help')->name('help.')->group(function () {
        Route::get('/documentation', [\App\Http\Controllers\Buyer\HelpController::class, 'documentation'])->name('documentation');
        Route::get('/tutorials', [\App\Http\Controllers\Buyer\HelpController::class, 'tutorials'])->name('tutorials');
        Route::get('/faq', [\App\Http\Controllers\Buyer\HelpController::class, 'faq'])->name('faq');
        Route::get('/contact', [\App\Http\Controllers\Buyer\HelpController::class, 'contact'])->name('contact');
    });
    */

    // Analytics Dashboard Route
    Route::get('/analytics', function() {
        return view('buyer.analytics-dashboard');
    })->name('analytics.dashboard');

    // Settings Route (missing)
    Route::get('/settings', function() {
        return redirect('/buyer/profile'); // Redirect to existing profile page
    })->name('settings');
});