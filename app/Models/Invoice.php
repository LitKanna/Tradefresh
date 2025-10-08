<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'buyer_id',
        'vendor_id',
        'order_id',
        'status',
        'type',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'currency',
        'invoice_date',
        'due_date',
        'paid_date',
        'sent_at',
        'viewed_at',
        'reminded_at',
        'payment_terms',
        'terms_days',
        'late_fee_amount',
        'late_fee_percentage',
        'bill_to_name',
        'bill_to_company',
        'bill_to_address',
        'bill_to_email',
        'bill_to_phone',
        'ship_to_name',
        'ship_to_company',
        'ship_to_address',
        'ship_to_phone',
        'notes',
        'internal_notes',
        'po_number',
        'reference_number',
        'metadata',
        'is_recurring',
        'recurring_frequency',
        'recurring_start_date',
        'recurring_end_date',
        'recurring_count',
        'parent_invoice_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'reminded_at' => 'datetime',
        'is_recurring' => 'boolean',
        'recurring_start_date' => 'date',
        'recurring_end_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the invoice
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the buyer that owns the invoice
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the vendor that owns the invoice
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the order associated with the invoice
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the parent invoice for recurring invoices
     */
    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    /**
     * Get child invoices for recurring invoices
     */
    public function childInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    /**
     * Get the invoice items
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get the payment transactions for this invoice
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the payment reminders for this invoice
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    /**
     * Get successful payment transactions
     */
    public function successfulTransactions(): HasMany
    {
        return $this->transactions()->where('status', 'completed');
    }

    /**
     * Check if invoice is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && 
               $this->balance_due > 0 && 
               $this->due_date && 
               now()->isAfter($this->due_date);
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Check if invoice is due soon (within 7 days)
     */
    public function getIsDueSoonAttribute(): bool
    {
        return $this->status !== 'paid' && 
               $this->balance_due > 0 && 
               $this->due_date && 
               now()->diffInDays($this->due_date) <= 7 && 
               now()->isBefore($this->due_date);
    }

    /**
     * Get the payment status
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }

        if ($this->paid_amount >= $this->total_amount) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * Get payment percentage
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    /**
     * Calculate late fee if applicable
     */
    public function calculateLateFee(): float
    {
        if (!$this->is_overdue || $this->balance_due <= 0) {
            return 0;
        }

        $lateFee = 0;

        if ($this->late_fee_amount) {
            $lateFee += $this->late_fee_amount;
        }

        if ($this->late_fee_percentage) {
            $lateFee += ($this->balance_due * $this->late_fee_percentage / 100);
        }

        return $lateFee;
    }

    /**
     * Get the total amount including late fees
     */
    public function getTotalWithLateFeesAttribute(): float
    {
        return $this->total_amount + $this->calculateLateFee();
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark invoice as viewed
     */
    public function markAsViewed(): bool
    {
        return $this->update([
            'viewed_at' => now(),
        ]);
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, ?PaymentTransaction $transaction = null): bool
    {
        $newPaidAmount = $this->paid_amount + $amount;
        $newBalance = $this->total_amount - $newPaidAmount;

        $updateData = [
            'paid_amount' => $newPaidAmount,
            'balance_due' => max(0, $newBalance),
        ];

        // Update status based on payment
        if ($newBalance <= 0) {
            $updateData['status'] = 'paid';
            $updateData['paid_date'] = now();
        } elseif ($this->paid_amount <= 0 && $amount > 0) {
            $updateData['status'] = 'partial';
        }

        return $this->update($updateData);
    }

    /**
     * Generate next recurring invoice
     */
    public function generateRecurringInvoice(): ?Invoice
    {
        if (!$this->is_recurring || !$this->recurring_frequency) {
            return null;
        }

        $nextInvoiceDate = $this->getNextRecurringDate();
        if (!$nextInvoiceDate) {
            return null;
        }

        $newInvoice = $this->replicate();
        $newInvoice->invoice_number = $this->generateInvoiceNumber();
        $newInvoice->parent_invoice_id = $this->id;
        $newInvoice->invoice_date = $nextInvoiceDate;
        $newInvoice->due_date = $nextInvoiceDate->addDays($this->terms_days ?? 30);
        $newInvoice->status = 'draft';
        $newInvoice->paid_amount = 0;
        $newInvoice->balance_due = $newInvoice->total_amount;
        $newInvoice->paid_date = null;
        $newInvoice->sent_at = null;
        $newInvoice->viewed_at = null;
        $newInvoice->reminded_at = null;
        $newInvoice->save();

        // Copy invoice items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return $newInvoice;
    }

    /**
     * Get next recurring date
     */
    protected function getNextRecurringDate(): ?\Carbon\Carbon
    {
        $lastInvoiceDate = $this->childInvoices()
            ->orderBy('invoice_date', 'desc')
            ->first()?->invoice_date ?? $this->invoice_date;

        return match ($this->recurring_frequency) {
            'monthly' => $lastInvoiceDate->addMonth(),
            'quarterly' => $lastInvoiceDate->addQuarter(),
            'yearly' => $lastInvoiceDate->addYear(),
            default => null,
        };
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Y');
        $lastNumber = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first()?->invoice_number;

        if ($lastNumber) {
            $number = (int) substr($lastNumber, -6) + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('balance_due', '>', 0)
            ->where('due_date', '<', now());
    }

    /**
     * Scope for due soon invoices
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', '!=', 'paid')
            ->where('balance_due', '>', 0)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days));
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}