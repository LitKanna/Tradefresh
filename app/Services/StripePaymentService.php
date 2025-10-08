<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class StripePaymentService
{
    protected $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create a payment intent for an order
     */
    public function createPaymentIntent(Order $order, array $options = []): array
    {
        try {
            // Get or create Stripe customer
            $stripeCustomer = $this->getOrCreateCustomer($order->buyer);

            // Calculate amount in cents
            $amount = (int) ($order->total_amount * 100);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => strtolower($order->currency ?? 'aud'),
                'customer' => $stripeCustomer->id,
                'description' => "Order #{$order->order_number}",
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'buyer_id' => $order->buyer_id,
                    'seller_id' => $order->seller_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'receipt_email' => $order->billing_email,
                ...$options
            ]);

            // Create payment record
            $payment = $this->createPaymentRecord($order, [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'payment_method' => 'card',
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
                'payment' => $payment,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getError(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            Log::error('Payment Intent Creation Error: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create payment intent',
            ];
        }
    }

    /**
     * Process payment confirmation
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            // Retrieve the payment intent
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Find the payment record
                $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
                
                if (!$payment) {
                    throw new Exception('Payment record not found');
                }

                // Update payment status
                $payment->update([
                    'status' => 'succeeded',
                    'processed_at' => now(),
                    'stripe_charge_id' => $paymentIntent->latest_charge,
                ]);

                // Update order
                $order = Order::find($payment->order_id);
                if ($order) {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'status' => 'paid',
                    ]);

                    // Generate invoice
                    $this->generateInvoice($order, $payment);
                }

                return [
                    'success' => true,
                    'payment' => $payment,
                    'order' => $order,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment not completed',
                'status' => $paymentIntent->status,
            ];

        } catch (Exception $e) {
            Log::error('Payment Confirmation Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Payment $payment, float $amount = null): array
    {
        try {
            if (!$payment->stripe_charge_id) {
                throw new Exception('No charge ID found for this payment');
            }

            $refundAmount = $amount ?? $payment->amount;
            $refundAmountCents = (int) ($refundAmount * 100);

            // Create refund in Stripe
            $refund = Refund::create([
                'charge' => $payment->stripe_charge_id,
                'amount' => $refundAmountCents,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                ],
            ]);

            // Update payment record
            $payment->update([
                'stripe_refund_id' => $refund->id,
                'status' => $amount && $amount < $payment->amount ? 'partial_refund' : 'refunded',
            ]);

            // Create refund payment record
            $refundPayment = $this->createPaymentRecord($payment->order, [
                'type' => 'refund',
                'amount' => -$refundAmount,
                'stripe_refund_id' => $refund->id,
                'status' => 'succeeded',
                'processed_at' => now(),
            ]);

            // Update order status
            if ($payment->order) {
                $payment->order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            }

            return [
                'success' => true,
                'refund' => $refund,
                'payment' => $refundPayment,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            Log::error('Refund Processing Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateCustomer($user): Customer
    {
        // Check if user has stripe customer ID
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (ApiErrorException $e) {
                Log::warning('Failed to retrieve Stripe customer: ' . $e->getMessage());
            }
        }

        // Create new customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->company_name ?? $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'abn' => $user->abn ?? '',
            ],
        ]);

        // Save customer ID to user
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create payment record
     */
    protected function createPaymentRecord($order, array $data): Payment
    {
        return Payment::create([
            'payment_number' => $this->generatePaymentNumber(),
            'order_id' => $order->id,
            'user_id' => $order->buyer_id,
            'type' => $data['type'] ?? 'payment',
            'status' => $data['status'] ?? 'pending',
            'amount' => $data['amount'],
            'currency' => $order->currency ?? 'AUD',
            'payment_method' => $data['payment_method'] ?? 'card',
            'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
            'stripe_charge_id' => $data['stripe_charge_id'] ?? null,
            'stripe_refund_id' => $data['stripe_refund_id'] ?? null,
            'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
            'processed_at' => $data['processed_at'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Generate unique payment number
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Generate invoice for paid order
     */
    protected function generateInvoice(Order $order, Payment $payment): Invoice
    {
        // Get quote items for line items
        $lineItems = [];
        if ($order->quote && $order->quote->items) {
            foreach ($order->quote->items as $item) {
                $lineItems[] = [
                    'description' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price,
                ];
            }
        }

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'seller_id' => $order->seller_id,
            'status' => 'paid',
            'type' => 'invoice',
            'issue_date' => now(),
            'due_date' => now(),
            'paid_at' => now(),
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'shipping_cost' => $order->shipping_cost,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'paid_amount' => $order->total_amount,
            'balance_due' => 0,
            'currency' => $order->currency,
            'tax_rate' => 10, // GST
            'is_tax_inclusive' => true,
            'payment_terms_days' => 0,
            'seller_company_name' => $order->seller->company_name ?? $order->seller->name,
            'seller_abn' => $order->seller->abn ?? '',
            'seller_address' => $order->seller->address ?? '',
            'seller_email' => $order->seller->email,
            'seller_phone' => $order->seller->phone ?? '',
            'buyer_company_name' => $order->billing_company_name,
            'buyer_abn' => $order->billing_abn,
            'buyer_address' => $order->billing_address,
            'buyer_email' => $order->billing_email,
            'buyer_phone' => $order->billing_phone,
            'line_items' => $lineItems,
            'notes' => $order->notes,
            'metadata' => [
                'payment_id' => $payment->id,
                'payment_method' => $payment->payment_method,
            ],
        ]);

        return $invoice;
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/INV-(\d{4})(\d{2})-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $sequence = intval($matches[3]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }

    /**
     * Create checkout session for complex payment flows
     */
    public function createCheckoutSession(Order $order, array $options = []): array
    {
        try {
            $lineItems = [];

            // Add order items
            if ($order->quote && $order->quote->items) {
                foreach ($order->quote->items as $item) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => strtolower($order->currency ?? 'aud'),
                            'product_data' => [
                                'name' => $item->product_name,
                                'description' => $item->description ?? '',
                            ],
                            'unit_amount' => (int) ($item->unit_price * 100),
                        ],
                        'quantity' => $item->quantity,
                    ];
                }
            }

            // Add shipping if applicable
            if ($order->shipping_cost > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => strtolower($order->currency ?? 'aud'),
                        'product_data' => [
                            'name' => 'Shipping & Handling',
                        ],
                        'unit_amount' => (int) ($order->shipping_cost * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/checkout/cancel'),
                'customer_email' => $order->billing_email,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                ...$options
            ]);

            return [
                'success' => true,
                'session' => $session,
                'url' => $session->url,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout Session Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate payment method
     */
    public function validatePaymentMethod(string $paymentMethodId): array
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

            return [
                'success' => true,
                'payment_method' => $paymentMethod,
                'type' => $paymentMethod->type,
                'card' => $paymentMethod->card ?? null,
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): array
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            return [
                'success' => true,
                'payment_method' => $paymentMethod,
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}