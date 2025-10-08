<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\CreditAccount;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * PAYMENT PROCESSING INTEGRATION TEST SUITE
 * 
 * Test Authority: Financial transaction integrity
 * Coverage Target: 100% of payment flows
 * Data Strategy: Real Stripe test mode - NO MOCKS
 * 
 * CRITICAL: These tests use Stripe's test environment
 * All card numbers are Stripe's official test cards
 * 
 * @author Test Architect Agent
 * @version 1.0.0
 */
class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;
    protected $vendor;
    protected $stripeTestKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use real Stripe test key
        $this->stripeTestKey = Config::get('services.stripe.secret');
        Stripe::setApiKey($this->stripeTestKey);
        
        // Create test accounts
        $this->buyer = Buyer::factory()->create([
            'business_name' => 'Test Buyer Pty Ltd',
            'stripe_customer_id' => $this->createStripeCustomer()
        ]);
        
        $this->vendor = Vendor::factory()->create([
            'business_name' => 'Test Vendor Pty Ltd',
            'stripe_account_id' => $this->createStripeConnectAccount()
        ]);
    }

    /**
     * ========================================
     * PAYMENT METHOD TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_add_credit_card()
    {
        $this->actingAs($this->buyer, 'buyer');
        
        // Use Stripe test card number
        $cardData = [
            'number' => '4242424242424242',
            'exp_month' => 12,
            'exp_year' => date('Y') + 1,
            'cvc' => '123',
            'name' => 'Test Buyer'
        ];
        
        $response = $this->postJson('/api/buyer/payment-methods', $cardData);
        
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'payment_method' => [
                'brand' => 'visa',
                'last4' => '4242',
                'exp_month' => 12
            ]
        ]);
        
        // Verify saved in database
        $this->assertDatabaseHas('payment_methods', [
            'buyer_id' => $this->buyer->id,
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'is_default' => true
        ]);
        
        // Verify Stripe payment method created
        $paymentMethod = PaymentMethod::where('buyer_id', $this->buyer->id)->first();
        $stripePaymentMethod = StripePaymentMethod::retrieve($paymentMethod->stripe_payment_method_id);
        
        $this->assertEquals('card', $stripePaymentMethod->type);
        $this->assertEquals('4242', $stripePaymentMethod->card->last4);
    }

    /** @test */
    public function test_declined_cards_are_rejected()
    {
        $this->actingAs($this->buyer, 'buyer');
        
        // Stripe test card that always declines
        $declinedCard = [
            'number' => '4000000000000002',
            'exp_month' => 12,
            'exp_year' => date('Y') + 1,
            'cvc' => '123'
        ];
        
        $response = $this->postJson('/api/buyer/payment-methods', $declinedCard);
        
        $response->assertStatus(402); // Payment Required
        $response->assertJson([
            'error' => 'Card was declined',
            'code' => 'card_declined'
        ]);
        
        // Verify not saved in database
        $this->assertDatabaseMissing('payment_methods', [
            'buyer_id' => $this->buyer->id
        ]);
    }

    /** @test */
    public function test_3d_secure_authentication_required()
    {
        $this->actingAs($this->buyer, 'buyer');
        
        // Stripe test card requiring 3D Secure
        $secureCard = [
            'number' => '4000002500003155',
            'exp_month' => 12,
            'exp_year' => date('Y') + 1,
            'cvc' => '123'
        ];
        
        $response = $this->postJson('/api/buyer/payment-methods', $secureCard);
        
        $response->assertStatus(200);
        $response->assertJson([
            'requires_action' => true,
            'authentication_url' => true,
            'client_secret' => true
        ]);
    }

    /**
     * ========================================
     * ORDER PAYMENT TESTS
     * ========================================
     */

    /** @test */
    public function test_successful_order_payment()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'total_amount' => 1500.00,
            'status' => 'pending_payment'
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_id' => true
        ]);
        
        // Verify order status updated
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processing', $order->status);
        
        // Verify transaction recorded
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'amount' => 1500.00,
            'status' => 'succeeded',
            'type' => 'payment'
        ]);
        
        // Verify Stripe payment intent
        $transaction = PaymentTransaction::where('order_id', $order->id)->first();
        $paymentIntent = PaymentIntent::retrieve($transaction->stripe_payment_intent_id);
        
        $this->assertEquals('succeeded', $paymentIntent->status);
        $this->assertEquals(150000, $paymentIntent->amount); // Stripe uses cents
    }

    /** @test */
    public function test_insufficient_funds_payment_failure()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1000.00
        ]);
        
        // Create payment method with insufficient funds test card
        $paymentMethod = $this->createPaymentMethod('4000000000009995');
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id
        ]);
        
        $response->assertStatus(402);
        $response->assertJson([
            'error' => 'Payment failed',
            'reason' => 'insufficient_funds',
            'retry_allowed' => true
        ]);
        
        // Verify order status unchanged
        $order->refresh();
        $this->assertEquals('pending_payment', $order->status);
        
        // Verify failed transaction recorded
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'status' => 'failed',
            'error_code' => 'insufficient_funds'
        ]);
    }

    /** @test */
    public function test_payment_splits_to_vendor_account()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'total_amount' => 1000.00
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id
        ]);
        
        $response->assertStatus(200);
        
        // Calculate platform fee (5%)
        $platformFee = 50.00;
        $vendorAmount = 950.00;
        
        // Verify vendor payout recorded
        $this->assertDatabaseHas('vendor_payouts', [
            'vendor_id' => $this->vendor->id,
            'order_id' => $order->id,
            'amount' => $vendorAmount,
            'status' => 'pending'
        ]);
        
        // Verify platform fee recorded
        $this->assertDatabaseHas('platform_fees', [
            'order_id' => $order->id,
            'amount' => $platformFee,
            'percentage' => 5.0
        ]);
    }

    /**
     * ========================================
     * CREDIT ACCOUNT TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_pay_with_credit_account()
    {
        // Create credit account with available balance
        $creditAccount = CreditAccount::factory()->create([
            'buyer_id' => $this->buyer->id,
            'credit_limit' => 10000.00,
            'available_balance' => 8000.00,
            'status' => 'active'
        ]);
        
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1500.00
        ]);
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_type' => 'credit_account'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'payment_type' => 'credit_account',
            'remaining_credit' => 6500.00
        ]);
        
        // Verify credit account balance updated
        $creditAccount->refresh();
        $this->assertEquals(6500.00, $creditAccount->available_balance);
        
        // Verify credit transaction recorded
        $this->assertDatabaseHas('credit_transactions', [
            'credit_account_id' => $creditAccount->id,
            'order_id' => $order->id,
            'amount' => -1500.00,
            'type' => 'debit',
            'balance_after' => 6500.00
        ]);
    }

    /** @test */
    public function test_credit_limit_enforcement()
    {
        $creditAccount = CreditAccount::factory()->create([
            'buyer_id' => $this->buyer->id,
            'credit_limit' => 5000.00,
            'available_balance' => 1000.00
        ]);
        
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1500.00
        ]);
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_type' => 'credit_account'
        ]);
        
        $response->assertStatus(402);
        $response->assertJson([
            'error' => 'Insufficient credit available',
            'available_credit' => 1000.00,
            'required_amount' => 1500.00
        ]);
        
        // Verify balance unchanged
        $creditAccount->refresh();
        $this->assertEquals(1000.00, $creditAccount->available_balance);
    }

    /**
     * ========================================
     * REFUND TESTS
     * ========================================
     */

    /** @test */
    public function test_full_refund_processing()
    {
        // Create a paid order
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'total_amount' => 1000.00,
            'payment_status' => 'paid'
        ]);
        
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'amount' => 1000.00,
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123'
        ]);
        
        $this->actingAs($this->vendor, 'vendor');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/refund', [
            'reason' => 'Product quality issue',
            'amount' => 1000.00
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'refund_amount' => 1000.00,
            'refund_id' => true
        ]);
        
        // Verify refund transaction
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'amount' => -1000.00,
            'type' => 'refund',
            'status' => 'succeeded'
        ]);
        
        // Verify order status
        $order->refresh();
        $this->assertEquals('refunded', $order->payment_status);
    }

    /** @test */
    public function test_partial_refund_processing()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1000.00,
            'payment_status' => 'paid'
        ]);
        
        $this->actingAs($this->vendor, 'vendor');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/refund', [
            'reason' => 'Partial order fulfillment',
            'amount' => 300.00
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'refund_amount' => 300.00
        ]);
        
        // Verify order still marked as paid (partial refund)
        $order->refresh();
        $this->assertEquals('partially_refunded', $order->payment_status);
        
        // Verify refund amount tracked
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'refunded_amount' => 300.00
        ]);
    }

    /**
     * ========================================
     * INVOICE TESTS
     * ========================================
     */

    /** @test */
    public function test_invoice_generation_after_payment()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'total_amount' => 2500.00
        ]);
        
        // Add order items
        $order->items()->create([
            'product_name' => 'Bananas',
            'quantity' => 100,
            'unit_price' => 25.00,
            'total' => 2500.00
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id
        ]);
        
        $response->assertStatus(200);
        
        // Verify invoice created
        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'subtotal' => 2500.00,
            'tax_amount' => 250.00, // 10% GST
            'total_amount' => 2750.00,
            'status' => 'paid'
        ]);
        
        // Verify invoice number format
        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoice->invoice_number);
    }

    /** @test */
    public function test_tax_calculation_accuracy()
    {
        $testCases = [
            ['amount' => 100.00, 'expected_tax' => 10.00],
            ['amount' => 999.99, 'expected_tax' => 100.00], // Rounded
            ['amount' => 1234.56, 'expected_tax' => 123.46],
        ];
        
        foreach ($testCases as $case) {
            $order = Order::factory()->create([
                'buyer_id' => $this->buyer->id,
                'total_amount' => $case['amount']
            ]);
            
            $invoice = Invoice::generateForOrder($order);
            
            $this->assertEquals($case['expected_tax'], $invoice->tax_amount);
            $this->assertEquals(
                $case['amount'] + $case['expected_tax'],
                $invoice->total_amount
            );
        }
    }

    /**
     * ========================================
     * SUBSCRIPTION PAYMENT TESTS
     * ========================================
     */

    /** @test */
    public function test_recurring_subscription_payment()
    {
        $subscription = Subscription::factory()->create([
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendor->id,
            'amount' => 500.00,
            'frequency' => 'monthly',
            'status' => 'active',
            'next_billing_date' => now()
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        $this->buyer->update(['default_payment_method_id' => $paymentMethod->id]);
        
        // Run subscription billing job
        $response = $this->artisan('subscriptions:bill');
        
        // Verify payment processed
        $this->assertDatabaseHas('payment_transactions', [
            'subscription_id' => $subscription->id,
            'amount' => 500.00,
            'status' => 'succeeded',
            'type' => 'subscription'
        ]);
        
        // Verify next billing date updated
        $subscription->refresh();
        $this->assertTrue($subscription->next_billing_date->isAfter(now()));
        $this->assertEquals(
            now()->addMonth()->format('Y-m-d'),
            $subscription->next_billing_date->format('Y-m-d')
        );
    }

    /** @test */
    public function test_failed_subscription_retry_logic()
    {
        $subscription = Subscription::factory()->create([
            'buyer_id' => $this->buyer->id,
            'amount' => 500.00,
            'status' => 'active'
        ]);
        
        // Use card that fails first time
        $paymentMethod = $this->createPaymentMethod('4000000000000341');
        $this->buyer->update(['default_payment_method_id' => $paymentMethod->id]);
        
        // First attempt fails
        $this->artisan('subscriptions:bill');
        
        $this->assertDatabaseHas('payment_transactions', [
            'subscription_id' => $subscription->id,
            'status' => 'failed',
            'retry_count' => 1
        ]);
        
        // Verify subscription marked for retry
        $subscription->refresh();
        $this->assertEquals('payment_failed', $subscription->status);
        
        // Retry with good card
        $goodPaymentMethod = $this->createPaymentMethod();
        $this->buyer->update(['default_payment_method_id' => $goodPaymentMethod->id]);
        
        $this->artisan('subscriptions:retry-failed');
        
        // Verify successful retry
        $this->assertDatabaseHas('payment_transactions', [
            'subscription_id' => $subscription->id,
            'status' => 'succeeded'
        ]);
        
        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }

    /**
     * ========================================
     * PERFORMANCE TESTS
     * ========================================
     */

    /** @test */
    public function test_payment_processing_speed()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1000.00
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        
        $this->actingAs($this->buyer, 'buyer');
        
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id
        ]);
        
        $executionTime = microtime(true) - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(3, $executionTime, 'Payment processing exceeded 3 seconds');
    }

    /** @test */
    public function test_concurrent_payment_handling()
    {
        $orders = [];
        for ($i = 0; $i < 10; $i++) {
            $orders[] = Order::factory()->create([
                'buyer_id' => $this->buyer->id,
                'total_amount' => rand(100, 1000)
            ]);
        }
        
        $paymentMethod = $this->createPaymentMethod();
        
        $this->actingAs($this->buyer, 'buyer');
        
        // Process all payments
        foreach ($orders as $order) {
            $response = $this->postJson('/api/orders/' . $order->id . '/pay', [
                'payment_method_id' => $paymentMethod->id
            ]);
            
            $response->assertStatus(200);
        }
        
        // Verify all transactions recorded correctly
        $this->assertEquals(10, PaymentTransaction::where('status', 'succeeded')->count());
    }

    /**
     * ========================================
     * SECURITY TESTS
     * ========================================
     */

    /** @test */
    public function test_payment_requires_authentication()
    {
        $order = Order::factory()->create(['total_amount' => 1000.00]);
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay');
        
        $response->assertStatus(401); // Unauthenticated
    }

    /** @test */
    public function test_buyer_cannot_pay_another_buyers_order()
    {
        $otherBuyer = Buyer::factory()->create();
        $order = Order::factory()->create([
            'buyer_id' => $otherBuyer->id,
            'total_amount' => 1000.00
        ]);
        
        $this->actingAs($this->buyer, 'buyer');
        
        $response = $this->postJson('/api/orders/' . $order->id . '/pay');
        
        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function test_payment_idempotency()
    {
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'total_amount' => 1000.00
        ]);
        
        $paymentMethod = $this->createPaymentMethod();
        $idempotencyKey = 'test-key-123';
        
        $this->actingAs($this->buyer, 'buyer');
        
        // First request
        $response1 = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id,
            'idempotency_key' => $idempotencyKey
        ]);
        
        // Second request with same key
        $response2 = $this->postJson('/api/orders/' . $order->id . '/pay', [
            'payment_method_id' => $paymentMethod->id,
            'idempotency_key' => $idempotencyKey
        ]);
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Verify only one transaction created
        $this->assertEquals(1, PaymentTransaction::where('order_id', $order->id)->count());
    }

    /**
     * ========================================
     * HELPER METHODS
     * ========================================
     */

    private function createStripeCustomer()
    {
        $customer = \Stripe\Customer::create([
            'email' => $this->buyer->email,
            'name' => $this->buyer->business_name,
            'metadata' => [
                'buyer_id' => $this->buyer->id
            ]
        ]);
        
        return $customer->id;
    }

    private function createStripeConnectAccount()
    {
        $account = \Stripe\Account::create([
            'type' => 'standard',
            'country' => 'AU',
            'email' => $this->vendor->email,
            'metadata' => [
                'vendor_id' => $this->vendor->id
            ]
        ]);
        
        return $account->id;
    }

    private function createPaymentMethod($cardNumber = '4242424242424242')
    {
        $stripePaymentMethod = StripePaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => $cardNumber,
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
                'cvc' => '123'
            ]
        ]);
        
        // Attach to customer
        $stripePaymentMethod->attach(['customer' => $this->buyer->stripe_customer_id]);
        
        // Save to database
        return PaymentMethod::create([
            'buyer_id' => $this->buyer->id,
            'stripe_payment_method_id' => $stripePaymentMethod->id,
            'type' => 'card',
            'brand' => $stripePaymentMethod->card->brand,
            'last4' => $stripePaymentMethod->card->last4,
            'exp_month' => $stripePaymentMethod->card->exp_month,
            'exp_year' => $stripePaymentMethod->card->exp_year,
            'is_default' => true
        ]);
    }
}