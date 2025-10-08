<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'vendor_id',
        'quantity',
        'unit',
        'unit_price',
        'original_price',
        'discount_percentage',
        'discount_amount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_price',
        'meets_min_quantity',
        'bulk_discount_percentage',
        'bulk_discount_tier',
        'price_tiers',
        'special_instructions',
        'requested_delivery_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'bulk_discount_percentage' => 'decimal:2',
        'meets_min_quantity' => 'boolean',
        'price_tiers' => 'array',
        'metadata' => 'array',
        'requested_delivery_date' => 'date',
    ];

    protected $appends = ['savings_amount', 'is_discounted'];

    /**
     * Get the cart that owns the item
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the total value of this cart item
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Check if the item is available (product is in stock)
     */
    public function getIsAvailableAttribute()
    {
        return $this->product && 
               $this->product->is_active && 
               $this->product->stock_quantity >= $this->quantity;
    }

    /**
     * Get savings amount
     */
    public function getSavingsAmountAttribute()
    {
        return ($this->original_price - $this->unit_price) * $this->quantity;
    }

    /**
     * Check if item is discounted
     */
    public function getIsDiscountedAttribute()
    {
        return $this->discount_percentage > 0 || $this->bulk_discount_percentage > 0;
    }

    /**
     * Update quantity and recalculate prices
     */
    public function updateQuantity($quantity)
    {
        $this->quantity = $quantity;
        $this->calculatePrices();
        $this->save();
        
        // Recalculate cart totals
        $this->cart->calculateTotals();
        
        return $this;
    }

    /**
     * Calculate item prices including bulk discounts
     */
    public function calculatePrices()
    {
        // Get current product price
        $this->original_price = $this->product->price;
        $this->unit_price = $this->original_price;
        
        // Apply bulk discount if applicable
        $bulkDiscount = $this->calculateBulkDiscount();
        if ($bulkDiscount) {
            $this->bulk_discount_percentage = $bulkDiscount['percentage'];
            $this->bulk_discount_tier = $bulkDiscount['tier'];
            $this->unit_price = $this->original_price * (1 - $bulkDiscount['percentage'] / 100);
        }
        
        // Calculate subtotal
        $this->subtotal = $this->unit_price * $this->quantity;
        
        // Calculate tax
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        
        // Calculate total
        $this->total_price = $this->subtotal + $this->tax_amount;
        
        return $this;
    }

    /**
     * Calculate bulk discount based on quantity
     */
    protected function calculateBulkDiscount()
    {
        if (!$this->product->bulk_discount_tiers) {
            return null;
        }
        
        $tiers = $this->product->bulk_discount_tiers->sortByDesc('min_quantity');
        
        foreach ($tiers as $tier) {
            if ($this->quantity >= $tier->min_quantity) {
                return [
                    'percentage' => $tier->discount_percentage,
                    'tier' => $tier->name,
                ];
            }
        }
        
        return null;
    }

    /**
     * Check if item meets minimum quantity requirements
     */
    public function checkMinimumQuantity()
    {
        $minQuantity = $this->product->min_order_quantity ?? 1;
        $this->meets_min_quantity = $this->quantity >= $minQuantity;
        return $this->meets_min_quantity;
    }
}