<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\AuditLog;

class EnhancedCsrfMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF for API routes using token authentication
        if ($request->is('api/*') && $request->bearerToken()) {
            return $next($request);
        }

        // Skip CSRF for certain safe methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Generate CSRF token if not exists
        if (!Session::has('_csrf_token')) {
            Session::put('_csrf_token', Str::random(40));
        }

        // Verify CSRF token
        $token = $request->header('X-CSRF-TOKEN') 
                ?? $request->input('_token') 
                ?? $request->header('X-XSRF-TOKEN');

        if (!$token || !hash_equals(Session::get('_csrf_token'), $token)) {
            // Log CSRF attempt
            AuditLog::create([
                'auditable_type' => 'security',
                'auditable_id' => null,
                'user_type' => 'unknown',
                'user_id' => null,
                'event' => 'csrf_violation',
                'audit_type' => 'security',
                'old_values' => null,
                'new_values' => null,
                'changed_fields' => null,
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'request_data' => $request->except(['password', 'password_confirmation', '_token']),
                'response_data' => ['error' => 'CSRF token mismatch'],
                'response_code' => 419,
                'session_id' => Session::getId(),
                'correlation_id' => Str::uuid(),
                'tags' => ['security', 'csrf', 'violation'],
                'notes' => 'CSRF token validation failed',
                'metadata' => [
                    'expected_token_present' => Session::has('_csrf_token'),
                    'provided_token_present' => !empty($token),
                    'token_source' => $this->getTokenSource($request)
                ],
                'environment' => app()->environment(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSRF token mismatch',
                    'error_code' => 'CSRF_TOKEN_MISMATCH'
                ], 419);
            }

            return redirect()->back()
                ->withErrors(['csrf' => 'Security token has expired. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        // Regenerate token periodically for additional security
        if (rand(1, 100) <= 5) { // 5% chance
            Session::put('_csrf_token', Str::random(40));
        }

        return $next($request);
    }

    /**
     * Determine the source of the CSRF token
     */
    private function getTokenSource(Request $request): string
    {
        if ($request->header('X-CSRF-TOKEN')) {
            return 'header_x_csrf_token';
        }
        if ($request->input('_token')) {
            return 'form_token';
        }
        if ($request->header('X-XSRF-TOKEN')) {
            return 'header_x_xsrf_token';
        }
        return 'none';
    }
}