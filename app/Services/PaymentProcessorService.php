<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentProcessorService
{
    /**
     * Process a payment transaction
     */
    public function processPayment(PaymentTransaction $transaction, PaymentMethod $paymentMethod): array
    {
        try {
            switch ($paymentMethod->type) {
                case 'credit_card':
                case 'debit_card':
                    return $this->processCreditCardPayment($transaction, $paymentMethod);
                
                case 'ach':
                    return $this->processACHPayment($transaction, $paymentMethod);
                
                case 'paypal':
                    return $this->processPayPalPayment($transaction, $paymentMethod);
                
                case 'terms':
                    return $this->processTermsPayment($transaction, $paymentMethod);
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported payment method type',
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'payment_method_type' => $paymentMethod->type,
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process credit/debit card payment
     */
    private function processCreditCardPayment(PaymentTransaction $transaction, PaymentMethod $paymentMethod): array
    {
        // In a real application, this would integrate with Stripe, Square, or another processor
        // For demonstration purposes, we'll simulate the process

        // Check if card is expired
        if ($paymentMethod->is_expired) {
            return [
                'success' => false,
                'error' => 'Card has expired',
            ];
        }

        // Simulate random success/failure for demo
        $success = rand(1, 100) <= 95; // 95% success rate

        if ($success) {
            return [
                'success' => true,
                'response' => [
                    'processor_transaction_id' => 'stripe_' . uniqid(),
                    'authorization_code' => 'auth_' . rand(100000, 999999),
                    'processor' => 'stripe',
                    'card_brand' => $paymentMethod->card_brand,
                    'card_last_four' => $paymentMethod->card_last_four,
                    'processed_at' => now()->toISOString(),
                ]
            ];
        } else {
            $errors = [
                'Insufficient funds',
                'Card declined',
                'Invalid card number',
                'Processing error',
            ];

            return [
                'success' => false,
                'error' => $errors[array_rand($errors)],
            ];
        }
    }

    /**
     * Process ACH payment
     */
    private function processACHPayment(PaymentTransaction $transaction, PaymentMethod $paymentMethod): array
    {
        // ACH payments typically take 1-3 business days to process
        // In a real application, this would integrate with Stripe ACH, Plaid, or similar

        // Simulate success (ACH has higher success rate but takes longer)
        $success = rand(1, 100) <= 98; // 98% success rate

        if ($success) {
            return [
                'success' => true,
                'response' => [
                    'processor_transaction_id' => 'ach_' . uniqid(),
                    'processor' => 'stripe_ach',
                    'bank_name' => $paymentMethod->bank_name,
                    'account_last_four' => $paymentMethod->account_last_four,
                    'processing_days' => rand(1, 3),
                    'processed_at' => now()->toISOString(),
                ]
            ];
        } else {
            $errors = [
                'Insufficient funds',
                'Invalid account',
                'Account closed',
                'Bank declined',
            ];

            return [
                'success' => false,
                'error' => $errors[array_rand($errors)],
            ];
        }
    }

    /**
     * Process PayPal payment
     */
    private function processPayPalPayment(PaymentTransaction $transaction, PaymentMethod $paymentMethod): array
    {
        // In a real application, this would integrate with PayPal's API
        
        $success = rand(1, 100) <= 96; // 96% success rate

        if ($success) {
            return [
                'success' => true,
                'response' => [
                    'processor_transaction_id' => 'pp_' . uniqid(),
                    'processor' => 'paypal',
                    'paypal_email' => $paymentMethod->paypal_email,
                    'processed_at' => now()->toISOString(),
                ]
            ];
        } else {
            $errors = [
                'PayPal account issue',
                'Payment declined by PayPal',
                'Insufficient PayPal balance',
                'PayPal processing error',
            ];

            return [
                'success' => false,
                'error' => $errors[array_rand($errors)],
            ];
        }
    }

    /**
     * Process terms payment (credit-based)
     */
    private function processTermsPayment(PaymentTransaction $transaction, PaymentMethod $paymentMethod): array
    {
        // Check credit limit
        if ($paymentMethod->available_credit < $transaction->amount) {
            return [
                'success' => false,
                'error' => 'Insufficient credit limit',
            ];
        }

        // Terms payments are essentially always successful if credit is available
        return [
            'success' => true,
            'response' => [
                'processor_transaction_id' => 'terms_' . uniqid(),
                'processor' => 'internal_terms',
                'terms_days' => $paymentMethod->terms_days,
                'credit_used' => $transaction->amount,
                'remaining_credit' => $paymentMethod->available_credit - $transaction->amount,
                'processed_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Refund a transaction
     */
    public function refundTransaction(PaymentTransaction $transaction, float $amount = null): array
    {
        $refundAmount = $amount ?? $transaction->amount;

        if ($refundAmount > $transaction->amount) {
            return [
                'success' => false,
                'error' => 'Refund amount cannot exceed original transaction amount',
            ];
        }

        // Process refund based on original payment method
        switch ($transaction->payment_method_type) {
            case 'credit_card':
            case 'debit_card':
                return $this->refundCreditCard($transaction, $refundAmount);
            
            case 'ach':
                return $this->refundACH($transaction, $refundAmount);
            
            case 'paypal':
                return $this->refundPayPal($transaction, $refundAmount);
            
            case 'terms':
                return $this->refundTerms($transaction, $refundAmount);
            
            default:
                return [
                    'success' => false,
                    'error' => 'Refunds not supported for this payment method',
                ];
        }
    }

    /**
     * Refund credit card transaction
     */
    private function refundCreditCard(PaymentTransaction $transaction, float $amount): array
    {
        // Simulate refund processing
        $success = rand(1, 100) <= 97; // 97% success rate for refunds

        if ($success) {
            return [
                'success' => true,
                'response' => [
                    'refund_id' => 'refund_' . uniqid(),
                    'processor' => 'stripe',
                    'original_transaction_id' => $transaction->payment_reference,
                    'refund_amount' => $amount,
                    'processing_days' => rand(3, 7),
                    'processed_at' => now()->toISOString(),
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Refund processing failed',
            ];
        }
    }

    /**
     * Refund ACH transaction
     */
    private function refundACH(PaymentTransaction $transaction, float $amount): array
    {
        // ACH refunds can take longer
        return [
            'success' => true,
            'response' => [
                'refund_id' => 'ach_refund_' . uniqid(),
                'processor' => 'stripe_ach',
                'original_transaction_id' => $transaction->payment_reference,
                'refund_amount' => $amount,
                'processing_days' => rand(5, 10),
                'processed_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Refund PayPal transaction
     */
    private function refundPayPal(PaymentTransaction $transaction, float $amount): array
    {
        $success = rand(1, 100) <= 95; // 95% success rate

        if ($success) {
            return [
                'success' => true,
                'response' => [
                    'refund_id' => 'pp_refund_' . uniqid(),
                    'processor' => 'paypal',
                    'original_transaction_id' => $transaction->payment_reference,
                    'refund_amount' => $amount,
                    'processed_at' => now()->toISOString(),
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'PayPal refund failed',
            ];
        }
    }

    /**
     * Refund terms transaction
     */
    private function refundTerms(PaymentTransaction $transaction, float $amount): array
    {
        // Terms refunds are credit adjustments
        return [
            'success' => true,
            'response' => [
                'refund_id' => 'terms_refund_' . uniqid(),
                'processor' => 'internal_terms',
                'original_transaction_id' => $transaction->payment_reference,
                'refund_amount' => $amount,
                'credit_restored' => $amount,
                'processed_at' => now()->toISOString(),
            ]
        ];
    }
}