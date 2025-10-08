<?php

namespace App\Services\Payment;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\Charge;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Subscription;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use App\Models\Vendor;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class StripeService
{
    protected $stripe;
    protected $commissionPercentage;
    protected $currency;
    
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
        $this->commissionPercentage = config('stripe.platform.commission_percentage');
        $this->currency = config('stripe.platform.currency');
    }
    
    /**
     * Create or retrieve a Stripe customer for a buyer
     */
    public function createOrGetCustomer(Buyer $buyer): Customer
    {
        if ($buyer->stripe_customer_id) {
            try {
                return Customer::retrieve($buyer->stripe_customer_id);
            } catch (ApiErrorException $e) {
                Log::warning('Failed to retrieve Stripe customer: ' . $e->getMessage());
            }
        }
        
        try {
            $customer = Customer::create([
                'email' => $buyer->email,
                'name' => $buyer->business_name ?: $buyer->name,
                'phone' => $buyer->phone,
                'metadata' => [
                    'buyer_id' => $buyer->id,
                    'abn' => $buyer->abn,
                    'business_type' => $buyer->business_type,
                ],
                'address' => [
                    'line1' => $buyer->address,
                    'city' => $buyer->suburb,
                    'state' => $buyer->state,
                    'postal_code' => $buyer->postcode,
                    'country' => 'AU',
                ],
            ]);
            
            $buyer->update(['stripe_customer_id' => $customer->id]);
            
            return $customer;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer: ' . $e->getMessage());
            throw new Exception('Failed to create payment customer');
        }
    }
    
    /**
     * Create a connected account for a vendor (Stripe Connect)
     */
    public function createVendorAccount(Vendor $vendor): Account
    {
        if ($vendor->stripe_account_id) {
            try {
                return Account::retrieve($vendor->stripe_account_id);
            } catch (ApiErrorException $e) {
                Log::warning('Failed to retrieve Stripe account: ' . $e->getMessage());
            }
        }
        
        try {
            $account = Account::create([
                'type' => 'express',
                'country' => 'AU',
                'email' => $vendor->email,
                'business_type' => 'company',
                'company' => [
                    'name' => $vendor->business_name,
                    'phone' => $vendor->phone,
                    'tax_id' => $vendor->abn,
                    'address' => [
                        'line1' => $vendor->address,
                        'city' => $vendor->suburb,
                        'state' => $vendor->state,
                        'postal_code' => $vendor->postcode,
                        'country' => 'AU',
                    ],
                ],
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                    'au_becs_debit_payments' => ['requested' => true],
                ],
                'business_profile' => [
                    'mcc' => '5499', // Miscellaneous Food Stores
                    'url' => config('app.url') . '/vendor/' . $vendor->slug,
                    'product_description' => 'Fresh produce and wholesale goods at Sydney Markets',
                ],
                'metadata' => [
                    'vendor_id' => $vendor->id,
                    'stall_number' => $vendor->stall_number,
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => config('stripe.payouts.schedule.interval'),
                            'delay_days' => config('stripe.payouts.schedule.delay_days'),
                        ],
                    ],
                ],
            ]);
            
            $vendor->update(['stripe_account_id' => $account->id]);
            
            return $account;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe account: ' . $e->getMessage());
            throw new Exception('Failed to create vendor payment account');
        }
    }
    
    /**
     * Generate account link for vendor onboarding
     */
    public function createAccountLink(Vendor $vendor): string
    {
        if (!$vendor->stripe_account_id) {
            $this->createVendorAccount($vendor);
        }
        
        try {
            $accountLink = \Stripe\AccountLink::create([
                'account' => $vendor->stripe_account_id,
                'refresh_url' => route('vendor.stripe.refresh'),
                'return_url' => route('vendor.stripe.return'),
                'type' => 'account_onboarding',
            ]);
            
            return $accountLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create account link: ' . $e->getMessage());
            throw new Exception('Failed to generate onboarding link');
        }
    }
    
    /**
     * Create a payment intent for an order
     */
    public function createPaymentIntent(Order $order, array $options = []): PaymentIntent
    {
        $buyer = $order->buyer;
        $vendor = $order->vendor;
        
        // Ensure customer exists
        $customer = $this->createOrGetCustomer($buyer);
        
        // Calculate platform fee
        $totalAmount = $order->total_amount * 100; // Convert to cents
        $platformFee = round($totalAmount * ($this->commissionPercentage / 100));
        
        $paymentIntentData = [
            'amount' => $totalAmount,
            'currency' => $this->currency,
            'customer' => $customer->id,
            'description' => "Order #{$order->order_number} - Sydney Markets",
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'buyer_id' => $buyer->id,
                'vendor_id' => $vendor->id,
            ],
            'application_fee_amount' => $platformFee,
            'transfer_data' => [
                'destination' => $vendor->stripe_account_id,
            ],
        ];
        
        // Add payment method types
        if (isset($options['payment_method_types'])) {
            $paymentIntentData['payment_method_types'] = $options['payment_method_types'];
        } else {
            $paymentIntentData['payment_method_types'] = ['card', 'au_becs_debit'];
        }
        
        // Handle payment on credit terms
        if (isset($options['credit_terms']) && $options['credit_terms']) {
            $paymentIntentData['capture_method'] = 'manual';
            $paymentIntentData['metadata']['payment_terms'] = $options['payment_terms'] ?? 7;
            $paymentIntentData['metadata']['due_date'] = now()->addDays($options['payment_terms'] ?? 7)->format('Y-m-d');
        }
        
        try {
            return PaymentIntent::create($paymentIntentData);
        } catch (ApiErrorException $e) {
            Log::error('Failed to create payment intent: ' . $e->getMessage());
            throw new Exception('Failed to initialize payment');
        }
    }
    
    /**
     * Create an invoice for credit terms payment
     */
    public function createInvoice(Order $order, int $daysUntilDue = 7): Invoice
    {
        $buyer = $order->buyer;
        $customer = $this->createOrGetCustomer($buyer);
        
        try {
            // Create invoice items
            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'customer' => $customer->id,
                    'amount' => $item->total_price * 100, // Convert to cents
                    'currency' => $this->currency,
                    'description' => "{$item->product->name} - Quantity: {$item->quantity} {$item->unit}",
                    'metadata' => [
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                    ],
                ]);
            }
            
            // Add delivery fee if applicable
            if ($order->delivery_fee > 0) {
                InvoiceItem::create([
                    'customer' => $customer->id,
                    'amount' => $order->delivery_fee * 100,
                    'currency' => $this->currency,
                    'description' => 'Delivery Fee',
                ]);
            }
            
            // Create the invoice
            $invoice = Invoice::create([
                'customer' => $customer->id,
                'auto_advance' => config('stripe.invoice.auto_advance'),
                'collection_method' => config('stripe.invoice.collection_method'),
                'days_until_due' => $daysUntilDue,
                'description' => "Order #{$order->order_number}",
                'footer' => config('stripe.invoice.footer'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'vendor_id' => $order->vendor_id,
                ],
                'on_behalf_of' => $order->vendor->stripe_account_id,
                'application_fee_percent' => $this->commissionPercentage,
            ]);
            
            // Send the invoice
            $invoice->sendInvoice();
            
            // Store invoice reference
            $order->update([
                'stripe_invoice_id' => $invoice->id,
                'payment_due_date' => now()->addDays($daysUntilDue),
            ]);
            
            return $invoice;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe invoice: ' . $e->getMessage());
            throw new Exception('Failed to create payment invoice');
        }
    }
    
    /**
     * Attach a payment method to a customer
     */
    public function attachPaymentMethod(string $paymentMethodId, Buyer $buyer): PaymentMethod
    {
        $customer = $this->createOrGetCustomer($buyer);
        
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customer->id]);
            
            // Set as default if it's the first payment method
            $existingMethods = PaymentMethod::all([
                'customer' => $customer->id,
                'type' => 'card',
            ]);
            
            if (count($existingMethods->data) === 1) {
                Customer::update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
            }
            
            return $paymentMethod;
        } catch (ApiErrorException $e) {
            Log::error('Failed to attach payment method: ' . $e->getMessage());
            throw new Exception('Failed to save payment method');
        }
    }
    
    /**
     * List payment methods for a buyer
     */
    public function listPaymentMethods(Buyer $buyer, string $type = 'card'): array
    {
        if (!$buyer->stripe_customer_id) {
            return [];
        }
        
        try {
            $paymentMethods = PaymentMethod::all([
                'customer' => $buyer->stripe_customer_id,
                'type' => $type,
            ]);
            
            return $paymentMethods->data;
        } catch (ApiErrorException $e) {
            Log::error('Failed to list payment methods: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process a refund
     */
    public function refund(Payment $payment, float $amount = null): \Stripe\Refund
    {
        try {
            $refundData = [
                'payment_intent' => $payment->stripe_payment_intent_id,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                ],
            ];
            
            if ($amount !== null) {
                $refundData['amount'] = $amount * 100; // Convert to cents
            }
            
            $refund = \Stripe\Refund::create($refundData);
            
            // Update payment record
            $payment->update([
                'status' => $amount === null ? 'refunded' : 'partially_refunded',
                'refunded_amount' => ($payment->refunded_amount ?? 0) + ($amount ?? $payment->amount),
                'refund_data' => array_merge($payment->refund_data ?? [], [
                    [
                        'id' => $refund->id,
                        'amount' => $refund->amount / 100,
                        'created_at' => now(),
                    ]
                ]),
            ]);
            
            return $refund;
        } catch (ApiErrorException $e) {
            Log::error('Failed to process refund: ' . $e->getMessage());
            throw new Exception('Failed to process refund');
        }
    }
    
    /**
     * Create a payout to vendor
     */
    public function createPayout(Vendor $vendor, float $amount): \Stripe\Payout
    {
        if (!$vendor->stripe_account_id) {
            throw new Exception('Vendor does not have a connected Stripe account');
        }
        
        try {
            $payout = \Stripe\Payout::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => $this->currency,
                'description' => 'Sydney Markets payout',
                'metadata' => [
                    'vendor_id' => $vendor->id,
                ],
            ], [
                'stripe_account' => $vendor->stripe_account_id,
            ]);
            
            return $payout;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create payout: ' . $e->getMessage());
            throw new Exception('Failed to create vendor payout');
        }
    }
    
    /**
     * Get account balance for a vendor
     */
    public function getVendorBalance(Vendor $vendor): array
    {
        if (!$vendor->stripe_account_id) {
            return [
                'available' => 0,
                'pending' => 0,
            ];
        }
        
        try {
            $balance = \Stripe\Balance::retrieve([], [
                'stripe_account' => $vendor->stripe_account_id,
            ]);
            
            $available = 0;
            $pending = 0;
            
            foreach ($balance->available as $balanceItem) {
                if ($balanceItem->currency === $this->currency) {
                    $available = $balanceItem->amount / 100;
                }
            }
            
            foreach ($balance->pending as $balanceItem) {
                if ($balanceItem->currency === $this->currency) {
                    $pending = $balanceItem->amount / 100;
                }
            }
            
            return [
                'available' => $available,
                'pending' => $pending,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to get vendor balance: ' . $e->getMessage());
            return [
                'available' => 0,
                'pending' => 0,
            ];
        }
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature): \Stripe\Event
    {
        try {
            return Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook.secret')
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload');
            throw new Exception('Invalid webhook payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature');
            throw new Exception('Invalid webhook signature');
        }
    }
    
    /**
     * Handle successful payment
     */
    public function handleSuccessfulPayment(PaymentIntent $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $orderId = $paymentIntent->metadata->order_id;
            $order = Order::find($orderId);
            
            if (!$order) {
                Log::error('Order not found for payment intent: ' . $paymentIntent->id);
                return;
            }
            
            // Create or update payment record
            $payment = Payment::updateOrCreate(
                ['stripe_payment_intent_id' => $paymentIntent->id],
                [
                    'order_id' => $order->id,
                    'buyer_id' => $order->buyer_id,
                    'vendor_id' => $order->vendor_id,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => 'completed',
                    'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                    'stripe_charge_id' => $paymentIntent->latest_charge,
                    'paid_at' => now(),
                    'metadata' => $paymentIntent->metadata->toArray(),
                ]
            );
            
            // Update order status
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'paid_at' => now(),
            ]);
            
            // Trigger order confirmation events
            event(new \App\Events\OrderPaid($order));
        });
    }
}