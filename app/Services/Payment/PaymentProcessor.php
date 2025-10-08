<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Buyer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\Invoice\InvoiceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentProcessor
{
    protected $stripeService;
    protected $creditManager;
    protected $invoiceGenerator;
    
    public function __construct(
        StripeService $stripeService,
        CreditManager $creditManager,
        InvoiceGenerator $invoiceGenerator
    ) {
        $this->stripeService = $stripeService;
        $this->creditManager = $creditManager;
        $this->invoiceGenerator = $invoiceGenerator;
    }
    
    /**
     * Process payment for an order
     */
    public function processOrderPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $paymentMethod = $paymentData['method'] ?? 'card';
            
            // Check if using credit terms
            if ($paymentMethod === 'credit_terms') {
                return $this->processCreditPayment($order, $paymentData);
            }
            
            // Check if cash on delivery
            if ($paymentMethod === 'cash_on_delivery') {
                return $this->processCashOnDelivery($order);
            }
            
            // Process online payment
            return $this->processOnlinePayment($order, $paymentData);
        });
    }
    
    /**
     * Process online payment (card, bank transfer, etc.)
     */
    protected function processOnlinePayment(Order $order, array $paymentData): Payment
    {
        try {
            // Create payment intent
            $paymentIntent = $this->stripeService->createPaymentIntent($order, [
                'payment_method_types' => [$paymentData['method'] ?? 'card'],
            ]);
            
            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'vendor_id' => $order->vendor_id,
                'amount' => $order->total_amount,
                'currency' => config('stripe.platform.currency'),
                'status' => 'pending',
                'payment_method' => $paymentData['method'] ?? 'card',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'metadata' => [
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);
            
            // Update order
            $order->update([
                'payment_status' => 'pending',
                'payment_method' => $paymentData['method'] ?? 'card',
            ]);
            
            return $payment;
        } catch (Exception $e) {
            Log::error('Failed to process online payment: ' . $e->getMessage());
            throw new Exception('Payment processing failed. Please try again.');
        }
    }
    
    /**
     * Process payment using credit terms
     */
    protected function processCreditPayment(Order $order, array $paymentData): Payment
    {
        $buyer = $order->buyer;
        $creditTerms = $paymentData['credit_terms'] ?? 7;
        
        // Check credit limit
        if (!$this->creditManager->checkCreditLimit($buyer, $order->total_amount)) {
            throw new Exception('Order exceeds available credit limit');
        }
        
        // Create Stripe invoice for credit payment
        $invoice = $this->stripeService->createInvoice($order, $creditTerms);
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => config('stripe.platform.currency'),
            'status' => 'pending',
            'payment_method' => 'credit_terms',
            'payment_terms' => $creditTerms,
            'due_date' => now()->addDays($creditTerms),
            'stripe_invoice_id' => $invoice->id,
            'metadata' => [
                'credit_terms' => $creditTerms,
                'credit_limit_before' => $this->creditManager->getAvailableCredit($buyer),
            ],
        ]);
        
        // Update credit usage
        $this->creditManager->useCredit($buyer, $order->total_amount);
        
        // Update order
        $order->update([
            'payment_status' => 'credit',
            'payment_method' => 'credit_terms',
            'payment_due_date' => now()->addDays($creditTerms),
        ]);
        
        // Generate invoice
        $this->invoiceGenerator->generateForOrder($order);
        
        return $payment;
    }
    
    /**
     * Process cash on delivery payment
     */
    protected function processCashOnDelivery(Order $order): Payment
    {
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'amount' => $order->total_amount,
            'currency' => config('stripe.platform.currency'),
            'status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'metadata' => [
                'collection_required' => true,
            ],
        ]);
        
        // Update order
        $order->update([
            'payment_status' => 'cod',
            'payment_method' => 'cash_on_delivery',
        ]);
        
        // Generate proforma invoice
        $this->invoiceGenerator->generateProforma($order);
        
        return $payment;
    }
    
    /**
     * Confirm cash payment on delivery
     */
    public function confirmCashPayment(Order $order, array $collectionData): Payment
    {
        $payment = $order->payment;
        
        if (!$payment || $payment->payment_method !== 'cash_on_delivery') {
            throw new Exception('Invalid payment method for cash confirmation');
        }
        
        DB::transaction(function () use ($payment, $order, $collectionData) {
            // Update payment
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'collected_by' => $collectionData['collected_by'] ?? null,
                    'collection_note' => $collectionData['note'] ?? null,
                    'collection_time' => now()->toDateTimeString(),
                ]),
            ]);
            
            // Create transaction record
            Transaction::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'vendor_id' => $order->vendor_id,
                'type' => 'payment',
                'amount' => $payment->amount,
                'status' => 'completed',
                'description' => 'Cash payment collected on delivery',
            ]);
            
            // Update order
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
            
            // Generate final invoice
            $this->invoiceGenerator->generateForOrder($order);
        });
        
        return $payment;
    }
    
    /**
     * Process refund
     */
    public function processRefund(Payment $payment, float $amount = null, string $reason = null): \Stripe\Refund
    {
        // Process refund through Stripe if it was an online payment
        if ($payment->stripe_payment_intent_id) {
            $refund = $this->stripeService->refund($payment, $amount);
            
            // Create transaction record
            Transaction::create([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'buyer_id' => $payment->buyer_id,
                'vendor_id' => $payment->vendor_id,
                'type' => 'refund',
                'amount' => $amount ?? $payment->amount,
                'status' => 'completed',
                'description' => $reason ?? 'Refund processed',
                'stripe_refund_id' => $refund->id,
            ]);
            
            // Update credit if it was a credit payment
            if ($payment->payment_method === 'credit_terms') {
                $this->creditManager->releaseCredit($payment->buyer, $amount ?? $payment->amount);
            }
            
            // Generate credit note
            $this->invoiceGenerator->generateCreditNote($payment->order, $amount ?? $payment->amount, $reason);
            
            return $refund;
        }
        
        // Handle cash refund
        return $this->processCashRefund($payment, $amount, $reason);
    }
    
    /**
     * Process cash refund
     */
    protected function processCashRefund(Payment $payment, float $amount = null, string $reason = null): \Stripe\Refund
    {
        DB::transaction(function () use ($payment, $amount, $reason) {
            $refundAmount = $amount ?? $payment->amount;
            
            // Update payment
            $payment->update([
                'status' => $amount === null ? 'refunded' : 'partially_refunded',
                'refunded_amount' => ($payment->refunded_amount ?? 0) + $refundAmount,
                'refund_data' => array_merge($payment->refund_data ?? [], [
                    [
                        'amount' => $refundAmount,
                        'reason' => $reason,
                        'created_at' => now(),
                        'type' => 'cash',
                    ]
                ]),
            ]);
            
            // Create transaction record
            Transaction::create([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'buyer_id' => $payment->buyer_id,
                'vendor_id' => $payment->vendor_id,
                'type' => 'refund',
                'amount' => $refundAmount,
                'status' => 'completed',
                'description' => $reason ?? 'Cash refund processed',
            ]);
            
            // Generate credit note
            $this->invoiceGenerator->generateCreditNote($payment->order, $refundAmount, $reason);
        });
        
        // Create mock refund object for consistency
        return (object) [
            'id' => 'cash_refund_' . uniqid(),
            'amount' => ($amount ?? $payment->amount) * 100,
            'currency' => $payment->currency,
            'status' => 'succeeded',
        ];
    }
    
    /**
     * Calculate payment fees
     */
    public function calculateFees(float $amount, string $paymentMethod): array
    {
        $fees = config("stripe.fees.{$paymentMethod}", [
            'percentage' => 0,
            'fixed' => 0,
        ]);
        
        $percentageFee = $amount * ($fees['percentage'] / 100);
        $fixedFee = $fees['fixed'] / 100; // Convert from cents
        $totalFee = $percentageFee + $fixedFee;
        
        $platformCommission = $amount * (config('stripe.platform.commission_percentage') / 100);
        
        return [
            'payment_fee' => round($totalFee, 2),
            'platform_commission' => round($platformCommission, 2),
            'vendor_payout' => round($amount - $totalFee - $platformCommission, 2),
            'breakdown' => [
                'percentage_fee' => round($percentageFee, 2),
                'fixed_fee' => round($fixedFee, 2),
            ],
        ];
    }
    
    /**
     * Get payment summary for an order
     */
    public function getPaymentSummary(Order $order): array
    {
        $payment = $order->payment;
        
        if (!$payment) {
            return [
                'status' => 'unpaid',
                'amount' => $order->total_amount,
                'paid_amount' => 0,
                'balance' => $order->total_amount,
            ];
        }
        
        $fees = $this->calculateFees($payment->amount, $payment->payment_method);
        
        return [
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'amount' => $payment->amount,
            'paid_amount' => $payment->status === 'completed' ? $payment->amount : 0,
            'refunded_amount' => $payment->refunded_amount ?? 0,
            'balance' => $payment->amount - ($payment->refunded_amount ?? 0),
            'fees' => $fees,
            'due_date' => $payment->due_date,
            'paid_at' => $payment->paid_at,
        ];
    }
}