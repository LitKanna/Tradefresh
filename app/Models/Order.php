<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PROCESSING = 'processing';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_PARTIALLY_PAID = 'partially_paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    const FULFILLMENT_TYPE_PICKUP = 'pickup';
    const FULFILLMENT_TYPE_DELIVERY = 'delivery';

    protected $fillable = [
        'order_number',
        'user_id',
        'buyer_id',
        'buyer_business_id',
        'vendor_id',
        'warehouse_id',
        'supplier_id',
        'status',
        'payment_status',
        'payment_method',
        'fulfillment_type',
        'subtotal',
        'tax_amount',
        'delivery_fee',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'notes',
        'buyer_notes',
        'vendor_notes',
        'internal_notes',
        'pickup_booking_id',
        'delivery_route_id',
        'delivery_address_id',
        'expected_delivery_date',
        'actual_delivery_date',
        'invoice_number',
        'invoice_date',
        'payment_terms_days',
        'payment_due_date',
        'is_urgent',
        'is_recurring',
        'parent_order_id',
        'metadata',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'picked_up_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'invoice_date' => 'date',
        'payment_due_date' => 'date',
        'is_urgent' => 'boolean',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $vendorCode = str_pad($this->vendor_id, 4, '0', STR_PAD_LEFT);
        $sequence = str_pad(
            self::where('vendor_id', $this->vendor_id)
                ->whereNotNull('invoice_number')
                ->count() + 1,
            6,
            '0',
            STR_PAD_LEFT
        );
        
        return "{$prefix}-{$vendorCode}-{$sequence}";
    }

    // Relationships
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function buyerBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'buyer_business_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatus::class)->orderBy('created_at', 'desc');
    }

    public function currentStatus(): HasOne
    {
        return $this->hasOne(OrderStatus::class)->latestOfMany();
    }

    public function pickupBooking(): BelongsTo
    {
        return $this->belongsTo(PickupBooking::class);
    }

    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function childOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED
        ]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_CONFIRMED
        ]);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PREPARING,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_IN_TRANSIT
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED
        ]);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PARTIALLY_PAID
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_due_date', '<', now())
            ->whereIn('payment_status', [
                self::PAYMENT_STATUS_PENDING,
                self::PAYMENT_STATUS_PARTIALLY_PAID
            ]);
    }

    // Helper Methods
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_CONFIRMED
        ]);
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED
        ]);
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function getOutstandingAmount(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function updateStatus(string $status, ?string $notes = null, ?int $userId = null): OrderStatus
    {
        $this->status = $status;
        
        // Update timestamp fields based on status
        switch ($status) {
            case self::STATUS_CONFIRMED:
                $this->confirmed_at = now();
                break;
            case self::STATUS_PREPARING:
                $this->preparing_at = now();
                break;
            case self::STATUS_READY_FOR_PICKUP:
                $this->ready_at = now();
                break;
            case self::STATUS_IN_TRANSIT:
                $this->picked_up_at = now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
            case self::STATUS_COMPLETED:
                $this->completed_at = now();
                break;
            case self::STATUS_CANCELLED:
                $this->cancelled_at = now();
                $this->cancelled_by = $userId;
                $this->cancellation_reason = $notes;
                break;
        }
        
        $this->save();

        return $this->statusHistory()->create([
            'status' => $status,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id(),
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->subtotal * 0.10; // 10% GST
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->delivery_fee - $this->discount_amount;
        $this->save();
    }

    public function reserveStock(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->product->reserveStock($item->quantity, $this->id)) {
                // Rollback any previous reservations
                $this->releaseStock();
                return false;
            }
        }
        return true;
    }

    public function releaseStock(): void
    {
        foreach ($this->items as $item) {
            $item->product->releaseStock($item->quantity, $this->id);
        }
    }

    public function getTimelineAttribute(): array
    {
        return [
            'created' => $this->created_at,
            'confirmed' => $this->confirmed_at,
            'preparing' => $this->preparing_at,
            'ready' => $this->ready_at,
            'picked_up' => $this->picked_up_at,
            'delivered' => $this->delivered_at,
            'completed' => $this->completed_at,
            'cancelled' => $this->cancelled_at
        ];
    }

    public function getProgressPercentageAttribute(): int
    {
        $statuses = [
            self::STATUS_DRAFT => 10,
            self::STATUS_SUBMITTED => 20,
            self::STATUS_CONFIRMED => 30,
            self::STATUS_PREPARING => 50,
            self::STATUS_READY_FOR_PICKUP => 70,
            self::STATUS_IN_TRANSIT => 85,
            self::STATUS_DELIVERED => 95,
            self::STATUS_COMPLETED => 100
        ];

        return $statuses[$this->status] ?? 0;
    }
}