<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'supplier_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'unit',
        'unit_type',
        'min_order_quantity',
        'max_order_quantity',
        'price',
        'compare_price',
        'cost',
        'image',
        'images',
        'specifications',
        'certifications',
        'certification',
        'quality_grade',
        'origin',
        'brand',
        'weight',
        'dimensions',
        'shelf_life',
        'storage_requirements',
        'is_active',
        'is_featured',
        'featured',
        'in_stock',
        'stock_quantity',
        'low_stock_threshold',
        'tags',
        'metadata',
        'views_count',
        'meta_keywords',
        'meta_description'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'min_order_quantity' => 'integer',
        'max_order_quantity' => 'integer',
        'stock_quantity' => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'featured' => 'boolean',
        'in_stock' => 'boolean',
        'images' => 'array',
        'specifications' => 'array',
        'certifications' => 'array',
        'dimensions' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'views_count' => 'integer'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function rfqItems(): HasMany
    {
        return $this->hasMany(RFQItem::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(Buyer::class, 'buyer_favorite_products')
            ->withTimestamps();
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    // public function productImages(): HasMany
    // {
    //     return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    // }

    // public function primaryImage()
    // {
    //     return $this->hasOne(ProductImage::class)->where('is_primary', true);
    // }

    // public function variations(): HasMany
    // {
    //     return $this->hasMany(ProductVariation::class);
    // }

    // public function activeVariations(): HasMany
    // {
    //     return $this->hasMany(ProductVariation::class)->where('is_active', true);
    // }

    // public function priceTiers(): HasMany
    // {
    //     return $this->hasMany(ProductPriceTier::class)->orderBy('min_quantity');
    // }

    // public function attributes(): HasMany
    // {
    //     return $this->hasMany(ProductAttribute::class);
    // }

    // public function inventory(): HasMany
    // {
    //     return $this->hasMany(ProductInventory::class);
    // }

    // public function reviews(): HasMany
    // {
    //     return $this->hasMany(ProductReview::class);
    // }

    // public function approvedReviews(): HasMany
    // {
    //     return $this->hasMany(ProductReview::class)->where('is_approved', true);
    // }

    // public function recentlyViewed(): HasMany
    // {
    //     return $this->hasMany(RecentlyViewedProduct::class);
    // }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('sku', 'LIKE', "%{$search}%")
              ->orWhere('brand', 'LIKE', "%{$search}%")
              ->orWhere('meta_keywords', 'LIKE', "%{$search}%");
        });
    }

    public function scopeWithFilters($query, array $filters)
    {
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        
        if (!empty($filters['vendor'])) {
            $query->where('vendor_id', $filters['vendor']);
        }
        
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        
        if (!empty($filters['brand'])) {
            $query->whereIn('brand', (array)$filters['brand']);
        }
        
        if (!empty($filters['origin'])) {
            $query->whereIn('origin', (array)$filters['origin']);
        }
        
        if (!empty($filters['in_stock'])) {
            $query->where('in_stock', true);
        }
        
        if (!empty($filters['featured'])) {
            $query->where('featured', true);
        }
        
        return $query;
    }

    // Attributes
    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }
        
        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 1);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getDefaultUnitAttribute(): string
    {
        return $this->unit ?? 'kg';
    }

    // Methods
    public function updateStock($quantity, $operation = 'subtract'): void
    {
        if ($operation === 'subtract') {
            $this->stock_quantity = max(0, $this->stock_quantity - $quantity);
        } else {
            $this->stock_quantity += $quantity;
        }
        
        $this->in_stock = $this->stock_quantity > 0;
        $this->save();
    }

    public function recordPriceChange($newPrice, $reason = null): void
    {
        $this->priceHistory()->create([
            'old_price' => $this->price,
            'new_price' => $newPrice,
            'change_reason' => $reason,
            'changed_by' => auth()->id()
        ]);
        
        $this->price = $newPrice;
        $this->save();
    }

    public function canBeOrderedBy(Buyer $buyer, $quantity): bool
    {
        if (!$this->is_active || !$this->in_stock) {
            return false;
        }
        
        if ($quantity < $this->min_order_quantity || ($this->max_order_quantity && $quantity > $this->max_order_quantity)) {
            return false;
        }
        
        if ($this->stock_quantity < $quantity) {
            return false;
        }
        
        return true;
    }

    public function getPriceForQuantity($quantity)
    {
        $tier = $this->priceTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->whereNull('variation_id')
            ->orderBy('min_quantity', 'desc')
            ->first();

        return $tier ? $tier->price : $this->price;
    }

    public function incrementViewCount()
    {
        $this->increment('views_count');
    }

    public function getAverageRatingAttribute()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->approvedReviews()->count();
    }

    public function getPrimaryImageUrlAttribute()
    {
        if ($this->primaryImage) {
            return $this->primaryImage->image_url;
        }
        
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        return asset('images/product-placeholder.png');
    }

    public function getGalleryImagesAttribute()
    {
        return $this->productImages;
    }

    // public function hasVariations()
    // {
    //     return $this->variations()->exists();
    // }

    // public function getAvailableStock($variationId = null)
    // {
    //     $inventory = $this->inventory()
    //         ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
    //         ->first();
    //
    //     return $inventory ? $inventory->available_quantity : $this->stock_quantity;
    // }

    // public function getAttribute($name, $default = null)
    // {
    //     $attribute = $this->attributes()->where('attribute_name', $name)->first();
    //     return $attribute ? $attribute->attribute_value : $default;
    // }
}