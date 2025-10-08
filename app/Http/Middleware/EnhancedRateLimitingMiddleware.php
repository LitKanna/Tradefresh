<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class EnhancedRateLimitingMiddleware
{
    /**
     * Rate limit configurations for different endpoint types
     */
    protected $rateLimits = [
        'auth' => ['attempts' => 5, 'decay' => 300], // 5 attempts per 5 minutes
        'api' => ['attempts' => 60, 'decay' => 60],   // 60 requests per minute
        'payment' => ['attempts' => 10, 'decay' => 3600], // 10 attempts per hour
        'admin' => ['attempts' => 100, 'decay' => 60],    // 100 requests per minute
        'upload' => ['attempts' => 5, 'decay' => 300],    // 5 uploads per 5 minutes
        'forgot-password' => ['attempts' => 3, 'decay' => 3600], // 3 attempts per hour
        'default' => ['attempts' => 60, 'decay' => 60],   // Default limit
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $limitType = 'default')
    {
        $key = $this->resolveRequestSignature($request, $limitType);
        $limit = $this->rateLimits[$limitType] ?? $this->rateLimits['default'];

        // Check if request should be rate limited
        if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
            return $this->buildRateLimitResponse($request, $key, $limit, $limitType);
        }

        // Increment the attempt count
        RateLimiter::hit($key, $limit['decay']);

        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $key, $limit);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, $limitType)
    {
        $user = $request->user();
        $ip = $request->ip();
        $route = $request->route() ? $request->route()->getName() : $request->path();

        // Different strategies based on endpoint type
        switch ($limitType) {
            case 'auth':
                // Rate limit by IP and email for auth endpoints
                $email = $request->input('email', 'unknown');
                return "auth:{$ip}:{$email}";

            case 'payment':
                // Rate limit by user and IP for payment endpoints
                $userId = $user ? $user->id : 'guest';
                return "payment:{$userId}:{$ip}";

            case 'upload':
                // Rate limit file uploads by user
                $userId = $user ? $user->id : 'guest';
                return "upload:{$userId}:{$ip}";

            case 'forgot-password':
                // Rate limit password reset by email and IP
                $email = $request->input('email', 'unknown');
                return "forgot-password:{$ip}:{$email}";

            default:
                // Default rate limiting by user or IP
                $identifier = $user ? $user->id : $ip;
                return "api:{$limitType}:{$identifier}:{$route}";
        }
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(Request $request, $key, $limit, $limitType)
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        // Log rate limit violation
        $this->logRateLimitViolation($request, $limitType, $retryAfter);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'limit_type' => $limitType
            ], 429)->header('Retry-After', $retryAfter);
        }

        return response()->view('errors.429', [
            'retryAfter' => $retryAfter,
            'limitType' => $limitType
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, $key, $limit)
    {
        $remaining = RateLimiter::remaining($key, $limit['attempts']);
        $resetTime = RateLimiter::availableIn($key);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit['attempts'],
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => now()->addSeconds($resetTime)->timestamp,
        ]);
    }

    /**
     * Log rate limit violation
     */
    protected function logRateLimitViolation(Request $request, $limitType, $retryAfter)
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => $request->user() ? get_class($request->user()) : 'unknown',
            'user_id' => $request->user() ? $request->user()->id : null,
            'event' => 'rate_limit_exceeded',
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'request_data' => $request->except(['password', 'password_confirmation']),
            'response_data' => [
                'error' => 'Rate limit exceeded',
                'retry_after' => $retryAfter
            ],
            'response_code' => 429,
            'session_id' => $request->session() ? $request->session()->getId() : null,
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'rate_limit', 'violation'],
            'notes' => "Rate limit exceeded for {$limitType} endpoint",
            'metadata' => [
                'limit_type' => $limitType,
                'retry_after_seconds' => $retryAfter,
                'referer' => $request->header('referer')
            ],
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Check for suspicious activity patterns
     */
    protected function detectSuspiciousActivity(Request $request)
    {
        $ip = $request->ip();
        $timeWindow = 300; // 5 minutes

        // Check for distributed attack patterns
        $suspiciousPatterns = [
            "suspicious_paths:{$ip}" => $this->hasSuspiciousPaths($request),
            "rapid_user_agent_changes:{$ip}" => $this->hasRapidUserAgentChanges($request),
            "unusual_request_patterns:{$ip}" => $this->hasUnusualRequestPatterns($request),
        ];

        foreach ($suspiciousPatterns as $key => $isSuspicious) {
            if ($isSuspicious) {
                Cache::increment($key, 1);
                Cache::expire($key, $timeWindow);

                if (Cache::get($key, 0) > 10) { // Threshold
                    $this->logSuspiciousActivity($request, $key);
                    // Consider blocking IP temporarily
                    $this->temporarilyBlockIP($ip, 3600); // 1 hour
                }
            }
        }
    }

    /**
     * Check for suspicious request paths
     */
    protected function hasSuspiciousPaths(Request $request)
    {
        $suspiciousPaths = [
            'admin', 'wp-admin', 'phpmyadmin', 'wp-login.php',
            '.env', 'config', 'backup', 'sql', 'database'
        ];

        $path = strtolower($request->path());
        foreach ($suspiciousPaths as $suspiciousPath) {
            if (strpos($path, $suspiciousPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for rapid user agent changes from same IP
     */
    protected function hasRapidUserAgentChanges(Request $request)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $cacheKey = "user_agents:{$ip}";
        
        $userAgents = Cache::get($cacheKey, []);
        $userAgents[] = $userAgent;
        
        // Keep only unique user agents from last 5 minutes
        $userAgents = array_unique($userAgents);
        Cache::put($cacheKey, $userAgents, 300);

        return count($userAgents) > 5; // More than 5 different user agents
    }

    /**
     * Check for unusual request patterns
     */
    protected function hasUnusualRequestPatterns(Request $request)
    {
        // Check for SQL injection attempts, XSS attempts, etc.
        $input = json_encode($request->all());
        $suspiciousPatterns = [
            '/union\s+select/i',
            '/or\s+1\s*=\s*1/i',
            '/<script/i',
            '/javascript:/i',
            '/eval\s*\(/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Temporarily block an IP address
     */
    protected function temporarilyBlockIP($ip, $duration = 3600)
    {
        Cache::put("blocked_ip:{$ip}", true, $duration);
    }

    /**
     * Log suspicious activity
     */
    protected function logSuspiciousActivity(Request $request, $patternType)
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => 'unknown',
            'user_id' => null,
            'event' => 'suspicious_activity',
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'request_data' => $request->all(),
            'response_data' => null,
            'response_code' => null,
            'session_id' => null,
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'suspicious', 'pattern_detection'],
            'notes' => "Suspicious activity pattern detected: {$patternType}",
            'metadata' => [
                'pattern_type' => $patternType,
                'detection_timestamp' => now()->timestamp
            ],
            'environment' => app()->environment(),
        ]);
    }
}