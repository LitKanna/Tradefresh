<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\MFAService;

class VerifyMFA
{
    protected $mfaService;
    protected $mfaTimeout = 30; // minutes

    public function __construct(MFAService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if MFA is enabled for the user
        if (!$this->mfaService->isMFAEnabled($user)) {
            return $next($request);
        }

        // Check if MFA has been verified in this session
        if ($this->isMFAVerified($request)) {
            return $next($request);
        }

        // Store the intended URL
        $request->session()->put('mfa_intended_url', $request->fullUrl());

        // Redirect to MFA verification page
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'MFA verification required',
                'mfa_required' => true
            ], 403);
        }

        return redirect()->route('mfa.verify');
    }

    /**
     * Check if MFA has been verified
     */
    protected function isMFAVerified(Request $request): bool
    {
        if (!$request->session()->has('mfa_verified')) {
            return false;
        }

        $verifiedAt = $request->session()->get('mfa_verified_at');
        
        if (!$verifiedAt) {
            return false;
        }

        // Check if verification has expired
        if (now()->diffInMinutes($verifiedAt) > $this->mfaTimeout) {
            $request->session()->forget(['mfa_verified', 'mfa_verified_at']);
            return false;
        }

        return true;
    }
}