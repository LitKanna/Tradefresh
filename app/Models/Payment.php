<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_id',
        'buyer_id',
        'vendor_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_terms',
        'due_date',
        'paid_at',
        'reference_number',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'stripe_refund_id',
        'refunded_amount',
        'refund_data',
        'late_fee_amount',
        'late_fee_applied',
        'total_due',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'late_fee_applied' => 'boolean',
        'metadata' => 'array',
        'refund_data' => 'array',
    ];

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the buyer
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'refunded' => 'gray',
            'partially_refunded' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'card' => 'Credit/Debit Card',
            'au_becs_debit' => 'Bank Transfer',
            'afterpay_clearpay' => 'Afterpay',
            'credit_terms' => 'Credit Terms',
            'cash_on_delivery' => 'Cash on Delivery',
            default => ucwords(str_replace('_', ' ', $this->payment_method)),
        };
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'AUD ' . number_format($this->amount, 2);
    }

    /**
     * Get formatted refunded amount
     */
    public function getFormattedRefundedAmountAttribute(): string
    {
        return 'AUD ' . number_format($this->refunded_amount ?? 0, 2);
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->amount - ($this->refunded_amount ?? 0);
        return 'AUD ' . number_format($balance, 2);
    }

    /**
     * Get formatted total due (including late fees)
     */
    public function getFormattedTotalDueAttribute(): string
    {
        $total = $this->total_due ?? $this->amount;
        return 'AUD ' . number_format($total, 2);
    }
}