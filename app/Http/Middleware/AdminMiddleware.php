<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
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
        if (!Auth::guard('admin')->check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login as admin.'
                ], 401);
            }

            return redirect()->route('admin.login')
                ->with('error', 'You must be logged in as an admin to access this page.');
        }

        $admin = Auth::guard('admin')->user();

        // Check if account is active
        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact support.'
                ], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Your account has been suspended. Please contact support.');
        }

        // Check for API token authentication if it's an API request
        if ($request->is('api/*')) {
            $token = $request->bearerToken() ?? $request->header('X-API-Token');
            
            if ($token) {
                $authService = app(\App\Services\AuthService::class);
                $admin = $authService->validateApiToken($token, 'admin');
                
                if (!$admin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired API token.'
                    ], 401);
                }
                
                // Set the authenticated admin for this request
                Auth::guard('admin')->setUser($admin);
            }
        }

        // Log admin activity
        activity()
            ->performedOn($admin)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->url()
            ])
            ->log('Admin accessed: ' . $request->path());

        return $next($request);
    }
}