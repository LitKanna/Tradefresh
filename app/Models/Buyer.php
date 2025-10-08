<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Buyer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'buyer';

    protected $fillable = [
        // Existing columns based on migrations
        'business_id',
        'abn',
        'business_name',
        'buyer_type',
        'business_type',
        'purchase_category',
        'contact_name',
        'first_name',
        'last_name',
        'phone',
        'mobile',
        'email',
        'email_verified_at',
        'password',
        'address',
        'suburb',
        'state',
        'postcode',
        'status',
        'last_login_at',
        'last_login_ip',
        'login_count',
        'last_activity_at',
        'remember_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(RFQ::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BuyerActivity::class);
    }

    public function dashboardPreferences()
    {
        return $this->hasOne(DashboardPreference::class);
    }

    public function dashboardMetrics(): HasMany
    {
        return $this->hasMany(DashboardMetric::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'buyer_favorite_products')
            ->withTimestamps();
    }

    public function favoriteVendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'buyer_favorite_vendors')
            ->withTimestamps();
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'buyer_supplier')
            ->withPivot('total_spent', 'total_orders', 'average_rating', 'is_preferred', 'first_order_at', 'last_order_at')
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'buyer_product')
            ->withPivot('quantity_ordered', 'last_ordered_at')
            ->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id')
            ->where('sender_type', self::class);
    }

    /**
     * Get the notifications for the buyer.
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function canPlaceOrder(): bool
    {
        return $this->isActive();
    }

    public function getDefaultShippingAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', 'shipping')
            ->where('is_default', true)
            ->first();
    }

    public function getDefaultBillingAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', 'billing')
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get approvals pending for this buyer
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }
    
    /**
     * Business-centric Relationships
     */

    /**
     * Get full address as a formatted string
     */
    public function getFullAddress(): string
    {
        return "{$this->address}, {$this->suburb}, {$this->state} {$this->postcode}";
    }

    /**
     * Scope queries to active buyers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope queries to pending buyers
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope queries to suspended buyers
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Get pickup bookings created by this buyer
     */
    public function pickupBookings()
    {
        return $this->hasMany(PickupBooking::class);
    }

    /**
     * Get users invited by this buyer
     */
    public function invitedUsers()
    {
        return $this->hasMany(BusinessUser::class, 'invited_by');
    }

    /**
     * Get users approved by this buyer
     */
    public function approvedUsers()
    {
        return $this->hasMany(BusinessUser::class, 'approved_by');
    }

    /**
     * Scope by buyer type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('buyer_type', $type);
    }

    /**
     * Scope by business type
     */
    public function scopeByBusinessType($query, $type)
    {
        return $query->where('business_type', $type);
    }

    /**
     * Scope by purchase category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('purchase_category', $category);
    }

    /**
     * Helper Methods
     */

    /**
     * Get buyer type label
     */
    public function getBuyerTypeLabel(): string
    {
        $types = [
            'owner' => 'Owner',
            'co_owner' => 'Co-Owner',
            'manager' => 'Manager',
            'buyer' => 'Buyer',
            'salesman' => 'Salesman',
            'accounts_member' => 'Accounts Member',
            'authorized_rep' => 'Authorized Representative'
        ];

        return $types[$this->buyer_type] ?? $this->buyer_type;
    }

    /**
     * Get business type label
     */
    public function getBusinessTypeLabel(): string
    {
        $types = [
            'company' => 'Company',
            'partnership' => 'Partnership',
            'sole_trader' => 'Sole Trader',
            'trust' => 'Trust'
        ];

        return $types[$this->business_type] ?? $this->business_type;
    }

    /**
     * Get purchase category label
     */
    public function getPurchaseCategoryLabel(): string
    {
        $categories = [
            'fruits_vegetables' => 'Fruits & Vegetables',
            'dairy_eggs' => 'Dairy & Eggs',
            'meat_seafood' => 'Meat & Seafood',
            'bakery' => 'Bakery',
            'beverages' => 'Beverages',
            'dry_goods' => 'Dry Goods',
            'frozen_foods' => 'Frozen Foods',
            'other' => 'Other'
        ];

        return $categories[$this->purchase_category] ?? $this->purchase_category;
    }
}