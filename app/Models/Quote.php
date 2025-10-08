<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_id',
        'buyer_id',
        'vendor_id',
        'quote_number',
        'status',
        'total_amount',
        'tax_amount',
        'delivery_charge',
        'discount_amount',
        'final_amount',
        'line_items',
        'terms_conditions',
        'notes',
        'vendor_message',
        'validity_date',
        'proposed_delivery_date',
        'proposed_delivery_time',
        'payment_terms_days',
        'payment_method',
        'attachments',
        'metadata',
        'is_negotiable',
        'revision_number',
        'parent_quote_id',
        'submitted_at',
        'reviewed_at',
        'rejection_reason'
    ];

    protected $casts = [
        'validity_date' => 'datetime',
        'proposed_delivery_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'line_items' => 'array',
        'terms_conditions' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
        'is_negotiable' => 'boolean',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'revision_number' => 'integer'
    ];

    /**
     * Get the RFQ that owns the quote
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class);
    }

    /**
     * Get the vendor that owns the quote
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the buyer that requested the quote
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the quote items
     */
    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the parent quote if this is a revision
     */
    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'parent_quote_id');
    }

    /**
     * Check if quote is still valid
     */
    public function isValid(): bool
    {
        return $this->validity_date >= now() &&
               in_array($this->status, ['submitted', 'under_review']);
    }

    /**
     * Check if quote can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->isValid() && 
               $this->rfq->status === 'open';
    }

    /**
     * Calculate total with tax
     */
    public function getTotalWithTaxAttribute(): float
    {
        return $this->total_amount + $this->tax_amount;
    }

    /**
     * Calculate total with delivery
     */
    public function getTotalWithDeliveryAttribute(): float
    {
        return $this->total_amount + $this->delivery_charge;
    }

    /**
     * Get final total (already calculated in database)
     */
    public function getFinalTotalAttribute(): float
    {
        return $this->final_amount ?? ($this->total_amount + $this->tax_amount + $this->delivery_charge - $this->discount_amount);
    }

    /**
     * Scope for pending quotes (submitted or under review)
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * Scope for accepted quotes
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for quotes by vendor
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}