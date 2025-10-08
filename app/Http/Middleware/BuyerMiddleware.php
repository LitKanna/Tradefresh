<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('buyer')->check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login as buyer.'
                ], 401);
            }

            return redirect()->route('buyer.login')
                ->with('error', 'You must be logged in as a buyer to access this page.');
        }

        $buyer = Auth::guard('buyer')->user();

        // Check if email is verified
        if (!$buyer->email_verified_at) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your email address is not verified.',
                    'requires_verification' => true
                ], 403);
            }

            Auth::guard('buyer')->logout();
            return redirect()->route('buyer.login')
                ->with('warning', 'Please verify your email address before accessing this page.');
        }

        // Check if account is active
        if ($buyer->status !== 'active') {
            Auth::guard('buyer')->logout();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is ' . $buyer->status . '. Please contact support.'
                ], 403);
            }

            $message = match($buyer->status) {
                'suspended' => 'Your account has been suspended. Reason: ' . ($buyer->suspension_reason ?? 'Contact support for details.'),
                'pending' => 'Your account is pending verification. Our team is reviewing your application.',
                'inactive' => 'Your account is inactive. Please contact support to reactivate.',
                default => 'Your account is not active. Please contact support.'
            };

            return redirect()->route('buyer.login')
                ->with('error', $message);
        }

        // Check credit limit (if applicable)
        if ($buyer->credit_limit !== null && $buyer->current_credit >= $buyer->credit_limit) {
            if ($request->is(['buyer/checkout/*', 'buyer/orders/create'])) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Credit limit exceeded. Please make a payment to continue.',
                        'redirect' => route('buyer.payments')
                    ], 402);
                }

                return redirect()->route('buyer.payments')
                    ->with('warning', 'You have reached your credit limit. Please make a payment to continue purchasing.');
            }
        }

        // Check for API token authentication if it's an API request
        if ($request->is('api/*')) {
            $token = $request->bearerToken() ?? $request->header('X-API-Token');
            
            if ($token) {
                $authService = app(\App\Services\AuthService::class);
                $buyer = $authService->validateApiToken($token, 'buyer');
                
                if (!$buyer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired API token.'
                    ], 401);
                }
                
                // Set the authenticated buyer for this request
                Auth::guard('buyer')->setUser($buyer);
            }
        }

        // Update last activity (safely check if columns exist)
        try {
            $buyer->last_activity_at = now();
            $buyer->last_ip_address = $request->ip();
            $buyer->save();
        } catch (\Exception $e) {
            // Log error but don't block the request
            \Log::warning('Could not update buyer activity: ' . $e->getMessage());
        }

        return $next($request);
    }
}