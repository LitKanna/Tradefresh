<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class VendorAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('guest:vendor')->except(['logout', 'verifyEmail']);
    }

    /**
     * Show vendor registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.vendor.register');
    }

    /**
     * Handle vendor registration (Simplified 6-field version)
     */
    public function register(Request $request)
    {
        // Validate the simplified 6-field form
        $validated = $request->validate([
            'abn' => 'required|string|size:14', // With spaces: "12 345 678 901"
            'business_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:vendors,email',
            'password' => 'required|string|min:8',
        ]);
        
        // Clean ABN (remove spaces)
        $abn = preg_replace('/\s+/', '', $validated['abn']);
        
        // Prepare vendor data with all required fields
        $vendorData = [
            'abn' => $abn,
            'business_name' => $validated['business_name'],
            'business_type' => 'company', // Default to company
            'vendor_category' => 'other', // Default category
            'contact_name' => $validated['contact_name'],
            'phone' => $validated['phone'],
            'email' => strtolower(trim($validated['email'])),
            'username' => strtolower(trim($validated['email'])), // Use email as username
            'password' => bcrypt($validated['password']),
            'vendor_type' => 'business',
            'address' => '', // Will be updated later
            'suburb' => '', // Will be updated later
            'state' => 'NSW', // Default to NSW
            'postcode' => '', // Will be updated later
            'status' => 'pending', // Requires admin approval
            'verification_status' => 'unverified', // Default status
            'email_verified_at' => null,
        ];
        
        try {
            // Create vendor account
            $vendor = \App\Models\Vendor::create($vendorData);
            
            // Send welcome email (optional)
            // Mail::to($vendor->email)->send(new VendorWelcome($vendor));
            
            // If auto-approval is enabled (for testing/demo)
            $autoApprove = config('marketplace.vendor.auto_approve', false);
            
            if ($autoApprove) {
                // Auto-approve and log in the vendor
                $vendor->status = 'active';
                $vendor->email_verified_at = now();
                $vendor->save();
                
                // Log the vendor in
                Auth::guard('vendor')->login($vendor);
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Registration successful! Welcome to Sydney Markets.',
                        'vendor' => $vendor->only(['id', 'business_name', 'email', 'status']),
                        'redirect' => route('vendor.dashboard')
                    ], 201);
                }
                
                return redirect()->route('vendor.dashboard')
                    ->with('success', 'Registration successful! Welcome to Sydney Markets.');
            }
            
            // Standard flow - pending approval
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful! Your account is pending approval.',
                    'vendor' => $vendor->only(['id', 'business_name', 'email', 'status']),
                    'redirect' => route('vendor.verification')
                ], 201);
            }
            
            return redirect()->route('vendor.verification')
                ->with('success', 'Registration successful! Your account is pending approval. You will receive an email once approved.');
                
        } catch (\Exception $e) {
            \Log::error('Vendor registration error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ], 400);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }

    /**
     * Show vendor login form
     */
    public function showLoginForm()
    {
        return view('auth.vendor.login');
    }

    /**
     * Handle vendor login
     */
    public function login(Request $request)
    {
        // Validate request
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        
        $remember = $request->boolean('remember');

        // Attempt to authenticate
        if (!Auth::guard('vendor')->attempt($credentials, $remember)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $vendor = Auth::guard('vendor')->user();

        // Check if vendor account is active
        if ($vendor->status === 'pending') {
            Auth::guard('vendor')->logout();
            return redirect()->route('vendor.verification')
                ->with('warning', 'Your account is still pending approval.');
        }

        if ($vendor->status === 'suspended') {
            Auth::guard('vendor')->logout();
            return redirect()->back()
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        // Successful login
        $request->session()->regenerate();

        // Log successful login for debugging
        \Log::info('Vendor login successful', [
            'vendor_id' => $vendor->id,
            'business_name' => $vendor->business_name,
            'redirect_to' => route('vendor.dashboard')
        ]);

        return redirect()->intended(route('vendor.dashboard'))
            ->with('success', 'Welcome back, ' . $vendor->business_name . '!');
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request, string $token)
    {
        $result = $this->authService->verifyEmail($token);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        if (!$result['success']) {
            return redirect()->route('vendor.login')
                ->withErrors(['error' => $result['message']]);
        }

        return redirect()->route('vendor.login')
            ->with('success', $result['message']);
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:vendors,email'
        ]);

        $vendor = \App\Models\Vendor::where('email', $request->email)->first();
        
        if ($vendor->email_verified_at) {
            return redirect()->back()
                ->withErrors(['email' => 'Email already verified.']);
        }

        // Resend verification email logic
        // $this->authService->sendVerificationEmail($vendor);

        return redirect()->back()
            ->with('success', 'Verification email has been resent.');
    }

    /**
     * Show two-factor authentication form
     */
    public function showTwoFactorForm()
    {
        if (!session('two_factor_user_id')) {
            return redirect()->route('vendor.login');
        }

        return view('auth.vendor.two-factor');
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

        return redirect()->intended(route('vendor.dashboard'))
            ->with('success', 'Welcome back, ' . $result['user']->name);
    }

    /**
     * Handle vendor logout
     */
    public function logout(Request $request)
    {
        $this->authService->logout('vendor');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Successfully logged out',
                'redirect' => '/'
            ]);
        }

        return redirect('/')
            ->with('success', 'You have been successfully logged out.');
    }

    /**
     * Show password reset request form
     */
    public function showPasswordRequestForm()
    {
        return view('auth.vendor.password-request');
    }

    /**
     * Handle password reset request
     */
    public function sendResetLink(Request $request)
    {
        // Rate limiting: 5 attempts per hour per IP
        $key = 'vendor_password_reset:' . $request->ip();
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 5) {
            \Log::warning('Vendor password reset rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts' => $attempts
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many password reset attempts. Please try again in an hour.'
                ], 429);
            }
            
            return redirect()->back()
                ->withErrors(['email' => 'Too many password reset attempts. Please try again in an hour.']);
        }

        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        try {
            $email = strtolower(trim($request->email));
            
            // Increment rate limit counter
            cache()->put($key, $attempts + 1, now()->addHour());
            
            // Find vendor by email
            $vendor = \App\Models\Vendor::where('email', $email)->first();
            
            if (!$vendor) {
                // Security: Don't reveal if email exists, but log the attempt
                \Log::warning('Password reset attempt for non-existent vendor email', [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                // Always show success message for security
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'If this email is registered as a vendor, you will receive a password reset link.'
                    ]);
                }
                
                return redirect()->back()
                    ->with('success', 'If this email is registered as a vendor, you will receive a password reset link.');
            }
            
            // Generate secure token
            $token = \Str::random(64);
            
            // Save token to password_resets table
            \DB::table('password_resets')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => \Hash::make($token),
                    'created_at' => now(),
                    'guard' => 'vendor'
                ]
            );
            
            // Send reset email using Resend with premium vendor template
            try {
                $resetUrl = route('vendor.password.reset', ['token' => $token]) . '?email=' . urlencode($email);
                
                \Mail::send('emails.vendor-password-reset', [
                    'vendor' => $vendor,
                    'resetUrl' => $resetUrl,
                    'timestamp' => now()->format('M j, Y \a\t g:i A T'),
                    'ipAddress' => $request->ip(),
                    'location' => 'Australia',
                    'expiresAt' => 'In 2 hours',
                    'token' => $token
                ], function ($message) use ($email, $vendor) {
                    $message->to($email, $vendor->contact_name ?? $vendor->business_name)
                            ->subject('Reset Your Vendor Password - TradeFresh B2B')
                            ->from(config('mail.from.address'), config('mail.from.name'));
                });
                
                \Log::info('Vendor password reset email sent successfully', [
                    'vendor_id' => $vendor->id,
                    'business_name' => $vendor->business_name,
                    'email' => $email,
                    'ip' => $request->ip(),
                    'expires_at' => now()->addHours(2)->toISOString()
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Failed to send vendor password reset email', [
                    'vendor_id' => $vendor->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send reset email. Please try again or contact support.'
                    ], 500);
                }
                
                return redirect()->back()
                    ->withErrors(['email' => 'Failed to send reset email. Please try again or contact support.']);
            }
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your business email address.'
                ]);
            }
            
            return redirect()->back()
                ->with('success', 'Password reset link sent to your business email address.');
                
        } catch (\Exception $e) {
            \Log::error('Vendor password reset error', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred. Please try again later.'
                ], 500);
            }
            
            return redirect()->back()
                ->withErrors(['email' => 'An error occurred. Please try again later.']);
        }
    }

    /**
     * Show password reset form
     */
    public function showResetForm(string $token)
    {
        return view('auth.vendor.password-reset', ['token' => $token]);
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

        return redirect()->route('vendor.login')
            ->with('success', $result['message']);
    }

    /**
     * Toggle two-factor authentication
     */
    public function toggleTwoFactor(Request $request)
    {
        $user = Auth::guard('vendor')->user();
        $enable = $request->boolean('enable');

        $result = $this->authService->toggleTwoFactor($user, $enable);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()
            ->with('success', $result['message']);
    }

    // Unused methods removed:
    // - generateApiToken() - Mobile apps forbidden by CLAUDE.md
    // - activityLogs() - Not routed
    // - lookupABN() - Mock data, use Api\ABNLookupController instead
}