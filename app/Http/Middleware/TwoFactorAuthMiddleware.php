<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthMiddleware
{
    protected $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $next($request);
        }

        // Skip 2FA for certain routes (like 2FA setup/verification routes)
        $exemptRoutes = [
            '2fa/verify',
            '2fa/setup',
            '2fa/disable',
            '2fa/recovery',
            'logout'
        ];

        foreach ($exemptRoutes as $route) {
            if ($request->is($route) || $request->is("*/{$route}")) {
                return $next($request);
            }
        }

        // Check if user needs 2FA verification
        if ($this->twoFactorService->requiresTwoFactor($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Two-factor authentication required',
                    'error_code' => 'TWO_FACTOR_REQUIRED',
                    'redirect_url' => route('2fa.verify')
                ], 423);
            }

            return redirect()->route('2fa.verify')
                ->with('message', 'Please complete two-factor authentication to continue.');
        }

        return $next($request);
    }
}