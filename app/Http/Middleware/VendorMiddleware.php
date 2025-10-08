<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('vendor')->check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login as vendor.',
                ], 401);
            }

            return redirect()->route('vendor.login')
                ->with('error', 'You must be logged in as a vendor to access this page.');
        }

        $vendor = Auth::guard('vendor')->user();

        // TEMPORARILY DISABLED - Email verification check
        // if (!$vendor->email_verified_at) {
        //     if ($request->wantsJson()) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Your email address is not verified.',
        //             'requires_verification' => true
        //         ], 403);
        //     }

        //     Auth::guard('vendor')->logout();
        //     return redirect()->route('vendor.login')
        //         ->with('warning', 'Please verify your email address before accessing this page.');
        // }

        // TEMPORARILY DISABLED - Account status check
        // if ($vendor->status !== 'active') {
        //     Auth::guard('vendor')->logout();

        //     if ($request->wantsJson()) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Your account is ' . $vendor->status . '. Please contact support.'
        //         ], 403);
        //     }

        //     $message = match($vendor->status) {
        //         'suspended' => 'Your account has been suspended. Reason: ' . ($vendor->suspension_reason ?? 'Contact support for details.'),
        //         'pending' => 'Your account is pending approval. Please wait for admin verification.',
        //         'inactive' => 'Your account is inactive. Please contact support to reactivate.',
        //         default => 'Your account is not active. Please contact support.'
        //     };

        //     return redirect()->route('vendor.login')
        //         ->with('error', $message);
        // }

        // Check for subscription status (if applicable)
        if ($vendor->subscription_expires_at && $vendor->subscription_expires_at < now()) {
            if (! $request->is('vendor/subscription/*')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your subscription has expired. Please renew to continue.',
                        'redirect' => route('vendor.subscription'),
                    ], 402);
                }

                return redirect()->route('vendor.subscription')
                    ->with('warning', 'Your subscription has expired. Please renew to continue.');
            }
        }

        // Check for API token authentication if it's an API request
        if ($request->is('api/*')) {
            $token = $request->bearerToken() ?? $request->header('X-API-Token');

            if ($token) {
                $authService = app(\App\Services\AuthService::class);
                $vendor = $authService->validateApiToken($token, 'vendor');

                if (! $vendor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired API token.',
                    ], 401);
                }

                // Set the authenticated vendor for this request
                Auth::guard('vendor')->setUser($vendor);
            }
        }

        // Update last activity
        $vendor->last_activity_at = now();
        $vendor->save();

        return $next($request);
    }
}
