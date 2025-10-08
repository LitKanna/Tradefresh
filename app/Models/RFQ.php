<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RFQ extends Model
{
    use HasFactory;

    protected $table = 'rfqs';

    protected $fillable = [
        'buyer_id',
        'title',
        'description',
        'rfq_number',
        'category_id',
        'items',  // Added items field
        'quantity',
        'unit',
        'budget_min',
        'budget_max',
        'delivery_date',
        'delivery_time',  // Added delivery_time
        'delivery_address',
        'delivery_instructions',  // Added delivery_instructions
        'delivery_suburb',
        'delivery_state',
        'delivery_postcode',
        'payment_terms',
        'status',
        'urgency',  // Added urgency
        'visibility',
        'is_public',  // Added is_public
        'selected_vendors',
        'auto_select_vendors',
        'min_rating_required',
        'certification_required',
        'sample_required',
        'specifications',
        'attachments',
        'closing_date',
        'closes_at',  // Added closes_at
        'published_at',  // Added published_at
        'extended_count',
        'last_extended_at',
        'auto_close',
        'auto_award',
        'award_criteria',
        'notes',
        'rejection_reason',
        'closed_at',
        'awarded_at',
        'metadata'
    ];

    protected $casts = [
        'items' => 'array',  // Cast items JSON to array
        'delivery_date' => 'date',
        'closing_date' => 'datetime',
        'closes_at' => 'datetime',
        'published_at' => 'datetime',
        'last_extended_at' => 'datetime',
        'closed_at' => 'datetime',
        'awarded_at' => 'datetime',
        'selected_vendors' => 'array',
        'specifications' => 'array',
        'attachments' => 'array',
        'award_criteria' => 'array',
        'metadata' => 'array',
        'auto_select_vendors' => 'boolean',
        'certification_required' => 'boolean',
        'sample_required' => 'boolean',
        'auto_close' => 'boolean',
        'auto_award' => 'boolean',
        'is_public' => 'boolean'
    ];

    /**
     * Get the buyer that owns the RFQ
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the category of the RFQ
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the quotes for the RFQ
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'rfq_id', 'id');
    }

    /**
     * Get accepted quotes
     */
    public function acceptedQuotes(): HasMany
    {
        return $this->quotes()->where('status', 'accepted');
    }

    /**
     * Check if RFQ is open for quotes
     */
    public function isOpen(): bool
    {
        return $this->status === 'open' && 
               $this->closing_date > now();
    }

    /**
     * Check if RFQ can be extended
     */
    public function canBeExtended(): bool
    {
        return $this->extended_count < 3 && 
               in_array($this->status, ['open', 'pending_review']);
    }

    /**
     * Get the winning quote
     */
    public function winningQuote()
    {
        return $this->quotes()->where('status', 'accepted')->first();
    }

    /**
     * Scope for active RFQs
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'pending_review']);
    }

    /**
     * Scope for buyer's RFQs
     */
    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Get reference_number attribute (alias for rfq_number)
     */
    public function getReferenceNumberAttribute()
    {
        return $this->rfq_number;
    }
}