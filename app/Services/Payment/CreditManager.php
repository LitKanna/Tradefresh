<?php

namespace App\Services\Payment;

use App\Models\Buyer;
use App\Models\CreditAccount;
use App\Models\Payment;
use App\Models\Order;
use App\Jobs\SendPaymentReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class CreditManager
{
    /**
     * Get or create credit account for buyer
     */
    public function getCreditAccount(Buyer $buyer): CreditAccount
    {
        return CreditAccount::firstOrCreate(
            ['buyer_id' => $buyer->id],
            [
                'credit_limit' => $this->calculateInitialCreditLimit($buyer),
                'used_credit' => 0,
                'available_credit' => $this->calculateInitialCreditLimit($buyer),
                'status' => 'active',
                'payment_terms' => 7, // Default 7 days
                'late_fee_percentage' => config('stripe.credit_terms.late_fee_percentage', 2),
            ]
        );
    }
    
    /**
     * Calculate initial credit limit based on buyer profile
     */
    protected function calculateInitialCreditLimit(Buyer $buyer): float
    {
        // Base limit
        $baseLimit = 5000; // AUD $5,000
        
        // Adjust based on business type
        $businessTypeMultipliers = [
            'restaurant' => 1.5,
            'retail_chain' => 2.0,
            'hotel' => 1.8,
            'catering' => 1.3,
            'wholesaler' => 2.5,
            'individual' => 0.5,
        ];
        
        $multiplier = $businessTypeMultipliers[$buyer->business_type] ?? 1.0;
        
        // Additional factors
        if ($buyer->years_in_business > 5) {
            $multiplier *= 1.2;
        }
        
        if ($buyer->verified_at) {
            $multiplier *= 1.1;
        }
        
        return round($baseLimit * $multiplier, 2);
    }
    
    /**
     * Check if buyer has sufficient credit
     */
    public function checkCreditLimit(Buyer $buyer, float $amount): bool
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        if ($creditAccount->status !== 'active') {
            return false;
        }
        
        return $creditAccount->available_credit >= $amount;
    }
    
    /**
     * Get available credit for buyer
     */
    public function getAvailableCredit(Buyer $buyer): float
    {
        $creditAccount = $this->getCreditAccount($buyer);
        return $creditAccount->available_credit;
    }
    
    /**
     * Use credit for an order
     */
    public function useCredit(Buyer $buyer, float $amount): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        if (!$this->checkCreditLimit($buyer, $amount)) {
            throw new Exception('Insufficient credit limit');
        }
        
        DB::transaction(function () use ($creditAccount, $amount) {
            $creditAccount->update([
                'used_credit' => $creditAccount->used_credit + $amount,
                'available_credit' => $creditAccount->available_credit - $amount,
                'last_used_at' => now(),
            ]);
            
            // Log credit usage
            $creditAccount->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $creditAccount->available_credit,
                'description' => 'Credit used for order',
            ]);
        });
        
        return $creditAccount->fresh();
    }
    
    /**
     * Release credit (for refunds or cancellations)
     */
    public function releaseCredit(Buyer $buyer, float $amount): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        DB::transaction(function () use ($creditAccount, $amount) {
            $creditAccount->update([
                'used_credit' => max(0, $creditAccount->used_credit - $amount),
                'available_credit' => min($creditAccount->credit_limit, $creditAccount->available_credit + $amount),
            ]);
            
            // Log credit release
            $creditAccount->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $creditAccount->available_credit,
                'description' => 'Credit released',
            ]);
        });
        
        return $creditAccount->fresh();
    }
    
    /**
     * Process payment for credit account
     */
    public function processPayment(Buyer $buyer, float $amount, array $paymentDetails = []): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        DB::transaction(function () use ($creditAccount, $amount, $paymentDetails) {
            // Release credit
            $this->releaseCredit($creditAccount->buyer, $amount);
            
            // Log payment
            $creditAccount->transactions()->create([
                'type' => 'payment',
                'amount' => $amount,
                'balance_after' => $creditAccount->available_credit,
                'description' => 'Payment received',
                'payment_reference' => $paymentDetails['reference'] ?? null,
                'payment_method' => $paymentDetails['method'] ?? 'bank_transfer',
            ]);
            
            // Update last payment date
            $creditAccount->update([
                'last_payment_at' => now(),
                'last_payment_amount' => $amount,
            ]);
        });
        
        return $creditAccount->fresh();
    }
    
    /**
     * Update credit limit
     */
    public function updateCreditLimit(Buyer $buyer, float $newLimit, string $reason = null): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        $oldLimit = $creditAccount->credit_limit;
        
        DB::transaction(function () use ($creditAccount, $newLimit, $oldLimit, $reason) {
            $limitDifference = $newLimit - $oldLimit;
            
            $creditAccount->update([
                'credit_limit' => $newLimit,
                'available_credit' => max(0, $creditAccount->available_credit + $limitDifference),
            ]);
            
            // Log credit limit change
            $creditAccount->transactions()->create([
                'type' => 'adjustment',
                'amount' => $limitDifference,
                'balance_after' => $creditAccount->available_credit,
                'description' => $reason ?? 'Credit limit adjusted',
                'metadata' => [
                    'old_limit' => $oldLimit,
                    'new_limit' => $newLimit,
                ],
            ]);
        });
        
        return $creditAccount->fresh();
    }
    
    /**
     * Get overdue payments
     */
    public function getOverduePayments(Buyer $buyer = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Payment::where('payment_method', 'credit_terms')
            ->where('status', 'pending')
            ->where('due_date', '<', now());
        
        if ($buyer) {
            $query->where('buyer_id', $buyer->id);
        }
        
        return $query->with(['order', 'buyer', 'vendor'])->get();
    }
    
    /**
     * Calculate late fees for overdue payment
     */
    public function calculateLateFee(Payment $payment): float
    {
        if ($payment->status !== 'pending' || !$payment->due_date) {
            return 0;
        }
        
        $daysOverdue = Carbon::parse($payment->due_date)->diffInDays(now());
        
        if ($daysOverdue <= config('stripe.credit_terms.grace_period_days', 3)) {
            return 0; // Still in grace period
        }
        
        $creditAccount = $this->getCreditAccount($payment->buyer);
        $lateFeePercentage = $creditAccount->late_fee_percentage;
        
        // Calculate late fee (compound for multiple periods)
        $periods = ceil($daysOverdue / 30); // Monthly compounding
        $lateFee = $payment->amount * ($lateFeePercentage / 100) * $periods;
        
        return round($lateFee, 2);
    }
    
    /**
     * Apply late fees to overdue payments
     */
    public function applyLateFees(): void
    {
        $overduePayments = $this->getOverduePayments();
        
        foreach ($overduePayments as $payment) {
            $lateFee = $this->calculateLateFee($payment);
            
            if ($lateFee > 0 && (!$payment->late_fee_applied || $payment->late_fee_amount < $lateFee)) {
                $payment->update([
                    'late_fee_amount' => $lateFee,
                    'late_fee_applied' => true,
                    'total_due' => $payment->amount + $lateFee,
                ]);
                
                // Send late payment notification
                SendPaymentReminder::dispatch($payment, 'overdue');
            }
        }
    }
    
    /**
     * Get credit statement for buyer
     */
    public function getCreditStatement(Buyer $buyer, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();
        
        $transactions = $creditAccount->transactions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $payments = Payment::where('buyer_id', $buyer->id)
            ->where('payment_method', 'credit_terms')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        $totalDebits = $transactions->where('type', 'debit')->sum('amount');
        $totalCredits = $transactions->where('type', 'credit')->sum('amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        
        $outstandingAmount = $payments->where('status', 'pending')->sum('amount');
        $overdueAmount = $payments->filter(function ($payment) {
            return $payment->status === 'pending' && $payment->due_date < now();
        })->sum('amount');
        
        return [
            'account' => [
                'credit_limit' => $creditAccount->credit_limit,
                'used_credit' => $creditAccount->used_credit,
                'available_credit' => $creditAccount->available_credit,
                'status' => $creditAccount->status,
            ],
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_purchases' => $totalDebits,
                'total_credits' => $totalCredits,
                'total_payments' => $totalPayments,
                'outstanding_amount' => $outstandingAmount,
                'overdue_amount' => $overdueAmount,
            ],
            'transactions' => $transactions,
            'payments' => $payments,
        ];
    }
    
    /**
     * Suspend credit account
     */
    public function suspendAccount(Buyer $buyer, string $reason = null): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        $creditAccount->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
        
        // Log suspension
        $creditAccount->transactions()->create([
            'type' => 'note',
            'amount' => 0,
            'balance_after' => $creditAccount->available_credit,
            'description' => 'Account suspended: ' . ($reason ?? 'No reason provided'),
        ]);
        
        return $creditAccount;
    }
    
    /**
     * Reactivate credit account
     */
    public function reactivateAccount(Buyer $buyer): CreditAccount
    {
        $creditAccount = $this->getCreditAccount($buyer);
        
        $creditAccount->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
        
        // Log reactivation
        $creditAccount->transactions()->create([
            'type' => 'note',
            'amount' => 0,
            'balance_after' => $creditAccount->available_credit,
            'description' => 'Account reactivated',
        ]);
        
        return $creditAccount;
    }
}