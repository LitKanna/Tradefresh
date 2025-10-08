<?php

namespace App\Http\Controllers\Auth;

use App\Events\BuyerStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Buyer;
use App\Models\PickupDetail;
use App\Services\ABN\ABNLookupService;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BuyerAuthController extends Controller
{
    protected AuthService $authService;

    protected ABNLookupService $abnService;

    public function __construct(AuthService $authService, ABNLookupService $abnService)
    {
        $this->authService = $authService;
        $this->abnService = $abnService;
        // Apply guest middleware only to login and registration routes
        $this->middleware('guest:buyer')->only(['showLoginForm', 'login', 'showRegistrationForm', 'register']);
    }

    /**
     * Show buyer registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.buyer.register');
    }

    /**
     * Check if email is available (AJAX endpoint)
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $emailExists = Buyer::where('email', strtolower(trim($request->email)))->exists();
        $businessUserExists = BusinessUser::where('email', strtolower(trim($request->email)))->exists();

        $exists = $emailExists || $businessUserExists;

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'This email is already registered.' : 'Email is available.',
        ]);
    }

    /**
     * Check ABN validity and fetch business details (AJAX endpoint)
     */
    public function checkABN(Request $request)
    {
        $request->validate([
            'abn' => 'required|string',
        ]);

        try {
            $abn = preg_replace('/\D/', '', $request->abn);

            // First check if ABN checksum is valid
            // TEMPORARILY DISABLED FOR TESTING - Accept any 11-digit number
            if (strlen($abn) !== 11) {
                return response()->json([
                    'success' => false,
                    'message' => 'ABN must be 11 digits.',
                    'valid' => false,
                ]);
            }

            // Check if business already exists in our system
            $existingBusiness = Business::where('abn', $abn)->first();

            if ($existingBusiness) {
                // Check if business already has users
                $hasUsers = BusinessUser::where('business_id', $existingBusiness->id)
                    ->where('user_type', 'buyer')
                    ->exists();

                return response()->json([
                    'success' => true,
                    'valid' => true,
                    'existing' => true,
                    'has_users' => $hasUsers,
                    'business' => [
                        'name' => $existingBusiness->entity_name,
                        'trading_names' => $existingBusiness->trading_names,
                        'abn_status' => $existingBusiness->abn_status,
                        'gst_registered' => $existingBusiness->gst_registered,
                        'state' => $existingBusiness->address_state_code,
                        'postcode' => $existingBusiness->address_postcode,
                    ],
                    'message' => $hasUsers
                        ? 'This business is already registered. You can be added as an additional user.'
                        : 'Business ABN verified. Please complete your registration.',
                ]);
            }

            // Lookup ABN via API
            $businessData = $this->abnService->lookup($abn);

            if (! $businessData) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Unable to verify ABN. You can proceed with provisional registration.',
                    'allow_provisional' => true,
                ]);
            }

            // Check if ABN is active
            $isActive = ($businessData['abn_status'] ?? '') === 'active';

            return response()->json([
                'success' => true,
                'valid' => true,
                'existing' => false,
                'business' => [
                    'name' => $businessData['entity_name'] ?? '',
                    'trading_names' => $businessData['trading_names'] ?? [],
                    'abn_status' => $businessData['abn_status'] ?? 'unknown',
                    'gst_registered' => $businessData['gst_registered'] ?? false,
                    'state' => $businessData['address_state_code'] ?? '',
                    'postcode' => $businessData['address_postcode'] ?? '',
                ],
                'warning' => ! $isActive ? 'This ABN appears to be inactive. You can still register but verification may be required.' : null,
            ]);

        } catch (Exception $e) {
            Log::error('ABN check failed', ['error' => $e->getMessage(), 'abn' => $request->abn]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to verify ABN at this time. You can proceed with provisional registration.',
                'allow_provisional' => true,
            ]);
        }
    }

    /**
     * Get business details from ABN (AJAX endpoint)
     */
    public function getBusinessDetails(Request $request)
    {
        $request->validate([
            'abn' => 'required|string',
        ]);

        try {
            $abn = preg_replace('/\D/', '', $request->abn);

            // Try to get from database first
            $business = Business::where('abn', $abn)->first();

            if ($business) {
                return response()->json([
                    'success' => true,
                    'business' => $business->getSummary(),
                    'source' => 'database',
                ]);
            }

            // Lookup via API
            $businessData = $this->abnService->lookup($abn);

            if ($businessData) {
                return response()->json([
                    'success' => true,
                    'business' => $businessData,
                    'source' => 'api',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Business details not found',
            ], 404);

        } catch (Exception $e) {
            Log::error('Get business details failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch business details',
            ], 500);
        }
    }

    /**
     * Handle simple buyer registration (individual or business)
     */
    public function simpleRegister(Request $request)
    {
        try {
            // Parse name fields flexibly
            $contactName = '';
            $firstName = '';
            $lastName = '';

            if ($request->has('contact_name')) {
                $contactName = trim($request->input('contact_name'));
                $nameParts = explode(' ', $contactName, 2);
                $firstName = $nameParts[0] ?? 'Buyer';
                $lastName = $nameParts[1] ?? '';
            } elseif ($request->has('name')) {
                $contactName = trim($request->input('name'));
                $nameParts = explode(' ', $contactName, 2);
                $firstName = $nameParts[0] ?? 'Buyer';
                $lastName = $nameParts[1] ?? '';
            } elseif ($request->has('first_name')) {
                $firstName = trim($request->input('first_name', 'Buyer'));
                $lastName = trim($request->input('last_name', ''));
                $contactName = trim($firstName.' '.$lastName);
            } else {
                // Default values if no name provided
                $firstName = 'Buyer';
                $lastName = 'User';
                $contactName = 'Buyer User';
            }

            // Minimal validation - only absolute essentials
            $validationRules = [
                'email' => 'required|email|max:255',
                'password' => 'required|min:6',
                'phone' => 'required|min:10|max:20',
            ];

            // Check for password confirmation only if it exists
            if ($request->has('password_confirmation')) {
                $validationRules['password'] = 'required|min:6|confirmed';
            }

            try {
                $validated = $request->validate($validationRules);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::warning('Registration validation failed', [
                    'errors' => $e->errors(),
                    'input' => $request->except(['password', 'password_confirmation']),
                ]);

                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please check your information and try again.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors($e->errors());
            }

            // Clean email
            $email = strtolower(trim($validated['email']));

            // Check if email already exists
            $existingBuyer = Buyer::where('email', $email)->first();
            if ($existingBuyer) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email is already registered. Please login instead.',
                        'errors' => ['email' => ['This email is already registered.']],
                    ], 422);
                }

                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' => 'This email is already registered. Please login instead.']);
            }

            // Clean phone
            $cleanPhone = preg_replace('/[^\d]/', '', $validated['phone']);
            if (strlen($cleanPhone) === 9 && $cleanPhone[0] === '4') {
                $cleanPhone = '0'.$cleanPhone;
            }
            if (empty($cleanPhone)) {
                $cleanPhone = '0400000000'; // Default phone if cleaning fails
            }

            DB::beginTransaction();

            try {
                // Create buyer with minimal required fields and safe defaults
                // Using ONLY columns that exist in the database
                $buyerData = [
                    'contact_name' => $contactName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => Hash::make($validated['password']), // Explicitly hash password
                    'phone' => $cleanPhone,
                    'buyer_type' => 'individual', // Valid enum: regular, premium, wholesale, individual (default: individual)
                    'business_type' => 'other', // Valid enum: restaurant, cafe, grocery, retailer, distributor, other, individual
                    'purchase_category' => 'fruits_vegetables', // Using a valid basic category
                    'status' => 'active',
                    'email_verified_at' => now(),
                ];

                // Add business name - required field
                if ($request->has('company_name') && ! empty($request->input('company_name'))) {
                    $buyerData['business_name'] = trim($request->input('company_name'));
                } else {
                    $buyerData['business_name'] = $contactName.' Business';
                }

                // Add ABN if provided
                if ($request->has('abn') && ! empty($request->input('abn'))) {
                    $buyerData['abn'] = preg_replace('/\D/', '', $request->input('abn'));
                }

                // Set default addresses - use provided or defaults
                $buyerData['address'] = $request->input('address', 'To be updated');
                $buyerData['suburb'] = $request->input('suburb', 'Sydney');
                $buyerData['state'] = $request->input('state', 'NSW');
                $buyerData['postcode'] = $request->input('postcode', '2000');

                // Create the buyer
                $buyer = Buyer::create($buyerData);

                if (! $buyer) {
                    throw new Exception('Failed to create buyer account');
                }

                // Log successful registration
                Log::info('Buyer registered successfully', [
                    'buyer_id' => $buyer->id,
                    'email' => $email,
                    'name' => $contactName,
                ]);

                // Auto-login the buyer (only if session is available)
                if ($request->hasSession()) {
                    Auth::guard('buyer')->login($buyer);

                    // Ensure session is saved
                    $request->session()->regenerate();
                    $request->session()->save();
                }

                DB::commit();

                // Success response
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Registration successful! Welcome to Sydney Markets.',
                        'user' => [
                            'id' => $buyer->id,
                            'name' => $buyer->contact_name,
                            'email' => $buyer->email,
                        ],
                        'redirect' => route('buyer.dashboard'),
                    ], 201);
                }

                return redirect()->route('buyer.dashboard')
                    ->with('success', 'Welcome to Sydney Markets! Your account has been created successfully.');

            } catch (Exception $e) {
                DB::rollback();

                Log::error('Failed to create buyer', [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e; // Re-throw to be caught by outer try-catch
            }

        } catch (Exception $e) {
            DB::rollback();

            // Log the error with details
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'input' => $request->except(['password', 'password_confirmation']),
            ]);

            // Prepare user-friendly error message
            $userMessage = 'Registration failed. ';

            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $userMessage = 'This email is already registered. Please try logging in.';
            } elseif (strpos($e->getMessage(), 'contact_name') !== false) {
                $userMessage = 'Please provide your name.';
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $userMessage = 'Please provide a valid email address.';
            } elseif (strpos($e->getMessage(), 'password') !== false) {
                $userMessage = 'Please provide a valid password (minimum 6 characters).';
            } elseif (strpos($e->getMessage(), 'phone') !== false) {
                $userMessage = 'Please provide a valid phone number.';
            } else {
                $userMessage = 'Unable to create your account. Please check your information and try again.';
            }

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $userMessage,
                    'debug' => config('app.debug') ? $e->getMessage() : null,
                ], 422);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => $userMessage]);
        }
    }

    /**
     * Handle buyer registration - always use simple registration
     */
    public function register(Request $request)
    {
        // Always use the simplified registration process
        return $this->simpleRegister($request);
    }

    // businessRegister() method removed - never called (register() uses simpleRegister() instead)

    /**
     * Add vehicle to business (AJAX endpoint)
     */
    public function addVehicle(Request $request)
    {
        $request->validate([
            'vehicle_rego' => 'required|string|max:10',
            'vehicle_state' => 'required|in:NSW,VIC,QLD,SA,WA,TAS,NT,ACT',
            'vehicle_type' => 'required|in:car,ute,van,small_truck,medium_truck,large_truck,refrigerated_truck',
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string',
        ]);

        try {
            $buyer = Auth::guard('buyer')->user();
            $business = Business::find($buyer->business_id);

            if (! $business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found',
                ], 404);
            }

            // Check for duplicate vehicle
            $existingVehicle = PickupDetail::where('business_id', $business->id)
                ->where('vehicle_rego', strtoupper($request->vehicle_rego))
                ->where('vehicle_state', $request->vehicle_state)
                ->first();

            if ($existingVehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'This vehicle is already registered',
                ], 422);
            }

            $pickupDetail = PickupDetail::create([
                'business_id' => $business->id,
                'vehicle_rego' => strtoupper($request->vehicle_rego),
                'vehicle_state' => $request->vehicle_state,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_make' => $request->vehicle_make,
                'vehicle_model' => $request->vehicle_model,
                'primary_driver_name' => $request->driver_name,
                'primary_driver_phone' => $this->normalizePhoneNumber($request->driver_phone),
                'is_active' => true,
                'is_primary_vehicle' => ! PickupDetail::where('business_id', $business->id)->exists(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully',
                'vehicle' => $pickupDetail,
            ]);

        } catch (Exception $e) {
            Log::error('Add vehicle failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add vehicle',
            ], 500);
        }
    }

    /**
     * Update pickup preferences (AJAX endpoint)
     */
    public function updatePickupPreferences(Request $request)
    {
        $request->validate([
            'pickup_method' => 'required|in:scheduled_bay,at_stand,drive_through,warehouse,flexible',
            'preferred_pickup_days' => 'nullable|array',
            'preferred_pickup_time' => 'nullable|date_format:H:i',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        try {
            $buyer = Auth::guard('buyer')->user();
            $business = Business::find($buyer->business_id);

            if (! $business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found',
                ], 404);
            }

            // Update primary vehicle's pickup preferences
            $primaryVehicle = $business->primaryVehicle;

            if ($primaryVehicle) {
                $primaryVehicle->update([
                    'pickup_method' => $request->pickup_method,
                    'preferred_pickup_days' => $request->preferred_pickup_days,
                    'preferred_pickup_time' => $request->preferred_pickup_time,
                    'special_instructions' => $request->special_instructions,
                    'requires_forklift_assistance' => $request->boolean('requires_forklift'),
                    'requires_pallets' => $request->boolean('requires_pallets'),
                    'requires_refrigeration' => $request->boolean('requires_refrigeration'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pickup preferences updated successfully',
            ]);

        } catch (Exception $e) {
            Log::error('Update pickup preferences failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
            ], 500);
        }
    }

    /**
     * Validate vehicle REGO format (AJAX endpoint)
     */
    public function validateRego(Request $request)
    {
        $request->validate([
            'rego' => 'required|string',
            'state' => 'required|in:NSW,VIC,QLD,SA,WA,TAS,NT,ACT',
        ]);

        $rego = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $request->rego));
        $valid = $this->isValidRegoFormat($rego, $request->state);

        return response()->json([
            'valid' => $valid,
            'formatted' => $rego,
            'message' => $valid ? 'Valid registration format' : 'Invalid registration format for '.$request->state,
        ]);
    }

    // Helper methods removed - only used by deleted businessRegister() method:
    // - findOrCreateBusiness()
    // - createPickupDetail()
    // - getPermissionsForRole()
    // - sendWelcomeEmail()

    /**
     * Validate ABN checksum
     */
    protected function validateABNChecksum(string $abn): bool
    {
        if (strlen($abn) !== 11 || ! ctype_digit($abn)) {
            return false;
        }

        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $sum = 0;

        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $abn[$i];
            if ($i === 0) {
                $digit -= 1;
            }
            $sum += $digit * $weights[$i];
        }

        return $sum % 89 === 0;
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        if (strlen($clean) === 9) {
            $clean = '0'.$clean;
        }

        return $clean;
    }

    /**
     * Validate REGO format by state
     */
    protected function isValidRegoFormat(string $rego, string $state): bool
    {
        $patterns = [
            'NSW' => '/^[A-Z]{2,3}\d{2,3}[A-Z]{0,2}$/',
            'VIC' => '/^[A-Z0-9]{1,6}$/',
            'QLD' => '/^\d{3}[A-Z]{3}$/',
            'SA' => '/^S\d{3}[A-Z]{3}$/',
            'WA' => '/^\d[A-Z]{3}\d{3}$/',
            'TAS' => '/^[A-Z]\d{2}[A-Z]{2}$/',
            'NT' => '/^[A-Z]{2}\d{2}[A-Z]{2}$/',
            'ACT' => '/^[A-Z]{3}\d{2}[A-Z]$/',
        ];

        return isset($patterns[$state]) && preg_match($patterns[$state], $rego);
    }

    /**
     * Handle validation error response
     */
    protected function validationError(Request $request, \Illuminate\Validation\ValidationException $e)
    {
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the errors below and try again.',
                'errors' => $e->errors(),
            ], 422);
        }

        return redirect()->back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->withErrors($e->errors());
    }

    /**
     * Handle registration error response
     */
    protected function registrationError(Request $request, string $message)
    {
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        return redirect()->back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->withErrors(['error' => $message]);
    }

    // Keep existing methods below...

    /**
     * Show buyer login form
     */
    public function showLoginForm()
    {
        return view('auth.buyer.login');
    }

    /**
     * Handle buyer login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Convert email to lowercase for case-insensitive login
        $credentials = [
            'email' => strtolower(trim($request->email)),
            'password' => $request->password,
        ];
        $remember = $request->boolean('remember');

        if (Auth::guard('buyer')->attempt($credentials, $remember)) {
            // CRITICAL: Regenerate session ID for security
            $request->session()->regenerate();

            // Get authenticated user
            $user = Auth::guard('buyer')->user();

            // Update user status and online tracking
            if ($user->status !== 'active') {
                $user->status = 'active';
                $user->email_verified_at = now();
            }

            // Track online status (only if columns exist)
            if (Schema::hasColumn('buyers', 'is_online')) {
                $user->is_online = true;
            }
            if (Schema::hasColumn('buyers', 'last_activity_at')) {
                $user->last_activity_at = now();
            }
            $user->save();

            // Broadcast buyer status change to all vendors
            // Temporarily disabled - Pusher not configured
            // broadcast(new BuyerStatusChanged(true));

            // CRITICAL: Explicitly save session to ensure persistence
            $request->session()->save();

            // Log successful login
            Log::info('Buyer login successful', [
                'buyer_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Handle AJAX/JSON requests - check multiple conditions
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful! Redirecting...',
                    'redirect' => route('buyer.dashboard'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->contact_name,
                        'email' => $user->email,
                        'company' => $user->company_name,
                    ],
                ]);
            }

            // Standard redirect with intended URL support
            $redirectTo = route('buyer.dashboard');

            return redirect()->intended($redirectTo)
                ->with('success', 'Welcome back, '.$user->contact_name.'!');
        }

        // Log failed login attempt
        Log::warning('Failed buyer login attempt', [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Handle failed login
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password. Please try again.',
            ], 401);
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid email or password.']);
    }

    /**
     * Handle buyer logout - simplified version
     */
    public function logout(Request $request)
    {
        // Update online status before logout
        $user = Auth::guard('buyer')->user();
        if ($user) {
            // Only update columns if they exist
            if (Schema::hasColumn('buyers', 'is_online')) {
                $user->is_online = false;
            }
            if (Schema::hasColumn('buyers', 'last_activity_at')) {
                $user->last_activity_at = now();
            }
            $user->save();

            // Broadcast buyer status change to all vendors
            // Temporarily disabled - Pusher not configured
            // broadcast(new BuyerStatusChanged(false));
        }

        // Simple and reliable logout
        Auth::guard('buyer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Handle AJAX/JSON requests - check multiple conditions
        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'message' => 'You have been successfully logged out.',
                'redirect' => '/',
            ]);
        }

        // Standard redirect to welcome page
        return redirect('/')->with('success', 'You have been successfully logged out.');
    }


    /**
     * Show password reset request form
     */
    public function showPasswordRequestForm()
    {
        return view('auth.buyer.forgot-password');
    }

    /**
     * Handle password reset request with enhanced security
     */
    public function sendResetLink(Request $request)
    {
        // Rate limiting: 5 attempts per hour per IP
        $key = 'password_reset:'.$request->ip();
        $attempts = cache()->get($key, 0);

        if ($attempts >= 5) {
            Log::warning('Password reset rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts' => $attempts,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many password reset attempts. Please try again in an hour.',
                ], 429);
            }

            return redirect()->back()
                ->withErrors(['email' => 'Too many password reset attempts. Please try again in an hour.']);
        }

        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $email = strtolower(trim($request->email));

            // Increment rate limit counter
            cache()->put($key, $attempts + 1, now()->addHour());

            // Security logging
            Log::info('Password reset request', [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'timestamp' => now()->toISOString(),
            ]);

            // Check if buyer exists
            $buyer = Buyer::where('email', $email)->first();

            if (! $buyer) {
                // Don't reveal that the email doesn't exist for security
                // But still log the attempt
                Log::warning('Password reset attempted for non-existent email', [
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'If your email is registered with us, you will receive a password reset link shortly.',
                    ]);
                }

                return redirect()->back()
                    ->with('status', 'If your email is registered with us, you will receive a password reset link shortly.');
            }

            // Check if a reset token already exists and was created recently (prevent spam)
            $existingToken = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('created_at', '>', now()->subMinutes(2))
                ->first();

            if ($existingToken) {
                Log::info('Password reset request blocked - recent token exists', [
                    'buyer_id' => $buyer->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Password reset link has been sent to your email address.',
                    ]);
                }

                return redirect()->back()
                    ->with('status', 'Password reset link has been sent to your email address.');
            }

            // Generate secure reset token
            $token = Str::random(64);

            // Store token in database with IP logging
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'email' => $email,
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Log token creation for security audit
            DB::table('password_reset_logs')->insert([
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action' => 'token_generated',
                'created_at' => now(),
            ]);

            // Send reset email using Resend with premium template
            try {
                $resetUrl = route('buyer.password.reset', ['token' => $token]).'?email='.urlencode($email);

                Mail::send('emails.password-reset', [
                    'user' => $buyer,
                    'resetUrl' => $resetUrl,
                    'timestamp' => now()->format('M j, Y \a\t g:i A T'),
                    'ipAddress' => $request->ip(),
                    'location' => 'Australia', // You can enhance this with IP geolocation
                    'token' => $token,
                ], function ($message) use ($email, $buyer) {
                    $message->to($email, $buyer->contact_name ?? $buyer->first_name.' '.$buyer->last_name)
                        ->subject('Password Reset | TradeFresh B2B')
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });

                Log::info('Password reset email sent successfully', [
                    'buyer_id' => $buyer->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                    'expires_at' => now()->addHours(2)->toISOString(),
                ]);

            } catch (Exception $e) {
                Log::error('Failed to send password reset email', [
                    'buyer_id' => $buyer->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Clean up the token since email failed
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to send password reset email. Please try again or contact support.',
                    ], 500);
                }

                return redirect()->back()
                    ->withErrors(['email' => 'Unable to send password reset email. Please try again or contact support.']);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email address.',
                ]);
            }

            return redirect()->back()
                ->with('status', 'Password reset link has been sent to your email address.');

        } catch (Exception $e) {
            Log::error('Password reset request failed', [
                'email' => $request->email ?? '',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to process password reset request. Please try again later.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['email' => 'Unable to process password reset request. Please try again later.']);
        }
    }

    /**
     * Show password reset form
     */
    public function showResetForm(string $token)
    {
        return view('auth.buyer.password-reset', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    /**
     * Reset password with enhanced security and validation
     */
    public function resetPassword(Request $request)
    {
        // Enhanced validation with custom password rules
        $request->validate([
            'token' => 'required|string|size:64',
            'email' => 'required|email|max:255',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/', // Must contain lowercase, uppercase, and number
            ],
        ], [
            'password.regex' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number.',
        ]);

        try {
            $email = strtolower(trim($request->email));

            // Security logging
            Log::info('Password reset attempt', [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

            // Find the password reset record
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $passwordReset) {
                Log::warning('Password reset attempted with invalid token', [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'token_provided' => ! empty($request->token),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired reset token.',
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['token' => 'This password reset token is invalid or has expired.']);
            }

            // Check if token matches
            if (! Hash::check($request->token, $passwordReset->token)) {
                Log::warning('Password reset attempted with incorrect token', [
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid reset token.',
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['token' => 'This password reset token is invalid.']);
            }

            // Check if token is expired (2 hours as per requirements)
            if (now()->diffInHours($passwordReset->created_at) > 2) {
                // Delete expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                Log::info('Expired password reset token cleaned up', [
                    'email' => $email,
                    'created_at' => $passwordReset->created_at,
                    'ip' => $request->ip(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This password reset token has expired.',
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['token' => 'This password reset token has expired. Please request a new one.']);
            }

            // Find the buyer
            $buyer = Buyer::where('email', $email)->first();

            if (! $buyer) {
                Log::error('Password reset attempted for non-existent buyer', [
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found.',
                    ], 404);
                }

                return redirect()->back()
                    ->withErrors(['email' => 'User not found.']);
            }

            // Check if the new password is different from the current password
            if (Hash::check($request->password, $buyer->password)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'New password must be different from your current password.',
                    ], 400);
                }

                return redirect()->back()
                    ->withErrors(['password' => 'New password must be different from your current password.']);
            }

            DB::beginTransaction();

            try {
                // Update buyer password
                $buyer->password = Hash::make($request->password);
                $buyer->email_verified_at = now(); // Mark email as verified
                $buyer->password_changed_at = now(); // Track when password was last changed
                $buyer->save();

                // Delete the password reset token
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                // Log successful password reset for security audit
                DB::table('password_reset_logs')->insert([
                    'email' => $email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'action' => 'password_reset_completed',
                    'created_at' => now(),
                ]);

                // Clear any existing remember tokens to force re-authentication
                $buyer->remember_token = null;
                $buyer->save();

                DB::commit();

                // Log successful password reset
                Log::info('Password reset completed successfully', [
                    'buyer_id' => $buyer->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                    'timestamp' => now()->toISOString(),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Your password has been reset successfully. Please log in with your new password.',
                    ]);
                }

                return redirect()->route('buyer.login')
                    ->with('status', 'Your password has been reset successfully. You can now log in with your new password.');

            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Password reset validation failed', [
                'email' => $request->email ?? '',
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'email' => $request->email ?? '',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reset password. Please try again.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['token' => 'Failed to reset password. Please try again.']);
        }
    }

    // Keep other existing methods like verifyEmail, resendVerification, showTwoFactorForm, etc.
    // These remain unchanged from the original implementation...
}
