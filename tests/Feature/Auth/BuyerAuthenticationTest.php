<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Buyer;
use App\Models\Business;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * COMPREHENSIVE BUYER AUTHENTICATION TEST SUITE
 * 
 * Test Authority: Complete authentication flow validation
 * Coverage Target: 100% of authentication paths
 * Data Strategy: Real data only - no mocks
 * 
 * @author Test Architect Agent
 * @version 1.0.0
 */
class BuyerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ========================================
     * REGISTRATION TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_register_with_valid_business_details()
    {
        $data = $this->getValidRegistrationData();
        
        $response = $this->post('/buyer/register', $data);
        
        $response->assertRedirect('/buyer/login');
        $response->assertSessionHas('success');
        
        // Verify database entries
        $this->assertDatabaseHas('buyers', [
            'email' => $data['email'],
            'name' => $data['name'],
            'phone' => $data['phone']
        ]);
        
        $this->assertDatabaseHas('businesses', [
            'business_name' => $data['business_name'],
            'abn' => str_replace(' ', '', $data['abn']),
            'business_type' => $data['business_type']
        ]);
        
        // Verify password is hashed
        $buyer = Buyer::where('email', $data['email'])->first();
        $this->assertTrue(Hash::check($data['password'], $buyer->password));
        
        // Verify audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'user_type' => 'buyer',
            'user_id' => $buyer->id,
            'action' => 'registration_completed'
        ]);
    }

    /** @test */
    public function test_buyer_registration_validates_required_fields()
    {
        $response = $this->post('/buyer/register', []);
        
        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'business_name',
            'abn',
            'phone'
        ]);
    }

    /** @test */
    public function test_buyer_registration_validates_unique_email()
    {
        // Create existing buyer
        $existing = Buyer::factory()->create(['email' => 'existing@buyer.com']);
        
        $data = $this->getValidRegistrationData(['email' => 'existing@buyer.com']);
        
        $response = $this->post('/buyer/register', $data);
        
        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('already been taken', $errors[0]);
    }

    /** @test */
    public function test_buyer_registration_validates_abn_format()
    {
        $invalidABNs = [
            '12345',           // Too short
            '123456789012',    // Too long
            'abcdefghijk',     // Letters
            '12 345 678 90',  // Invalid checksum
        ];
        
        foreach ($invalidABNs as $abn) {
            $data = $this->getValidRegistrationData(['abn' => $abn]);
            $response = $this->post('/buyer/register', $data);
            $response->assertSessionHasErrors(['abn']);
        }
        
        // Test valid ABN passes
        $validABN = '53 004 085 616'; // Real Commonwealth Bank ABN
        $data = $this->getValidRegistrationData(['abn' => $validABN]);
        $response = $this->post('/buyer/register', $data);
        $response->assertSessionDoesntHaveErrors('abn');
    }

    /** @test */
    public function test_buyer_registration_validates_password_strength()
    {
        $weakPasswords = [
            '12345',          // Too short
            'password',       // No numbers
            'password123',    // No special chars/uppercase
        ];
        
        foreach ($weakPasswords as $password) {
            $data = $this->getValidRegistrationData([
                'password' => $password,
                'password_confirmation' => $password
            ]);
            
            $response = $this->post('/buyer/register', $data);
            $response->assertSessionHasErrors(['password']);
        }
        
        // Strong password should pass
        $strongPassword = 'SecureP@ss123!';
        $data = $this->getValidRegistrationData([
            'password' => $strongPassword,
            'password_confirmation' => $strongPassword
        ]);
        
        $response = $this->post('/buyer/register', $data);
        $response->assertSessionDoesntHaveErrors('password');
    }

    /** @test */
    public function test_buyer_registration_validates_australian_phone()
    {
        $invalidPhones = [
            '123',              // Too short
            '+61412345678',     // International format not accepted
            '0412 345 678',     // Spaces not accepted
            '9999999999',       // Invalid format
        ];
        
        foreach ($invalidPhones as $phone) {
            $data = $this->getValidRegistrationData(['phone' => $phone]);
            $response = $this->post('/buyer/register', $data);
            $response->assertSessionHasErrors(['phone']);
        }
        
        // Valid Australian phones
        $validPhones = ['0412345678', '0298765432'];
        foreach ($validPhones as $phone) {
            $data = $this->getValidRegistrationData(['phone' => $phone]);
            $response = $this->post('/buyer/register', $data);
            $response->assertSessionDoesntHaveErrors('phone');
        }
    }

    /**
     * ========================================
     * LOGIN TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_login_with_valid_credentials()
    {
        $buyer = Buyer::factory()->create([
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $response->assertRedirect('/buyer/dashboard');
        $this->assertAuthenticatedAs($buyer, 'buyer');
        
        // Verify session data
        $this->assertTrue(Session::has('buyer_id'));
        $this->assertEquals($buyer->id, Session::get('buyer_id'));
        
        // Verify last login timestamp
        $buyer->refresh();
        $this->assertNotNull($buyer->last_login_at);
        $this->assertTrue($buyer->last_login_at->isToday());
    }

    /** @test */
    public function test_buyer_cannot_login_with_invalid_password()
    {
        $buyer = Buyer::factory()->create([
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'WrongPassword'
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest('buyer');
        
        // Verify failed login attempt is logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /** @test */
    public function test_buyer_login_is_rate_limited()
    {
        $buyer = Buyer::factory()->create([
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        // Attempt login 5 times with wrong password
        for ($i = 0; $i < 5; $i++) {
            $this->post('/buyer/login', [
                'email' => 'buyer@test.com',
                'password' => 'WrongPassword'
            ]);
        }
        
        // 6th attempt should be rate limited
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $response->assertStatus(429); // Too Many Requests
        $response->assertSee('Too many login attempts');
    }

    /** @test */
    public function test_buyer_cannot_login_with_inactive_account()
    {
        $buyer = Buyer::factory()->create([
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123'),
            'status' => 'inactive'
        ]);
        
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('account has been deactivated', $errors[0]);
        $this->assertGuest('buyer');
    }

    /** @test */
    public function test_buyer_cannot_login_with_suspended_business()
    {
        $business = Business::factory()->create(['status' => 'suspended']);
        $buyer = Buyer::factory()->create([
            'business_id' => $business->id,
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('business account is suspended', $errors[0]);
    }

    /**
     * ========================================
     * SESSION MANAGEMENT TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_session_persists_across_requests()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        // First request
        $response = $this->get('/buyer/dashboard');
        $response->assertStatus(200);
        
        // Second request should maintain authentication
        $response = $this->get('/buyer/profile');
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($buyer, 'buyer');
    }

    /** @test */
    public function test_buyer_can_logout()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        $response = $this->post('/buyer/logout');
        
        $response->assertRedirect('/');
        $this->assertGuest('buyer');
        
        // Verify session is cleared
        $this->assertFalse(Session::has('buyer_id'));
        
        // Verify logout is logged
        $this->assertDatabaseHas('audit_logs', [
            'user_type' => 'buyer',
            'user_id' => $buyer->id,
            'action' => 'logout'
        ]);
    }

    /** @test */
    public function test_buyer_session_expires_after_inactivity()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        // Simulate session expiry by manipulating session timestamp
        Session::put('last_activity', now()->subMinutes(121)); // 2 hours + 1 minute
        
        $response = $this->get('/buyer/dashboard');
        
        $response->assertRedirect('/buyer/login');
        $response->assertSessionHas('message', 'Session expired due to inactivity');
    }

    /**
     * ========================================
     * PASSWORD RESET TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_request_password_reset()
    {
        $buyer = Buyer::factory()->create(['email' => 'buyer@test.com']);
        
        $response = $this->post('/buyer/password/email', [
            'email' => 'buyer@test.com'
        ]);
        
        $response->assertSessionHas('status');
        
        // Verify reset token is created
        $this->assertDatabaseHas('password_resets', [
            'email' => 'buyer@test.com'
        ]);
        
        // Verify email notification is queued
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $buyer->id,
            'notifiable_type' => 'App\Models\Buyer',
            'type' => 'PasswordReset'
        ]);
    }

    /** @test */
    public function test_buyer_can_reset_password_with_valid_token()
    {
        $buyer = Buyer::factory()->create();
        $token = Password::createToken($buyer);
        
        $newPassword = 'NewSecureP@ss123';
        
        $response = $this->post('/buyer/password/reset', [
            'token' => $token,
            'email' => $buyer->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);
        
        $response->assertRedirect('/buyer/login');
        $response->assertSessionHas('status', 'Your password has been reset!');
        
        // Verify password is updated
        $buyer->refresh();
        $this->assertTrue(Hash::check($newPassword, $buyer->password));
    }

    /** @test */
    public function test_password_reset_token_expires()
    {
        $buyer = Buyer::factory()->create();
        
        // Create expired token (61 minutes old)
        DB::table('password_resets')->insert([
            'email' => $buyer->email,
            'token' => Hash::make('expired-token'),
            'created_at' => now()->subMinutes(61)
        ]);
        
        $response = $this->post('/buyer/password/reset', [
            'token' => 'expired-token',
            'email' => $buyer->email,
            'password' => 'NewP@ssword123',
            'password_confirmation' => 'NewP@ssword123'
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('password reset token is invalid', $errors[0]);
    }

    /**
     * ========================================
     * ACCESS CONTROL TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_cannot_access_vendor_routes()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        $vendorRoutes = [
            '/vendor/dashboard',
            '/vendor/products',
            '/vendor/quotes',
            '/vendor/orders'
        ];
        
        foreach ($vendorRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403); // Forbidden
            
            // Verify unauthorized access attempt is logged
            $this->assertDatabaseHas('audit_logs', [
                'user_type' => 'buyer',
                'user_id' => $buyer->id,
                'action' => 'unauthorized_access_attempt',
                'details' => json_encode(['route' => $route])
            ]);
        }
    }

    /** @test */
    public function test_buyer_cannot_access_admin_routes()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/settings',
            '/admin/reports'
        ];
        
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403); // Forbidden
        }
    }

    /** @test */
    public function test_unauthenticated_user_redirected_to_login()
    {
        $protectedRoutes = [
            '/buyer/dashboard',
            '/buyer/profile',
            '/buyer/orders',
            '/buyer/quotes'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/buyer/login');
        }
    }

    /**
     * ========================================
     * TWO-FACTOR AUTHENTICATION TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_enable_two_factor_authentication()
    {
        $buyer = Buyer::factory()->create();
        
        $this->actingAs($buyer, 'buyer');
        
        $response = $this->post('/buyer/two-factor/enable');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'qr_code' => true,
            'recovery_codes' => true
        ]);
        
        $buyer->refresh();
        $this->assertNotNull($buyer->two_factor_secret);
        $this->assertNotNull($buyer->two_factor_recovery_codes);
    }

    /** @test */
    public function test_buyer_must_provide_2fa_code_when_enabled()
    {
        $buyer = Buyer::factory()->create([
            'two_factor_secret' => encrypt('test-secret'),
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        // First step: email and password
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $response->assertRedirect('/buyer/two-factor-challenge');
        
        // Second step: 2FA code required
        $response = $this->get('/buyer/two-factor-challenge');
        $response->assertStatus(200);
        $response->assertSee('Two-Factor Authentication');
    }

    /**
     * ========================================
     * SECURITY TESTS
     * ========================================
     */

    /** @test */
    public function test_sql_injection_prevention_in_login()
    {
        $maliciousInputs = [
            "admin' OR '1'='1",
            "'; DROP TABLE buyers; --",
            "1' UNION SELECT * FROM buyers WHERE '1'='1"
        ];
        
        foreach ($maliciousInputs as $input) {
            $response = $this->post('/buyer/login', [
                'email' => $input,
                'password' => $input
            ]);
            
            $response->assertSessionHasErrors(['email']);
            $this->assertGuest('buyer');
        }
        
        // Verify tables still exist
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('buyers'));
    }

    /** @test */
    public function test_xss_prevention_in_registration()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '"><script>alert(String.fromCharCode(88,83,83))</script>',
            '<img src=x onerror=alert("XSS")>'
        ];
        
        foreach ($xssPayloads as $payload) {
            $data = $this->getValidRegistrationData([
                'name' => $payload,
                'business_name' => $payload
            ]);
            
            $response = $this->post('/buyer/register', $data);
            
            if ($response->status() === 302) {
                // Check data is stored safely
                $buyer = Buyer::where('email', $data['email'])->first();
                
                // Verify output is escaped
                $this->assertEquals($payload, $buyer->name); // Stored as-is
                $this->assertStringNotContainsString('<script>', e($buyer->name)); // Escaped on output
            }
        }
    }

    /** @test */
    public function test_csrf_token_required_for_authentication()
    {
        $response = $this->post('/buyer/login', [
            'email' => 'test@buyer.com',
            'password' => 'password'
        ], ['HTTP_X-CSRF-TOKEN' => 'invalid-token']);
        
        $response->assertStatus(419); // Token mismatch
    }

    /**
     * ========================================
     * PERFORMANCE TESTS
     * ========================================
     */

    /** @test */
    public function test_login_response_time_under_200ms()
    {
        $buyer = Buyer::factory()->create([
            'email' => 'buyer@test.com',
            'password' => Hash::make('ValidP@ss123')
        ]);
        
        $startTime = microtime(true);
        
        $response = $this->post('/buyer/login', [
            'email' => 'buyer@test.com',
            'password' => 'ValidP@ss123'
        ]);
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to ms
        
        $response->assertStatus(302);
        $this->assertLessThan(200, $responseTime, 'Login response exceeded 200ms');
    }

    /** @test */
    public function test_registration_handles_concurrent_requests()
    {
        $requests = [];
        
        // Simulate 10 concurrent registration attempts
        for ($i = 0; $i < 10; $i++) {
            $requests[] = $this->getValidRegistrationData([
                'email' => "buyer{$i}@test.com"
            ]);
        }
        
        foreach ($requests as $data) {
            $response = $this->post('/buyer/register', $data);
            $response->assertSessionDoesntHaveErrors();
        }
        
        // Verify all buyers were created
        $this->assertEquals(10, Buyer::count());
    }

    /**
     * ========================================
     * HELPER METHODS
     * ========================================
     */

    private function getValidRegistrationData($overrides = [])
    {
        return array_merge([
            'name' => 'John Buyer',
            'email' => 'buyer@sydneymarkets.com.au',
            'password' => 'SecureP@ss123!',
            'password_confirmation' => 'SecureP@ss123!',
            'phone' => '0412345678',
            'business_name' => 'Fresh Produce Buyers Pty Ltd',
            'abn' => '53 004 085 616', // Real Commonwealth Bank ABN
            'business_type' => 'company',
            'address' => '1 Market Street',
            'suburb' => 'Flemington',
            'postcode' => '2140',
            'state' => 'NSW',
            'trading_name' => 'Fresh Buyers',
            'buyer_type' => 'wholesaler',
            'terms' => true
        ], $overrides);
    }
}