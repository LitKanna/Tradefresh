<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'buyer_id',
        'session_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'coupon_code',
        'discount_details',
        'items_count',
        'total_weight',
        'last_activity_at',
        'abandoned_at',
        'recovery_email_sent_at',
        'checked_out_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'recovery_email_sent_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'metadata' => 'array',
        'discount_details' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_weight' => 'decimal:2',
    ];

    protected $appends = ['vendor_groups', 'is_shareable', 'has_bulk_discounts'];

    /**
     * Get the buyer that owns the cart
     */
    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the cart items
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get saved cart information
     */
    public function savedCart()
    {
        return $this->hasOne(SavedCart::class);
    }

    /**
     * Get cart shares
     */
    public function shares()
    {
        return $this->hasMany(CartShare::class);
    }

    /**
     * Get applied coupons
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'cart_coupons')
                    ->withPivot('discount_amount', 'applied_to_items')
                    ->withTimestamps();
    }

    /**
     * Get cart abandonment recovery
     */
    public function abandonmentRecovery()
    {
        return $this->hasOne(CartAbandonmentRecovery::class);
    }

    /**
     * Get the total number of items in cart
     */
    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get the total value of the cart
     */
    public function getTotalValueAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Check if cart is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope for active carts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for buyer's cart
     */
    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope for session cart
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for abandoned carts
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned')
                    ->whereNotNull('abandoned_at');
    }

    /**
     * Group items by vendor
     */
    public function getVendorGroupsAttribute()
    {
        return $this->items->load('product.vendor')->groupBy('product.vendor_id')->map(function ($items, $vendorId) {
            $vendor = $items->first()->product->vendor;
            return [
                'vendor' => $vendor,
                'items' => $items,
                'subtotal' => $items->sum('total_price'),
                'item_count' => $items->sum('quantity'),
            ];
        });
    }

    /**
     * Check if cart can be shared
     */
    public function getIsShareableAttribute()
    {
        return $this->buyer_id && $this->items_count > 0;
    }

    /**
     * Check if cart has bulk discounts applied
     */
    public function getHasBulkDiscountsAttribute()
    {
        return $this->items->contains(function ($item) {
            return $item->bulk_discount_percentage > 0;
        });
    }

    /**
     * Calculate and update cart totals
     */
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->items_count = $this->items->sum('quantity');
        $this->total_weight = $this->items->sum(function ($item) {
            return $item->quantity * ($item->product->weight ?? 0);
        });
        
        // Calculate shipping (can be enhanced with shipping service)
        $this->calculateShipping();
        
        // Apply coupon discounts
        $this->calculateCouponDiscounts();
        
        // Final total
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
        
        $this->save();
        
        return $this;
    }

    /**
     * Calculate shipping costs
     */
    protected function calculateShipping()
    {
        // Basic shipping calculation - can be enhanced
        if ($this->subtotal >= 500) {
            $this->shipping_amount = 0; // Free shipping over $500
        } else {
            $this->shipping_amount = min($this->total_weight * 2, 50); // $2 per kg, max $50
        }
    }

    /**
     * Calculate coupon discounts
     */
    protected function calculateCouponDiscounts()
    {
        $totalDiscount = 0;
        
        foreach ($this->coupons as $coupon) {
            if ($coupon->isValidFor($this)) {
                $discount = $coupon->calculateDiscount($this);
                $totalDiscount += $discount;
                
                // Update pivot table
                $this->coupons()->updateExistingPivot($coupon->id, [
                    'discount_amount' => $discount
                ]);
            }
        }
        
        $this->discount_amount = $totalDiscount;
    }

    /**
     * Mark cart as abandoned
     */
    public function markAsAbandoned()
    {
        $this->update([
            'status' => 'abandoned',
            'abandoned_at' => now(),
        ]);
    }

    /**
     * Share cart with another buyer
     */
    public function shareWith($email, $permission = 'view', $message = null)
    {
        return CartShare::create([
            'cart_id' => $this->id,
            'shared_by' => $this->buyer_id,
            'share_email' => $email,
            'share_token' => Str::random(32),
            'permission' => $permission,
            'message' => $message,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Save cart for later
     */
    public function saveForLater($name, $type = 'saved')
    {
        return SavedCart::create([
            'buyer_id' => $this->buyer_id,
            'cart_id' => $this->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    /**
     * Clone cart for reordering
     */
    public function duplicate()
    {
        $newCart = $this->replicate(['checked_out_at', 'abandoned_at']);
        $newCart->status = 'active';
        $newCart->session_id = session()->getId();
        $newCart->save();
        
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->cart_id = $newCart->id;
            $newItem->save();
        }
        
        return $newCart;
    }
}