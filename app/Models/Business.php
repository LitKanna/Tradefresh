<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Business extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'abn',
        'entity_name',
        'entity_type_code',
        'entity_type_text',
        'trading_names',
        'business_type',
        'abn_status',
        'abn_status_from_date',
        'gst_registered',
        'gst_registered_from',
        'gst_registered_to',
        'address_state_code',
        'address_postcode',
        'address_full',
        'main_business_activity_code',
        'main_business_activity_description',
        'acn',
        'entity_status',
        'last_verified_at',
        'cached_until',
        'verification_failed',
        'verification_error',
        'raw_abr_response',
        'data_source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trading_names' => 'array',
        'gst_registered' => 'boolean',
        'verification_failed' => 'boolean',
        'raw_abr_response' => 'array',
        'last_verified_at' => 'datetime',
        'cached_until' => 'datetime',
        'abn_status_from_date' => 'date',
        'gst_registered_from' => 'date',
        'gst_registered_to' => 'date',
    ];

    /**
     * Get formatted ABN attribute
     *
     * @return Attribute
     */
    protected function formattedAbn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatABN($this->abn)
        );
    }

    /**
     * Get formatted ACN attribute
     *
     * @return Attribute
     */
    protected function formattedAcn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->acn ? $this->formatACN($this->acn) : null
        );
    }

    /**
     * Format ABN for display
     *
     * @param string $abn
     * @return string
     */
    protected function formatABN(string $abn): string
    {
        if (strlen($abn) !== 11) {
            return $abn;
        }

        return substr($abn, 0, 2) . ' ' . 
               substr($abn, 2, 3) . ' ' . 
               substr($abn, 5, 3) . ' ' . 
               substr($abn, 8, 3);
    }

    /**
     * Format ACN for display
     *
     * @param string $acn
     * @return string
     */
    protected function formatACN(string $acn): string
    {
        if (strlen($acn) !== 9) {
            return $acn;
        }

        return substr($acn, 0, 3) . ' ' . 
               substr($acn, 3, 3) . ' ' . 
               substr($acn, 6, 3);
    }

    /**
     * Check if the business is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->abn_status === 'active';
    }

    /**
     * Check if the business is GST registered
     *
     * @return bool
     */
    public function isGstRegistered(): bool
    {
        return $this->gst_registered && 
               ($this->gst_registered_to === null || 
                Carbon::parse($this->gst_registered_to)->isFuture());
    }

    /**
     * Check if the cached data is still valid
     *
     * @return bool
     */
    public function isCacheValid(): bool
    {
        return $this->cached_until && 
               $this->cached_until->isFuture() && 
               !$this->verification_failed;
    }

    /**
     * Check if verification is needed
     *
     * @return bool
     */
    public function needsVerification(): bool
    {
        if (!$this->cached_until) {
            return true;
        }

        if ($this->verification_failed) {
            $retryAfter = config('abn.fallback.retry_after_hours', 24);
            return $this->updated_at->addHours($retryAfter)->isPast();
        }

        return $this->cached_until->isPast();
    }

    /**
     * Get the display name for the business
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if (!empty($this->trading_names) && is_array($this->trading_names)) {
            return $this->trading_names[0];
        }

        return $this->entity_name;
    }

    /**
     * Get all business names (entity name + trading names)
     *
     * @return array
     */
    public function getAllNames(): array
    {
        $names = [$this->entity_name];

        if (!empty($this->trading_names) && is_array($this->trading_names)) {
            $names = array_merge($names, $this->trading_names);
        }

        return array_unique(array_filter($names));
    }

    /**
     * Scope for active businesses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('abn_status', 'active');
    }

    /**
     * Scope for GST registered businesses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGstRegistered($query)
    {
        return $query->where('gst_registered', true)
                    ->where(function ($q) {
                        $q->whereNull('gst_registered_to')
                          ->orWhere('gst_registered_to', '>', now());
                    });
    }

    /**
     * Scope for businesses with valid cache
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValidCache($query)
    {
        return $query->where('cached_until', '>', now())
                    ->where('verification_failed', false);
    }

    /**
     * Scope for businesses needing verification
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedingVerification($query)
    {
        $retryAfter = config('abn.fallback.retry_after_hours', 24);

        return $query->where(function ($q) use ($retryAfter) {
            $q->whereNull('cached_until')
              ->orWhere('cached_until', '<', now())
              ->orWhere(function ($q2) use ($retryAfter) {
                  $q2->where('verification_failed', true)
                     ->where('updated_at', '<', now()->subHours($retryAfter));
              });
        });
    }

    /**
     * Scope for businesses by type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('business_type', $type);
    }

    /**
     * Scope for businesses in a state
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stateCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInState($query, string $stateCode)
    {
        return $query->where('address_state_code', strtoupper($stateCode));
    }

    /**
     * Mark business as verified
     *
     * @return void
     */
    public function markAsVerified(): void
    {
        $this->update([
            'last_verified_at' => now(),
            'cached_until' => now()->addSeconds(config('abn.cache.ttl', 2592000)),
            'verification_failed' => false,
            'verification_error' => null,
        ]);
    }

    /**
     * Mark business as failed verification
     *
     * @param string $error
     * @return void
     */
    public function markAsFailedVerification(string $error): void
    {
        $this->update([
            'verification_failed' => true,
            'verification_error' => $error,
            'cached_until' => now()->addHours(config('abn.fallback.retry_after_hours', 24)),
        ]);
    }

    /**
     * Get business summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'abn' => $this->formatted_abn,
            'acn' => $this->formatted_acn,
            'name' => $this->getDisplayName(),
            'type' => $this->business_type,
            'status' => $this->abn_status,
            'gst_registered' => $this->isGstRegistered(),
            'state' => $this->address_state_code,
            'postcode' => $this->address_postcode,
        ];
    }

    /**
     * Relationships
     */

    /**
     * Get the buyers (users) associated with this business
     */
    public function buyers()
    {
        return $this->hasMany(Buyer::class);
    }

    /**
     * Get the primary buyer for this business
     */
    public function primaryBuyer()
    {
        return $this->hasOne(Buyer::class)->where('is_primary_contact', true);
    }

    /**
     * Get the business users (many-to-many with roles)
     */
    public function businessUsers()
    {
        return $this->hasMany(BusinessUser::class);
    }

    /**
     * Get the active business users
     */
    public function activeUsers()
    {
        return $this->hasMany(BusinessUser::class)->where('status', 'active');
    }

    /**
     * Get the contact methods for this business
     */
    public function contacts()
    {
        return $this->hasMany(BusinessContact::class);
    }

    /**
     * Get the primary contact method
     */
    public function primaryContact()
    {
        return $this->hasOne(BusinessContact::class)->where('is_primary', true);
    }

    /**
     * Get the pickup details for this business
     */
    public function pickupDetails()
    {
        return $this->hasMany(PickupDetail::class);
    }

    /**
     * Get the primary vehicle for this business
     */
    public function primaryVehicle()
    {
        return $this->hasOne(PickupDetail::class)->where('is_primary_vehicle', true);
    }

    /**
     * Get the pickup bookings for this business
     */
    public function pickupBookings()
    {
        return $this->hasMany(PickupBooking::class);
    }

    /**
     * Get upcoming pickup bookings
     */
    public function upcomingPickups()
    {
        return $this->hasMany(PickupBooking::class)
            ->where('pickup_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('pickup_date')
            ->orderBy('scheduled_time');
    }

    /**
     * Get the orders for this business
     */
    public function orders()
    {
        return $this->hasManyThrough(Order::class, Buyer::class);
    }

    /**
     * Additional Scopes
     */

    /**
     * Scope for verified businesses
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('last_verified_at')
            ->where('verification_failed', false);
    }

    /**
     * Scope for businesses with active users
     */
    public function scopeWithActiveUsers($query)
    {
        return $query->whereHas('businessUsers', function ($q) {
            $q->where('status', 'active');
        });
    }

    /**
     * Scope for businesses with vehicles
     */
    public function scopeWithVehicles($query)
    {
        return $query->whereHas('pickupDetails', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Helper Methods
     */

    /**
     * Check if business has any active users
     */
    public function hasActiveUsers(): bool
    {
        return $this->activeUsers()->exists();
    }

    /**
     * Get total number of vehicles
     */
    public function getVehicleCount(): int
    {
        return $this->pickupDetails()->where('is_active', true)->count();
    }

    /**
     * Get the business's preferred pickup method
     */
    public function getPreferredPickupMethod(): ?string
    {
        $primaryVehicle = $this->primaryVehicle;
        return $primaryVehicle ? $primaryVehicle->pickup_method : null;
    }
}