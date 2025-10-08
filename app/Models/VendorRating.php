<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorRating extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vendor_id',
        'buyer_id',
        'order_id',
        'product_id',
        'rating_type',
        'overall_rating',
        'quality_rating',
        'price_rating',
        'service_rating',
        'delivery_rating',
        'communication_rating',
        'review_title',
        'review_text',
        'pros',
        'cons',
        'images',
        'is_verified_purchase',
        'is_anonymous',
        'is_featured',
        'is_published',
        'helpful_count',
        'unhelpful_count',
        'helpful_voters',
        'vendor_response',
        'vendor_responded_at',
        'admin_notes',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'overall_rating' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'price_rating' => 'decimal:2',
        'service_rating' => 'decimal:2',
        'delivery_rating' => 'decimal:2',
        'communication_rating' => 'decimal:2',
        'pros' => 'array',
        'cons' => 'array',
        'images' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'helpful_voters' => 'array',
        'vendor_responded_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the vendor being rated.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the buyer who made the rating.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the order associated with this rating.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product being rated.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin who approved the rating.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Scope to filter published ratings.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('status', 'approved');
    }

    /**
     * Scope to filter featured ratings.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by rating type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('rating_type', $type);
    }

    /**
     * Scope to filter by minimum rating.
     */
    public function scopeMinRating($query, float $rating)
    {
        return $query->where('overall_rating', '>=', $rating);
    }

    /**
     * Scope to filter verified purchases.
     */
    public function scopeVerifiedPurchases($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mark the rating as helpful for a user.
     */
    public function markAsHelpful(int $userId): void
    {
        $voters = $this->helpful_voters ?? [];
        
        if (!in_array($userId, $voters)) {
            $voters[] = $userId;
            $this->update([
                'helpful_voters' => $voters,
                'helpful_count' => $this->helpful_count + 1,
            ]);
        }
    }

    /**
     * Calculate the average rating across all criteria.
     */
    public function calculateAverageRating(): float
    {
        $ratings = array_filter([
            $this->quality_rating,
            $this->price_rating,
            $this->service_rating,
            $this->delivery_rating,
            $this->communication_rating,
        ]);

        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : $this->overall_rating;
    }

    /**
     * Check if the rating can be edited by the buyer.
     */
    public function canBeEdited(): bool
    {
        return $this->created_at->diffInDays(now()) <= 30 && 
               $this->status !== 'rejected';
    }

    /**
     * Get display name for the reviewer.
     */
    public function getReviewerName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous Buyer';
        }

        return $this->buyer->name ?? 'Buyer';
    }
}