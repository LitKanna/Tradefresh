<?php

namespace App\Http\Middleware;

use App\Services\VendorTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackVendorActivity
{
    /**
     * The vendor tracking service instance
     */
    protected VendorTrackingService $trackingService;

    /**
     * Create a new middleware instance
     */
    public function __construct(VendorTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if vendor is authenticated
        if (Auth::guard('vendor')->check()) {
            $vendor = Auth::guard('vendor')->user();
            
            // Track activity (throttled internally)
            $this->trackingService->trackActivity($vendor, $this->getActivityType($request));
            
            // Process heartbeat if it's a heartbeat request
            if ($request->is('api/vendor/heartbeat') || $request->is('vendor/heartbeat')) {
                $this->trackingService->processHeartbeat($vendor);
            }
        }

        return $next($request);
    }

    /**
     * Determine activity type based on request
     */
    private function getActivityType(Request $request): string
    {
        if ($request->isMethod('POST')) {
            if ($request->is('*/quote*')) return 'quote_submission';
            if ($request->is('*/product*')) return 'product_update';
            if ($request->is('*/order*')) return 'order_management';
            return 'data_submission';
        }
        
        if ($request->is('*/dashboard*')) return 'dashboard_view';
        if ($request->is('*/rfq*')) return 'rfq_view';
        if ($request->is('*/orders*')) return 'orders_view';
        
        return 'page_view';
    }
}