<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        return view('auth.admin.login');
    }

    /**
     * Handle admin login request
     */
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        $result = $this->authService->authenticate('admin', $credentials, $remember);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 401);
        }

        if (!$result['success']) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $result['message']]);
        }

        if (!empty($result['requires_2fa'])) {
            return redirect()->route('admin.2fa');
        }

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $result['user']->name);
    }

    /**
     * Show two-factor authentication form
     */
    public function showTwoFactorForm()
    {
        if (!session('two_factor_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('auth.admin.two-factor');
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $result = $this->authService->verifyTwoFactorCode($request->code);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 401);
        }

        if (!$result['success']) {
            return redirect()->back()
                ->withErrors(['code' => $result['message']]);
        }

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $result['user']->name);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        $this->authService->logout('admin');

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Successfully logged out']);
        }

        return redirect()->route('admin.login')
            ->with('success', 'You have been successfully logged out.');
    }

    /**
     * Show password reset request form
     */
    public function showPasswordRequestForm()
    {
        return view('auth.admin.password-request');
    }

    /**
     * Handle password reset request
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->authService->requestPasswordReset('admin', $request->email);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()
            ->with('success', $result['message']);
    }

    /**
     * Show password reset form
     */
    public function showResetForm(string $token)
    {
        return view('auth.admin.password-reset', ['token' => $token]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);

        $result = $this->authService->resetPassword($request->token, $request->password);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        if (!$result['success']) {
            return redirect()->back()
                ->withErrors(['token' => $result['message']]);
        }

        return redirect()->route('admin.login')
            ->with('success', $result['message']);
    }

    /**
     * Toggle two-factor authentication
     */
    public function toggleTwoFactor(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $enable = $request->boolean('enable');

        $result = $this->authService->toggleTwoFactor($user, $enable);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()
            ->with('success', $result['message']);
    }

    // activityLogs() method removed - not routed anywhere
}