<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'vendor';

    protected $fillable = [
        'abn',
        'business_name',
        'business_type',
        'vendor_category',
        'vendor_type',
        'contact_name',
        'phone',
        'email',
        'username',
        'email_verified_at',
        'password',
        'address',
        'suburb',
        'state',
        'postcode',
        'status',
        'verification_status',
        'is_online',
        'last_activity_at',
        'last_heartbeat_at',
        'current_session_id',
        'active_sessions_count',
        'remember_token'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'is_online' => 'boolean',
        'active_sessions_count' => 'integer',
        'password' => 'hashed'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
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

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isUnverified(): bool
    {
        return $this->verification_status === 'unverified';
    }

    public function canRespondToRFQ(): bool
    {
        return $this->isActive() && $this->isVerified();
    }

    /**
     * Get full address as a formatted string
     */
    public function getFullAddress(): string
    {
        return "{$this->address}, {$this->suburb}, {$this->state} {$this->postcode}";
    }

    /**
     * Scope queries to active vendors
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope queries to pending vendors
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope queries to suspended vendors
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope queries to verified vendors
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope by vendor category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('vendor_category', $category);
    }

    /**
     * Scope by business type
     */
    public function scopeByBusinessType($query, $type)
    {
        return $query->where('business_type', $type);
    }

    /**
     * Get vendor category label
     */
    public function getVendorCategoryLabel(): string
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

        return $categories[$this->vendor_category] ?? $this->vendor_category;
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
     * Mark vendor as online
     */
    public function markAsOnline(string $sessionId = null): void
    {
        $this->update([
            'is_online' => true,
            'last_activity_at' => now(),
            'last_heartbeat_at' => now(),
            'current_session_id' => $sessionId ?: session()->getId(),
            'active_sessions_count' => $this->active_sessions_count + 1
        ]);
    }

    /**
     * Mark vendor as offline
     */
    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_activity_at' => now(),
            'current_session_id' => null,
            'active_sessions_count' => 0
        ]);
    }

    /**
     * Update heartbeat timestamp
     */
    public function updateHeartbeat(): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'last_activity_at' => now()
        ]);
    }

    /**
     * Update activity timestamp
     */
    public function updateActivity(): void
    {
        if (!$this->is_online || $this->last_activity_at < now()->subMinutes(1)) {
            $this->update(['last_activity_at' => now()]);
        }
    }

    /**
     * Check if vendor should be marked offline (no heartbeat for 30 seconds)
     */
    public function shouldBeMarkedOffline(): bool
    {
        return $this->is_online && 
               $this->last_heartbeat_at && 
               $this->last_heartbeat_at->lt(now()->subSeconds(30));
    }

    /**
     * Scope for online vendors
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true)
                    ->where('last_heartbeat_at', '>=', now()->subSeconds(30));
    }

    /**
     * Scope for recently active vendors
     */
    public function scopeRecentlyActive($query, $minutes = 5)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Activity logs relationship
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(VendorActivityLog::class);
    }

    /**
     * Log vendor activity
     */
    public function logActivity(string $eventType, array $metadata = []): void
    {
        $this->activityLogs()->create([
            'event_type' => $eventType,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata
        ]);
    }
}