<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'nickname',
        'is_default',
        'is_active',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'card_holder_name',
        'card_token',
        'bank_name',
        'account_type',
        'account_last_four',
        'routing_number_last_four',
        'ach_token',
        'paypal_email',
        'paypal_token',
        'terms_days',
        'credit_limit',
        'available_credit',
        'terms_approved_date',
        'terms_approved_by',
        'billing_name',
        'billing_address',
        'billing_address2',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',
        'billing_phone',
        'is_verified',
        'verified_at',
        'verification_status',
        'verification_notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'terms_approved_date' => 'date',
        'credit_limit' => 'decimal:2',
        'available_credit' => 'decimal:2',
    ];

    protected $hidden = [
        'card_token',
        'ach_token',
        'paypal_token',
    ];

    /**
     * Get the user that owns the payment method
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this payment method
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get all scheduled payments for this payment method
     */
    public function scheduledPayments(): HasMany
    {
        return $this->hasMany(ScheduledPayment::class);
    }

    /**
     * Get the decrypted card token
     */
    public function getDecryptedCardTokenAttribute(): ?string
    {
        return $this->card_token ? Crypt::decryptString($this->card_token) : null;
    }

    /**
     * Set the encrypted card token
     */
    public function setCardTokenAttribute(?string $value): void
    {
        $this->attributes['card_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted ACH token
     */
    public function getDecryptedAchTokenAttribute(): ?string
    {
        return $this->ach_token ? Crypt::decryptString($this->ach_token) : null;
    }

    /**
     * Set the encrypted ACH token
     */
    public function setAchTokenAttribute(?string $value): void
    {
        $this->attributes['ach_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted PayPal token
     */
    public function getDecryptedPaypalTokenAttribute(): ?string
    {
        return $this->paypal_token ? Crypt::decryptString($this->paypal_token) : null;
    }

    /**
     * Set the encrypted PayPal token
     */
    public function setPaypalTokenAttribute(?string $value): void
    {
        $this->attributes['paypal_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get display name for the payment method
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        return match ($this->type) {
            'credit_card', 'debit_card' => ($this->card_brand ? ucfirst($this->card_brand) : 'Card') . ' ending in ' . $this->card_last_four,
            'ach' => ($this->bank_name ?? 'Bank') . ' account ending in ' . $this->account_last_four,
            'paypal' => 'PayPal - ' . $this->paypal_email,
            'terms' => 'Net ' . $this->terms_days . ' Terms',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Check if payment method is expired (for cards)
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!in_array($this->type, ['credit_card', 'debit_card'])) {
            return false;
        }

        if (!$this->card_exp_month || !$this->card_exp_year) {
            return false;
        }

        $expiry = now()->createFromFormat('Y-m', $this->card_exp_year . '-' . $this->card_exp_month)->endOfMonth();
        return now()->isAfter($expiry);
    }

    /**
     * Check if payment method is expiring soon (within 30 days)
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!in_array($this->type, ['credit_card', 'debit_card'])) {
            return false;
        }

        if (!$this->card_exp_month || !$this->card_exp_year) {
            return false;
        }

        $expiry = now()->createFromFormat('Y-m', $this->card_exp_year . '-' . $this->card_exp_month)->endOfMonth();
        return now()->diffInDays($expiry) <= 30 && now()->isBefore($expiry);
    }

    /**
     * Get available credit amount for terms payment methods
     */
    public function getAvailableCreditAmountAttribute(): ?float
    {
        if ($this->type !== 'terms' || !$this->credit_limit) {
            return null;
        }

        return $this->available_credit ?? $this->credit_limit;
    }

    /**
     * Check if payment method can process a payment
     */
    public function canProcessPayment(float $amount = 0): bool
    {
        if (!$this->is_active || $this->is_expired) {
            return false;
        }

        // Check credit limit for terms payments
        if ($this->type === 'terms' && $amount > 0) {
            return $this->getAvailableCreditAmountAttribute() >= $amount;
        }

        return true;
    }

    /**
     * Scope to get only active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only verified payment methods
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get default payment method for user
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}