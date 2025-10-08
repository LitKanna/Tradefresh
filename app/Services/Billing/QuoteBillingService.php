<?php

namespace App\Services\Billing;

use App\Modules\Quote\Models\Quote;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Buyer;
use App\Models\Invoice;
use App\Services\Payment\StripeService;
use App\Services\Invoice\InvoiceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class QuoteBillingService
{
    protected StripeService $stripeService;
    protected InvoiceGenerator $invoiceGenerator;
    
    public function __construct(
        StripeService $stripeService,
        InvoiceGenerator $invoiceGenerator
    ) {
        $this->stripeService = $stripeService;
        $this->invoiceGenerator = $invoiceGenerator;
    }
    
    /**
     * Process quote acceptance with billing
     */
    public function processQuoteAcceptance(Quote $quote, array $billingData): array
    {
        DB::beginTransaction();
        
        try {
            // Validate quote can be accepted
            $this->validateQuoteForAcceptance($quote);
            
            // Create order from quote
            $order = $this->createOrderFromQuote($quote, $billingData);
            
            // Process payment based on selected method
            $paymentResult = $this->processPayment($order, $billingData);
            
            // Generate invoice
            $invoice = $this->invoiceGenerator->generateForOrder($order);
            
            // Update quote status
            $quote->accept();
            
            // Send notifications
            $this->sendAcceptanceNotifications($quote, $order, $invoice);
            
            DB::commit();
            
            return [
                'success' => true,
                'order' => $order,
                'invoice' => $invoice,
                'payment' => $paymentResult['payment'] ?? null,
                'payment_intent' => $paymentResult['payment_intent'] ?? null,
                'requires_action' => $paymentResult['requires_action'] ?? false,
                'redirect_url' => $paymentResult['redirect_url'] ?? null,
                'message' => 'Quote accepted successfully and payment processed.'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Quote billing failed: ' . $e->getMessage(), [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Failed to process quote acceptance: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate quote can be accepted
     */
    protected function validateQuoteForAcceptance(Quote $quote): void
    {
        if (!in_array($quote->status, ['submitted'])) {
            throw new Exception('Quote cannot be accepted in current status: ' . $quote->status);
        }
        
        if ($quote->is_expired) {
            throw new Exception('Quote has expired and cannot be accepted');
        }
        
        if (!$quote->rfq) {
            throw new Exception('Quote is not associated with a valid RFQ');
        }
        
        if ($quote->rfq->status === 'closed') {
            throw new Exception('RFQ has already been closed');
        }
    }
    
    /**
     * Create order from accepted quote
     */
    protected function createOrderFromQuote(Quote $quote, array $billingData): Order
    {
        $buyer = Buyer::findOrFail($quote->rfq->buyer_id);
        
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'buyer_id' => $buyer->id,
            'vendor_id' => $quote->vendor_id,
            'status' => Order::STATUS_SUBMITTED,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'payment_method' => $billingData['payment_method'],
            'fulfillment_type' => $billingData['fulfillment_type'] ?? Order::FULFILLMENT_TYPE_PICKUP,
            'subtotal' => $quote->subtotal,
            'tax_amount' => $quote->tax_amount,
            'delivery_fee' => $quote->shipping_amount ?? 0,
            'discount_amount' => $quote->discount_amount ?? 0,
            'total_amount' => $quote->total_price,
            'notes' => $quote->notes,
            'vendor_notes' => $quote->vendor_notes,
            'expected_delivery_date' => $quote->delivery_date,
            'payment_terms_days' => $this->getPaymentTermsDays($billingData['payment_method']),
            'payment_due_date' => $this->calculatePaymentDueDate($billingData['payment_method']),
            'metadata' => [
                'quote_id' => $quote->id,
                'quote_reference' => $quote->reference_number,
                'rfq_id' => $quote->rfq_id,
                'billing_address' => $billingData['billing_address'] ?? null,
                'delivery_address' => $billingData['delivery_address'] ?? null,
                'purchase_order_number' => $billingData['purchase_order_number'] ?? null,
            ]
        ]);
        
        // Create order items from quote items
        foreach ($quote->items as $quoteItem) {
            $order->items()->create([
                'product_id' => $quoteItem->product_id,
                'product_name' => $quoteItem->product_name,
                'description' => $quoteItem->description,
                'quantity' => $quoteItem->quantity,
                'unit' => $quoteItem->unit,
                'unit_price' => $quoteItem->unit_price,
                'discount_percentage' => $quoteItem->discount_percentage ?? 0,
                'tax_percentage' => 10, // GST
                'total' => $quoteItem->total_price,
                'metadata' => $quoteItem->metadata
            ]);
        }
        
        return $order;
    }
    
    /**
     * Process payment based on selected method
     */
    protected function processPayment(Order $order, array $billingData): array
    {
        $paymentMethod = $billingData['payment_method'];
        
        switch ($paymentMethod) {
            case 'card':
                return $this->processCardPayment($order, $billingData);
                
            case 'au_becs_debit':
                return $this->processBankTransfer($order, $billingData);
                
            case 'credit_terms':
                return $this->processCreditTermsPayment($order, $billingData);
                
            case 'afterpay_clearpay':
                return $this->processAfterpayPayment($order, $billingData);
                
            default:
                throw new Exception('Unsupported payment method: ' . $paymentMethod);
        }
    }
    
    /**
     * Process card payment
     */
    protected function processCardPayment(Order $order, array $billingData): array
    {
        $buyer = $order->buyer;
        
        // Attach payment method if new
        if (isset($billingData['payment_method_id'])) {
            $this->stripeService->attachPaymentMethod($billingData['payment_method_id'], $buyer);
        }
        
        // Create payment intent
        $paymentIntent = $this->stripeService->createPaymentIntent($order, [
            'payment_method_types' => ['card'],
            'payment_method' => $billingData['payment_method_id'] ?? null,
            'confirm' => true,
            'return_url' => route('buyer.billing.return', ['order' => $order->id])
        ]);
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $buyer->id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => 'AUD',
            'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'processing',
            'payment_method' => 'card',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'metadata' => [
                'quote_id' => $order->metadata['quote_id'],
                'payment_intent_status' => $paymentIntent->status
            ]
        ]);
        
        // Handle 3D Secure or additional authentication
        if ($paymentIntent->status === 'requires_action') {
            return [
                'payment' => $payment,
                'payment_intent' => $paymentIntent,
                'requires_action' => true,
                'redirect_url' => $paymentIntent->next_action->redirect_to_url->url ?? null
            ];
        }
        
        // Update order if payment succeeded
        if ($paymentIntent->status === 'succeeded') {
            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'paid_amount' => $order->total_amount,
                'status' => Order::STATUS_CONFIRMED
            ]);
            
            $payment->update([
                'status' => 'completed',
                'paid_at' => now()
            ]);
        }
        
        return [
            'payment' => $payment,
            'payment_intent' => $paymentIntent,
            'requires_action' => false
        ];
    }
    
    /**
     * Process bank transfer payment
     */
    protected function processBankTransfer(Order $order, array $billingData): array
    {
        $buyer = $order->buyer;
        
        // Create payment intent for bank transfer
        $paymentIntent = $this->stripeService->createPaymentIntent($order, [
            'payment_method_types' => ['au_becs_debit'],
            'payment_method_options' => [
                'au_becs_debit' => [
                    'mandate_options' => [
                        'mandate_acceptance_date' => 'on_session'
                    ]
                ]
            ],
            'mandate_data' => [
                'customer_acceptance' => [
                    'type' => 'online',
                    'online' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ]
                ]
            ]
        ]);
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $buyer->id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => 'AUD',
            'status' => 'pending',
            'payment_method' => 'au_becs_debit',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'due_date' => now()->addDays(3), // Bank transfers typically take 2-3 days
            'metadata' => [
                'quote_id' => $order->metadata['quote_id'],
                'bsb' => $billingData['bsb'] ?? null,
                'account_number' => substr($billingData['account_number'] ?? '', -4)
            ]
        ]);
        
        return [
            'payment' => $payment,
            'payment_intent' => $paymentIntent,
            'requires_action' => false
        ];
    }
    
    /**
     * Process credit terms payment
     */
    protected function processCreditTermsPayment(Order $order, array $billingData): array
    {
        $creditTermsDays = $billingData['credit_terms_days'] ?? 7;
        $dueDate = now()->addDays($creditTermsDays);
        
        // Create Stripe invoice for credit terms
        $stripeInvoice = $this->stripeService->createInvoice($order, $creditTermsDays);
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => 'AUD',
            'status' => 'pending',
            'payment_method' => 'credit_terms',
            'payment_terms' => $creditTermsDays,
            'due_date' => $dueDate,
            'stripe_invoice_id' => $stripeInvoice->id,
            'metadata' => [
                'quote_id' => $order->metadata['quote_id'],
                'credit_limit' => $billingData['credit_limit'] ?? null,
                'approved_by' => $billingData['approved_by'] ?? null
            ]
        ]);
        
        // Update order with payment terms
        $order->update([
            'payment_terms_days' => $creditTermsDays,
            'payment_due_date' => $dueDate,
            'stripe_invoice_id' => $stripeInvoice->id
        ]);
        
        return [
            'payment' => $payment,
            'stripe_invoice' => $stripeInvoice,
            'requires_action' => false
        ];
    }
    
    /**
     * Process Afterpay payment
     */
    protected function processAfterpayPayment(Order $order, array $billingData): array
    {
        // Create Afterpay payment intent
        $paymentIntent = $this->stripeService->createPaymentIntent($order, [
            'payment_method_types' => ['afterpay_clearpay'],
            'payment_method_options' => [
                'afterpay_clearpay' => [
                    'reference' => $order->order_number
                ]
            ]
        ]);
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => 'AUD',
            'status' => 'processing',
            'payment_method' => 'afterpay_clearpay',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'metadata' => [
                'quote_id' => $order->metadata['quote_id']
            ]
        ]);
        
        return [
            'payment' => $payment,
            'payment_intent' => $paymentIntent,
            'requires_action' => true,
            'redirect_url' => $paymentIntent->next_action->redirect_to_url->url ?? null
        ];
    }
    
    /**
     * Get payment terms days based on payment method
     */
    protected function getPaymentTermsDays(string $paymentMethod): int
    {
        return match($paymentMethod) {
            'card' => 0,
            'au_becs_debit' => 3,
            'credit_terms' => 7,
            'afterpay_clearpay' => 0,
            default => 7
        };
    }
    
    /**
     * Calculate payment due date
     */
    protected function calculatePaymentDueDate(string $paymentMethod): ?Carbon
    {
        $days = $this->getPaymentTermsDays($paymentMethod);
        return $days > 0 ? now()->addDays($days) : null;
    }
    
    /**
     * Send acceptance notifications
     */
    protected function sendAcceptanceNotifications(Quote $quote, Order $order, Invoice $invoice): void
    {
        // Send email to buyer
        event(new \App\Events\Quote\QuoteAccepted($quote, $order));
        
        // Send invoice by email
        $this->invoiceGenerator->sendByEmail($invoice);
        
        // Send WhatsApp notification if enabled
        if ($order->buyer->preferences->whatsapp_notifications ?? false) {
            $this->invoiceGenerator->sendByWhatsApp($invoice);
        }
    }
    
    /**
     * Validate payment method availability for buyer
     */
    public function validatePaymentMethod(Buyer $buyer, string $paymentMethod): array
    {
        $available = true;
        $message = null;
        $requirements = [];
        
        switch ($paymentMethod) {
            case 'credit_terms':
                if (!$buyer->credit_approved) {
                    $available = false;
                    $message = 'Credit terms not available. Please contact support to apply.';
                    $requirements = ['credit_application', 'business_verification'];
                }
                break;
                
            case 'afterpay_clearpay':
                if ($buyer->total_amount > 2000) {
                    $available = false;
                    $message = 'Afterpay is only available for orders under $2,000.';
                }
                break;
                
            case 'au_becs_debit':
                if (!$buyer->bank_account_verified) {
                    $requirements = ['bank_account_verification'];
                }
                break;
        }
        
        return [
            'available' => $available,
            'message' => $message,
            'requirements' => $requirements
        ];
    }
    
    /**
     * Get available payment methods for quote
     */
    public function getAvailablePaymentMethods(Quote $quote): array
    {
        $buyer = Buyer::findOrFail($quote->rfq->buyer_id);
        $methods = [];
        
        // Card payment always available
        $methods[] = [
            'id' => 'card',
            'name' => 'Credit/Debit Card',
            'description' => 'Pay instantly with credit or debit card',
            'fee' => 0,
            'processing_time' => 'Instant',
            'icon' => 'credit-card',
            'available' => true
        ];
        
        // Bank transfer
        $methods[] = [
            'id' => 'au_becs_debit',
            'name' => 'Bank Transfer',
            'description' => 'Direct debit from Australian bank account',
            'fee' => 0,
            'processing_time' => '2-3 business days',
            'icon' => 'building-columns',
            'available' => true
        ];
        
        // Credit terms (if approved)
        $creditValidation = $this->validatePaymentMethod($buyer, 'credit_terms');
        $methods[] = [
            'id' => 'credit_terms',
            'name' => 'Credit Terms',
            'description' => 'Pay on account with approved credit terms',
            'fee' => 0,
            'processing_time' => '7-30 days terms',
            'icon' => 'file-invoice',
            'available' => $creditValidation['available'],
            'message' => $creditValidation['message']
        ];
        
        // Afterpay (for eligible amounts)
        if ($quote->total_price <= 2000) {
            $methods[] = [
                'id' => 'afterpay_clearpay',
                'name' => 'Afterpay',
                'description' => 'Buy now, pay later in 4 installments',
                'fee' => 0,
                'processing_time' => 'Instant approval',
                'icon' => 'calendar-days',
                'available' => true
            ];
        }
        
        return $methods;
    }
}