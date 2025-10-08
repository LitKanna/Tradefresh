<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionStarted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure session is started
        if (!Session::isStarted()) {
            Session::start();
        }
        
        // Regenerate CSRF token if missing
        if (!Session::has('_token')) {
            Session::regenerateToken();
        }
        
        // Add CSRF token to view
        view()->share('csrf_token', Session::token());
        
        // Log session debug info (remove in production)
        if (config('app.debug')) {
            \Log::debug('Session Debug', [
                'session_id' => Session::getId(),
                'has_token' => Session::has('_token'),
                'token' => substr(Session::token(), 0, 8) . '...',
                'driver' => config('session.driver'),
                'is_started' => Session::isStarted(),
            ]);
        }
        
        return $next($request);
    }
}