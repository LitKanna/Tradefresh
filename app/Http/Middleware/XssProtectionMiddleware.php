<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\XssFilter;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class XssProtectionMiddleware
{
    protected $xssFilter;

    public function __construct(XssFilter $xssFilter)
    {
        $this->xssFilter = $xssFilter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Clean input data
        $this->cleanInput($request);
        
        // Process the request
        $response = $next($request);
        
        // Add XSS protection headers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        
        return $response;
    }

    /**
     * Clean request input to prevent XSS attacks
     */
    protected function cleanInput(Request $request)
    {
        $input = $request->all();
        $cleanedInput = $this->cleanArray($input);
        
        // Check if any malicious content was detected and removed
        if ($this->hasMaliciousContent($input, $cleanedInput)) {
            $this->logXssAttempt($request, $input);
        }
        
        // Replace the request input with cleaned data
        $request->replace($cleanedInput);
    }

    /**
     * Recursively clean array data
     */
    protected function cleanArray($data)
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = $this->cleanArray($value);
            } else {
                $cleaned[$key] = $this->xssFilter->clean($value);
            }
        }
        
        return $cleaned;
    }

    /**
     * Check if malicious content was detected
     */
    protected function hasMaliciousContent($original, $cleaned)
    {
        return json_encode($original) !== json_encode($cleaned);
    }

    /**
     * Log XSS attempt
     */
    protected function logXssAttempt(Request $request, $originalInput)
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => $request->user() ? get_class($request->user()) : 'unknown',
            'user_id' => $request->user() ? $request->user()->id : null,
            'event' => 'xss_attempt',
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'request_data' => $originalInput,
            'response_data' => null,
            'response_code' => null,
            'session_id' => $request->session()->getId(),
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'xss', 'attempt'],
            'notes' => 'Potential XSS attack detected and sanitized',
            'metadata' => [
                'referer' => $request->header('referer'),
                'suspicious_fields' => $this->findSuspiciousFields($originalInput)
            ],
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Find fields that contained suspicious content
     */
    protected function findSuspiciousFields($input)
    {
        $suspicious = [];
        
        foreach ($input as $key => $value) {
            if (is_string($value) && $this->xssFilter->isSuspicious($value)) {
                $suspicious[] = $key;
            }
        }
        
        return $suspicious;
    }
}