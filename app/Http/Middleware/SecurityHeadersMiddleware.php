<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apply security headers based on environment and request type
        $this->addBasicSecurityHeaders($response);
        $this->addContentSecurityPolicy($response, $request);
        $this->addHttpSecurityHeaders($response);
        $this->addPermissionsPolicy($response);
        $this->addCacheHeaders($response);

        return $response;
    }

    /**
     * Add basic security headers
     */
    protected function addBasicSecurityHeaders($response)
    {
        // Prevent XSS attacks
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Hide server information
        $response->headers->set('X-Powered-By', '');
        $response->headers->remove('Server');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS (HTTP Strict Transport Security)
        if (app()->environment('production') || request()->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
    }

    /**
     * Add Content Security Policy headers
     */
    protected function addContentSecurityPolicy($response, $request)
    {
        // Base CSP directives
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.stripe.com wss: ws:",
            "media-src 'self' data: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];

        // Add specific directives for different environments
        if (app()->environment('local', 'development')) {
            // More lenient CSP for development
            $directives = array_map(function($directive) {
                if (str_starts_with($directive, 'script-src')) {
                    return $directive . " 'unsafe-eval' http://localhost:* http://127.0.0.1:*";
                }
                if (str_starts_with($directive, 'connect-src')) {
                    return $directive . " http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*";
                }
                return $directive;
            }, $directives);
        }

        // Special handling for API routes
        if ($request->is('api/*')) {
            // More restrictive CSP for API endpoints
            $directives = [
                "default-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'none'"
            ];
        }

        $csp = implode('; ', $directives);
        $response->headers->set('Content-Security-Policy', $csp);

        // Also add report-only version for monitoring
        if (app()->environment('production')) {
            $reportCsp = $csp . "; report-uri " . route('security.csp-report', [], false);
            $response->headers->set('Content-Security-Policy-Report-Only', $reportCsp);
        }
    }

    /**
     * Add additional HTTP security headers
     */
    protected function addHttpSecurityHeaders($response)
    {
        // Cross-Origin Resource Sharing (CORS) security
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // DNS prefetch control
        $response->headers->set('X-DNS-Prefetch-Control', 'off');

        // Download options for IE
        $response->headers->set('X-Download-Options', 'noopen');

        // Prevent information disclosure
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
    }

    /**
     * Add Permissions Policy (formerly Feature Policy)
     */
    protected function addPermissionsPolicy($response)
    {
        $policies = [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=()',
            'battery=()',
            'camera=*',  // Allow camera for product images
            'cross-origin-isolated=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=*',
            'geolocation=()',
            'gyroscope=()',
            'keyboard-map=()',
            'magnetometer=()',
            'microphone=*',  // Allow microphone for voice features
            'midi=()',
            'navigation-override=()',
            'payment=*',  // Allow payment features
            'picture-in-picture=()',
            'publickey-credentials-get=()',
            'screen-wake-lock=()',
            'sync-xhr=()',
            'usb=()',
            'web-share=()',
            'xr-spatial-tracking=()'
        ];

        $permissionsPolicy = implode(', ', $policies);
        $response->headers->set('Permissions-Policy', $permissionsPolicy);
    }

    /**
     * Add cache control headers
     */
    protected function addCacheHeaders($response)
    {
        // Prevent caching of sensitive content by default
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Override for static assets (this would typically be handled by web server)
        if (request()->is('css/*') || request()->is('js/*') || request()->is('images/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        // API responses should not be cached by intermediaries
        if (request()->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Vary', 'Authorization, Accept-Encoding');
        }
    }

    /**
     * Get nonce for inline scripts (if needed)
     */
    protected function getNonce()
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Add security headers specific to file uploads
     */
    public function addFileUploadHeaders($response)
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', 'attachment');
        
        // Prevent execution of uploaded files
        $response->headers->set('X-Download-Options', 'noopen');
        
        return $response;
    }

    /**
     * Add security headers for admin areas
     */
    public function addAdminSecurityHeaders($response)
    {
        // More restrictive headers for admin areas
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, nosnippet, noarchive');
        
        // Shorter cache times for admin content
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        
        return $response;
    }

    /**
     * Add headers for payment processing pages
     */
    public function addPaymentSecurityHeaders($response)
    {
        // Extra strict headers for payment pages
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        
        // Prevent any caching of payment pages
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        
        return $response;
    }
}