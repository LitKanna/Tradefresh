<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\Business;
use App\Models\PaymentTerm;
use App\Models\CreditLimit;
use App\Events\PaymentProcessed;
use App\Events\PaymentFailed;
use App\Events\InvoiceGenerated;
use App\Events\PaymentOverdue;
use App\Jobs\ProcessRecurringPayment;
use App\Jobs\SendPaymentReminder;
use App\Jobs\ReconcilePayments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PaymentGatewayService
{
    protected array $gateways = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'square' => SquareGateway::class,
        'bank_transfer' => BankTransferGateway::class,
        'credit_terms' => CreditTermsGateway::class,
        'cod' => CashOnDeliveryGateway::class
    ];

    /**
     * Process payment for an order
     */
    public function processPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            // Validate payment method
            $paymentMethod = $this->validatePaymentMethod($order, $paymentData['method']);
            
            // Check for idempotency
            if (isset($paymentData['idempotency_key'])) {
                $existingPayment = Payment::where('idempotency_key', $paymentData['idempotency_key'])
                    ->where('status', 'completed')
                    ->first();
                
                if ($existingPayment) {
                    return $existingPayment;
                }
            }
            
            // Create payment record
            $payment = Payment::create([
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'payment_method' => $paymentData['method'],
                'amount' => $paymentData['amount'] ?? $order->getOutstandingAmount(),
                'currency' => $paymentData['currency'] ?? 'AUD',
                'status' => 'pending',
                'idempotency_key' => $paymentData['idempotency_key'] ?? Str::uuid(),
                'metadata' => [
                    'order_number' => $order->order_number,
                    'buyer_business' => $order->buyerBusiness->business_name,
                    'vendor' => $order->vendor->business_name
                ]
            ]);
            
            try {
                // Process based on payment method
                $result = $this->processPaymentByMethod($payment, $paymentData);
                
                if ($result['success']) {
                    // Update payment status
                    $payment->update([
                        'status' => 'completed',
                        'gateway_payment_id' => $result['transaction_id'] ?? null,
                        'gateway_response' => $result['response'] ?? null,
                        'processed_at' => now()
                    ]);
                    
                    // Update order payment status
                    $this->updateOrderPaymentStatus($order, $payment);
                    
                    // Create transaction record
                    $this->createTransaction($payment, 'credit');
                    
                    // Send confirmation
                    event(new PaymentProcessed($payment));
                    
                    // Generate invoice if needed
                    if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                        $this->generateInvoice($order);
                    }
                } else {
                    // Payment failed
                    $payment->update([
                        'status' => 'failed',
                        'failure_reason' => $result['error'] ?? 'Unknown error',
                        'gateway_response' => $result['response'] ?? null,
                        'failed_at' => now()
                    ]);
                    
                    event(new PaymentFailed($payment));
                    
                    throw new \Exception($result['error'] ?? 'Payment processing failed');
                }
                
                return $payment;
                
            } catch (\Exception $e) {
                // Log error
                Log::error('Payment processing error', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                
                // Update payment status
                $payment->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                    'failed_at' => now()
                ]);
                
                throw $e;
            }
        });
    }

    /**
     * Process payment by method
     */
    protected function processPaymentByMethod(Payment $payment, array $data): array
    {
        switch ($payment->payment_method) {
            case 'credit_card':
            case 'debit_card':
                return $this->processCardPayment($payment, $data);
                
            case 'credit_terms':
                return $this->processCreditTermsPayment($payment, $data);
                
            case 'bank_transfer':
                return $this->processBankTransfer($payment, $data);
                
            case 'cod':
                return $this->processCODPayment($payment, $data);
                
            case 'paypal':
                return $this->processPayPalPayment($payment, $data);
                
            default:
                throw new \Exception("Unsupported payment method: {$payment->payment_method}");
        }
    }

    /**
     * Process card payment
     */
    protected function processCardPayment(Payment $payment, array $data): array
    {
        // Use Stripe as default card processor
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        try {
            // Create payment intent
            $intent = $stripe->paymentIntents->create([
                'amount' => $payment->amount * 100, // Convert to cents
                'currency' => strtolower($payment->currency),
                'payment_method' => $data['payment_method_id'] ?? null,
                'confirm' => true,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->payable_id
                ]
            ]);
            
            if ($intent->status === 'succeeded') {
                return [
                    'success' => true,
                    'transaction_id' => $intent->id,
                    'response' => $intent->toArray()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment not completed',
                    'response' => $intent->toArray()
                ];
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => ['error' => $e->getError()->toArray()]
            ];
        }
    }

    /**
     * Process credit terms payment
     */
    protected function processCreditTermsPayment(Payment $payment, array $data): array
    {
        $order = $payment->payable;
        $buyer = $order->buyerBusiness;
        
        // Check credit limit
        $creditLimit = CreditLimit::where('business_id', $buyer->id)
            ->where('vendor_id', $order->vendor_id)
            ->first();
        
        if (!$creditLimit) {
            return [
                'success' => false,
                'error' => 'No credit terms established with this vendor'
            ];
        }
        
        // Check available credit
        $usedCredit = Payment::where('payment_method', 'credit_terms')
            ->whereHas('payable', function ($query) use ($buyer, $order) {
                $query->where('buyer_business_id', $buyer->id)
                    ->where('vendor_id', $order->vendor_id);
            })
            ->where('status', 'completed')
            ->whereNull('settled_at')
            ->sum('amount');
        
        $availableCredit = $creditLimit->credit_limit - $usedCredit;
        
        if ($payment->amount > $availableCredit) {
            return [
                'success' => false,
                'error' => "Insufficient credit. Available: \${$availableCredit}"
            ];
        }
        
        // Set payment terms
        $paymentTerms = PaymentTerm::find($creditLimit->payment_term_id);
        $dueDate = Carbon::now()->addDays($paymentTerms->days ?? 30);
        
        // Update payment with terms
        $payment->update([
            'payment_due_date' => $dueDate,
            'payment_terms' => $paymentTerms->name,
            'credit_limit_id' => $creditLimit->id
        ]);
        
        return [
            'success' => true,
            'transaction_id' => 'CREDIT-' . $payment->id,
            'response' => [
                'credit_limit' => $creditLimit->credit_limit,
                'available_credit' => $availableCredit - $payment->amount,
                'due_date' => $dueDate->toDateString(),
                'terms' => $paymentTerms->name
            ]
        ];
    }

    /**
     * Process bank transfer
     */
    protected function processBankTransfer(Payment $payment, array $data): array
    {
        // Generate unique reference
        $reference = 'BT-' . strtoupper(Str::random(10));
        
        // Create pending bank transfer record
        $payment->update([
            'gateway_payment_id' => $reference,
            'bank_reference' => $reference,
            'status' => 'awaiting_transfer'
        ]);
        
        // Bank account details for display
        $bankDetails = [
            'account_name' => 'Sydney Markets Pty Ltd',
            'bsb' => '123-456',
            'account_number' => '12345678',
            'reference' => $reference,
            'amount' => $payment->amount,
            'due_date' => Carbon::now()->addDays(3)->toDateString()
        ];
        
        return [
            'success' => true,
            'transaction_id' => $reference,
            'response' => $bankDetails
        ];
    }

    /**
     * Process Cash on Delivery payment
     */
    protected function processCODPayment(Payment $payment, array $data): array
    {
        $order = $payment->payable;
        
        // COD is only confirmed when order is delivered
        $payment->update([
            'status' => 'pending_delivery',
            'cod_collection_scheduled' => true
        ]);
        
        return [
            'success' => true,
            'transaction_id' => 'COD-' . $payment->id,
            'response' => [
                'message' => 'Payment will be collected on delivery',
                'estimated_delivery' => $order->expected_delivery_date
            ]
        ];
    }

    /**
     * Process PayPal payment
     */
    protected function processPayPalPayment(Payment $payment, array $data): array
    {
        // PayPal API integration would go here
        // This is a simplified example
        
        $paypalOrderId = $data['paypal_order_id'] ?? null;
        
        if (!$paypalOrderId) {
            return [
                'success' => false,
                'error' => 'PayPal order ID required'
            ];
        }
        
        // Verify with PayPal API
        // $response = Http::withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
        //     ->get("https://api.paypal.com/v2/checkout/orders/{$paypalOrderId}");
        
        return [
            'success' => true,
            'transaction_id' => $paypalOrderId,
            'response' => ['paypal_order_id' => $paypalOrderId]
        ];
    }

    /**
     * Generate invoice for order
     */
    public function generateInvoice(Order $order): Invoice
    {
        // Check if invoice already exists
        $existingInvoice = Invoice::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            return $existingInvoice;
        }
        
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber($order),
            'order_id' => $order->id,
            'buyer_business_id' => $order->buyer_business_id,
            'vendor_id' => $order->vendor_id,
            'invoice_date' => now(),
            'due_date' => $order->payment_due_date ?? now()->addDays(30),
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'discount_amount' => $order->discount_amount,
            'delivery_fee' => $order->delivery_fee,
            'total_amount' => $order->total_amount,
            'paid_amount' => $order->paid_amount,
            'status' => $order->isFullyPaid() ? 'paid' : 'pending',
            'buyer_details' => $this->getBuyerDetails($order),
            'vendor_details' => $this->getVendorDetails($order),
            'items' => $this->getInvoiceItems($order)
        ]);
        
        // Generate PDF
        $pdfPath = $this->generateInvoicePDF($invoice);
        $invoice->update(['pdf_path' => $pdfPath]);
        
        // Update order
        $order->update([
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date
        ]);
        
        // Send invoice notification
        event(new InvoiceGenerated($invoice));
        
        return $invoice;
    }

    /**
     * Generate invoice PDF
     */
    protected function generateInvoicePDF(Invoice $invoice): string
    {
        $data = [
            'invoice' => $invoice,
            'order' => $invoice->order,
            'buyer' => $invoice->buyerBusiness,
            'vendor' => $invoice->vendor,
            'items' => $invoice->order->items,
            'logo' => public_path('images/logo.png'),
            'terms' => $this->getInvoiceTerms()
        ];
        
        $pdf = Pdf::loadView('invoices.template', $data);
        
        $filename = "invoices/{$invoice->invoice_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());
        
        return $filename;
    }

    /**
     * Process refund
     */
    public function processRefund(Order $order, array $refundData): Payment
    {
        return DB::transaction(function () use ($order, $refundData) {
            // Find original payment
            $originalPayment = Payment::where('payable_type', Order::class)
                ->where('payable_id', $order->id)
                ->where('status', 'completed')
                ->latest()
                ->first();
            
            if (!$originalPayment) {
                throw new \Exception('No completed payment found for this order');
            }
            
            $refundAmount = $refundData['amount'] ?? $originalPayment->amount;
            
            // Validate refund amount
            if ($refundAmount > $originalPayment->amount) {
                throw new \Exception('Refund amount cannot exceed original payment amount');
            }
            
            // Create refund payment record
            $refund = Payment::create([
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'payment_method' => $originalPayment->payment_method,
                'amount' => -$refundAmount, // Negative amount for refund
                'currency' => $originalPayment->currency,
                'status' => 'pending',
                'type' => 'refund',
                'parent_payment_id' => $originalPayment->id,
                'reason' => $refundData['reason'] ?? null,
                'metadata' => [
                    'original_payment_id' => $originalPayment->id,
                    'refund_reason' => $refundData['reason'] ?? null,
                    'refunded_by' => auth()->id()
                ]
            ]);
            
            // Process refund based on payment method
            $result = $this->processRefundByMethod($refund, $originalPayment);
            
            if ($result['success']) {
                $refund->update([
                    'status' => 'completed',
                    'gateway_payment_id' => $result['refund_id'] ?? null,
                    'processed_at' => now()
                ]);
                
                // Update order payment status
                $order->paid_amount -= $refundAmount;
                
                if ($order->paid_amount <= 0) {
                    $order->payment_status = Order::PAYMENT_STATUS_REFUNDED;
                } else {
                    $order->payment_status = Order::PAYMENT_STATUS_PARTIALLY_PAID;
                }
                
                $order->save();
                
                // Create transaction
                $this->createTransaction($refund, 'debit');
                
                // Update order status if fully refunded
                if ($refundAmount >= $originalPayment->amount) {
                    $order->updateStatus(Order::STATUS_REFUNDED, $refundData['reason']);
                }
            } else {
                $refund->update([
                    'status' => 'failed',
                    'failure_reason' => $result['error'] ?? 'Refund failed'
                ]);
                
                throw new \Exception($result['error'] ?? 'Refund processing failed');
            }
            
            return $refund;
        });
    }

    /**
     * Process refund by method
     */
    protected function processRefundByMethod(Payment $refund, Payment $originalPayment): array
    {
        switch ($originalPayment->payment_method) {
            case 'credit_card':
            case 'debit_card':
                // Process card refund via Stripe
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                
                try {
                    $stripeRefund = $stripe->refunds->create([
                        'payment_intent' => $originalPayment->gateway_payment_id,
                        'amount' => abs($refund->amount) * 100, // Convert to cents
                        'reason' => 'requested_by_customer'
                    ]);
                    
                    return [
                        'success' => true,
                        'refund_id' => $stripeRefund->id
                    ];
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
                
            case 'credit_terms':
                // Create credit note
                return [
                    'success' => true,
                    'refund_id' => 'CN-' . $refund->id
                ];
                
            case 'bank_transfer':
                // Mark for manual refund
                return [
                    'success' => true,
                    'refund_id' => 'REFUND-' . $refund->id,
                    'requires_manual_processing' => true
                ];
                
            default:
                return [
                    'success' => false,
                    'error' => 'Refund not supported for this payment method'
                ];
        }
    }

    /**
     * Reconcile payments
     */
    public function reconcilePayments(array $filters = []): array
    {
        $query = Payment::with(['payable', 'transactions']);
        
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $payments = $query->get();
        
        $reconciliation = [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->where('amount', '>', 0)->sum('amount'),
            'total_refunds' => $payments->where('amount', '<', 0)->sum('amount'),
            'pending_payments' => $payments->where('status', 'pending')->count(),
            'failed_payments' => $payments->where('status', 'failed')->count(),
            'unreconciled' => []
        ];
        
        // Check for unreconciled payments
        foreach ($payments as $payment) {
            if ($payment->status === 'completed' && !$payment->reconciled_at) {
                $reconciliation['unreconciled'][] = [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'date' => $payment->created_at,
                    'method' => $payment->payment_method
                ];
            }
        }
        
        // Dispatch reconciliation job if needed
        if (count($reconciliation['unreconciled']) > 0) {
            ReconcilePayments::dispatch($reconciliation['unreconciled']);
        }
        
        return $reconciliation;
    }

    /**
     * Handle payment webhooks
     */
    public function handleWebhook(string $gateway, array $payload): array
    {
        switch ($gateway) {
            case 'stripe':
                return $this->handleStripeWebhook($payload);
            case 'paypal':
                return $this->handlePayPalWebhook($payload);
            default:
                throw new \Exception("Unknown gateway: {$gateway}");
        }
    }

    /**
     * Handle Stripe webhook
     */
    protected function handleStripeWebhook(array $payload): array
    {
        $event = $payload['type'] ?? null;
        
        switch ($event) {
            case 'payment_intent.succeeded':
                $paymentIntentId = $payload['data']['object']['id'];
                $payment = Payment::where('gateway_payment_id', $paymentIntentId)->first();
                
                if ($payment && $payment->status === 'pending') {
                    $payment->update([
                        'status' => 'completed',
                        'processed_at' => now()
                    ]);
                    
                    $this->updateOrderPaymentStatus($payment->payable, $payment);
                }
                break;
                
            case 'payment_intent.payment_failed':
                $paymentIntentId = $payload['data']['object']['id'];
                $payment = Payment::where('gateway_payment_id', $paymentIntentId)->first();
                
                if ($payment) {
                    $payment->update([
                        'status' => 'failed',
                        'failure_reason' => $payload['data']['object']['last_payment_error']['message'] ?? 'Unknown error',
                        'failed_at' => now()
                    ]);
                }
                break;
        }
        
        return ['success' => true];
    }

    /**
     * Handle PayPal webhook
     */
    protected function handlePayPalWebhook(array $payload): array
    {
        // PayPal webhook handling logic
        return ['success' => true];
    }

    /**
     * Update order payment status
     */
    protected function updateOrderPaymentStatus(Order $order, Payment $payment): void
    {
        $order->paid_amount += $payment->amount;
        
        if ($order->paid_amount >= $order->total_amount) {
            $order->payment_status = Order::PAYMENT_STATUS_PAID;
        } elseif ($order->paid_amount > 0) {
            $order->payment_status = Order::PAYMENT_STATUS_PARTIALLY_PAID;
        }
        
        $order->save();
    }

    /**
     * Create transaction record
     */
    protected function createTransaction(Payment $payment, string $type): Transaction
    {
        return Transaction::create([
            'transactionable_type' => get_class($payment->payable),
            'transactionable_id' => $payment->payable_id,
            'payment_id' => $payment->id,
            'type' => $type,
            'amount' => abs($payment->amount),
            'balance_before' => $this->getAccountBalance($payment->payable),
            'balance_after' => $this->getAccountBalance($payment->payable) + ($type === 'credit' ? $payment->amount : -$payment->amount),
            'description' => $type === 'credit' ? 'Payment received' : 'Refund processed',
            'reference_number' => $payment->gateway_payment_id,
            'processed_at' => now()
        ]);
    }

    /**
     * Get account balance
     */
    protected function getAccountBalance($entity): float
    {
        // Implementation would depend on accounting system
        return 0;
    }

    /**
     * Validate payment method
     */
    protected function validatePaymentMethod(Order $order, string $method): bool
    {
        $buyer = $order->buyerBusiness;
        
        // Check if payment method is allowed for this buyer
        $allowedMethods = $buyer->allowed_payment_methods ?? ['credit_card', 'bank_transfer'];
        
        if (!in_array($method, $allowedMethods)) {
            throw new \Exception("Payment method {$method} not allowed for this account");
        }
        
        // Check specific requirements
        if ($method === 'credit_terms') {
            $creditLimit = CreditLimit::where('business_id', $buyer->id)
                ->where('vendor_id', $order->vendor_id)
                ->where('status', 'active')
                ->first();
            
            if (!$creditLimit) {
                throw new \Exception('Credit terms not available for this vendor');
            }
        }
        
        return true;
    }

    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(Order $order): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $sequence = Invoice::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Get buyer details for invoice
     */
    protected function getBuyerDetails(Order $order): array
    {
        $buyer = $order->buyerBusiness;
        
        return [
            'business_name' => $buyer->business_name,
            'abn' => $buyer->abn,
            'address' => $buyer->billing_address ?? $buyer->primary_address,
            'email' => $buyer->email,
            'phone' => $buyer->phone
        ];
    }

    /**
     * Get vendor details for invoice
     */
    protected function getVendorDetails(Order $order): array
    {
        $vendor = $order->vendor;
        
        return [
            'business_name' => $vendor->business_name,
            'abn' => $vendor->vendorProfile->abn ?? null,
            'address' => $vendor->vendorProfile->business_address ?? null,
            'email' => $vendor->email,
            'phone' => $vendor->vendorProfile->phone ?? null
        ];
    }

    /**
     * Get invoice items
     */
    protected function getInvoiceItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'description' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount_amount,
                'tax' => $item->tax_amount,
                'total' => $item->total
            ];
        })->toArray();
    }

    /**
     * Get invoice terms
     */
    protected function getInvoiceTerms(): array
    {
        return [
            'payment_terms' => 'Net 30 days',
            'late_fee' => '1.5% per month on overdue amounts',
            'gst_included' => 'All prices include GST',
            'bank_details' => [
                'account_name' => 'Sydney Markets Pty Ltd',
                'bsb' => '123-456',
                'account_number' => '12345678'
            ]
        ];
    }

    /**
     * Check for overdue payments
     */
    public function checkOverduePayments(): Collection
    {
        $overduePayments = Payment::where('payment_method', 'credit_terms')
            ->where('status', 'completed')
            ->whereNull('settled_at')
            ->where('payment_due_date', '<', now())
            ->with(['payable', 'payable.buyerBusiness'])
            ->get();
        
        foreach ($overduePayments as $payment) {
            // Calculate overdue days
            $overdueDays = Carbon::parse($payment->payment_due_date)->diffInDays(now());
            
            // Send reminder based on overdue period
            if ($overdueDays === 1) {
                SendPaymentReminder::dispatch($payment, 'first_reminder');
            } elseif ($overdueDays === 7) {
                SendPaymentReminder::dispatch($payment, 'second_reminder');
            } elseif ($overdueDays === 14) {
                SendPaymentReminder::dispatch($payment, 'final_reminder');
            } elseif ($overdueDays >= 30) {
                // Escalate to collections
                event(new PaymentOverdue($payment, $overdueDays));
            }
        }
        
        return $overduePayments;
    }
}