<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'sku',
        'description',
        'price',
        'compare_price',
        'currency',
        'unit',
        'min_order_quantity',
        'stock_quantity',
        'track_inventory',
        'lead_time',
        'images',
        'specifications',
        'bulk_pricing',
        'category',
        'tags',
        'status',
        'rating',
        'review_count',
        'order_count'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'images' => 'array',
        'specifications' => 'array',
        'bulk_pricing' => 'array',
        'tags' => 'array',
        'track_inventory' => 'boolean',
        'rating' => 'decimal:2'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function getBulkPriceAttribute($quantity)
    {
        if (!$this->bulk_pricing || !is_array($this->bulk_pricing)) {
            return $this->price;
        }

        $applicablePrice = $this->price;
        
        foreach ($this->bulk_pricing as $tier) {
            if ($quantity >= ($tier['min_quantity'] ?? 0)) {
                if (isset($tier['price'])) {
                    $applicablePrice = $tier['price'];
                } elseif (isset($tier['discount_percentage'])) {
                    $applicablePrice = $this->price * (1 - $tier['discount_percentage'] / 100);
                }
            }
        }
        
        return $applicablePrice;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }
        
        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function isInStock()
    {
        return !$this->track_inventory || $this->stock_quantity > 0;
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->track_inventory) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function incrementStock($quantity = 1)
    {
        if ($this->track_inventory) {
            $this->increment('stock_quantity', $quantity);
        }
    }
}