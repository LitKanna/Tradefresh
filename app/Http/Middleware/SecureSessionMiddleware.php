<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class SecureSessionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Regenerate session ID periodically for security
        $this->handleSessionRegeneration($request);

        // Check session timeout
        $this->handleSessionTimeout($request);

        // Validate session integrity
        $this->validateSessionIntegrity($request);

        // Set secure session configuration
        $this->configureSecureSession($request);

        $response = $next($request);

        // Set secure cookie headers
        return $this->setSecureCookieHeaders($response);
    }

    /**
     * Handle session ID regeneration
     */
    protected function handleSessionRegeneration(Request $request)
    {
        $lastRegeneration = Session::get('last_regeneration', 0);
        $regenerationInterval = config('session.regeneration_interval', 1800); // 30 minutes

        // Regenerate session ID every 30 minutes or on login
        if ((time() - $lastRegeneration) > $regenerationInterval) {
            Session::regenerate();
            Session::put('last_regeneration', time());

            if (Auth::check()) {
                $this->logSecurityEvent(Auth::user(), 'session_regenerated', [
                    'old_session_id' => Session::getId(),
                    'regeneration_reason' => 'periodic'
                ]);
            }
        }

        // Regenerate on authentication changes
        if (Auth::check() && !Session::has('auth_verified')) {
            Session::regenerate();
            Session::put('auth_verified', true);
            Session::put('last_regeneration', time());

            $this->logSecurityEvent(Auth::user(), 'session_regenerated', [
                'regeneration_reason' => 'authentication'
            ]);
        }
    }

    /**
     * Handle session timeout
     */
    protected function handleSessionTimeout(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $lastActivity = Session::get('last_activity', time());
        $timeout = config('session.timeout', 7200); // 2 hours default

        // Different timeouts for different user types
        if (method_exists($user, 'getSessionTimeout')) {
            $timeout = $user->getSessionTimeout();
        } elseif ($user instanceof \App\Models\Admin) {
            $timeout = config('session.admin_timeout', 3600); // 1 hour for admins
        }

        if ((time() - $lastActivity) > $timeout) {
            $this->logSecurityEvent($user, 'session_timeout', [
                'timeout_duration' => $timeout,
                'last_activity' => date('Y-m-d H:i:s', $lastActivity)
            ]);

            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            if ($request->expectsJson()) {
                abort(401, 'Session timeout');
            }

            redirect()->guest('/login')->with('message', 'Your session has expired. Please log in again.');
        }

        Session::put('last_activity', time());
    }

    /**
     * Validate session integrity
     */
    protected function validateSessionIntegrity(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Check if session belongs to current IP (with some flexibility for mobile networks)
        $sessionIP = Session::get('session_ip');
        $currentIP = $request->ip();

        if ($sessionIP && $sessionIP !== $currentIP) {
            // Allow IP changes within same /24 subnet for mobile networks
            if (!$this->isAllowedIPChange($sessionIP, $currentIP)) {
                $this->logSecurityEvent($user, 'session_ip_mismatch', [
                    'original_ip' => $sessionIP,
                    'current_ip' => $currentIP,
                    'action' => 'session_terminated'
                ]);

                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                if ($request->expectsJson()) {
                    abort(401, 'Session security violation');
                }

                return redirect()->guest('/login')
                    ->with('error', 'Session terminated due to security violation.');
            }
        } else {
            Session::put('session_ip', $currentIP);
        }

        // Validate user agent consistency
        $sessionUserAgent = Session::get('session_user_agent');
        $currentUserAgent = $request->userAgent();

        if ($sessionUserAgent && $sessionUserAgent !== $currentUserAgent) {
            $this->logSecurityEvent($user, 'session_user_agent_change', [
                'original_user_agent' => $sessionUserAgent,
                'current_user_agent' => $currentUserAgent
            ]);

            // Don't terminate session for user agent changes, just log them
            Session::put('session_user_agent', $currentUserAgent);
        } else {
            Session::put('session_user_agent', $currentUserAgent);
        }
    }

    /**
     * Configure secure session settings
     */
    protected function configureSecureSession(Request $request)
    {
        // Set session cookie parameters for security
        $sessionConfig = [
            'lifetime' => config('session.lifetime', 120),
            'path' => config('session.path', '/'),
            'domain' => config('session.domain'),
            'secure' => $request->isSecure() || config('session.secure', false),
            'httponly' => true,
            'samesite' => config('session.same_site', 'lax'),
        ];

        // Additional security for production
        if (app()->environment('production')) {
            $sessionConfig['secure'] = true;
            $sessionConfig['samesite'] = 'strict';
        }

        session_set_cookie_params($sessionConfig);
    }

    /**
     * Set secure cookie headers
     */
    protected function setSecureCookieHeaders($response)
    {
        // Set secure flags for all cookies
        foreach ($response->headers->getCookies() as $cookie) {
            if (app()->environment('production') || request()->isSecure()) {
                $cookie->setSecure(true);
            }
            $cookie->setHttpOnly(true);
            $cookie->setSameSite('lax');
        }

        return $response;
    }

    /**
     * Check if IP change is allowed (same subnet)
     */
    protected function isAllowedIPChange($originalIP, $currentIP)
    {
        // Allow changes within same /24 subnet for IPv4
        if (filter_var($originalIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            filter_var($currentIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $originalSubnet = implode('.', array_slice(explode('.', $originalIP), 0, 3));
            $currentSubnet = implode('.', array_slice(explode('.', $currentIP), 0, 3));
            
            return $originalSubnet === $currentSubnet;
        }

        // For IPv6 or mixed scenarios, be more restrictive
        return false;
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent($user, $event, $metadata = [])
    {
        AuditLog::create([
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => $event,
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'request_data' => null,
            'response_data' => null,
            'response_code' => null,
            'session_id' => Session::getId(),
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'session', $event],
            'notes' => "Session security event: {$event}",
            'metadata' => array_merge($metadata, [
                'timestamp' => now()->toDateTimeString(),
                'environment' => app()->environment()
            ]),
            'environment' => app()->environment(),
        ]);
    }
}