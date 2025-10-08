<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_PICKED = 'picked';
    const STATUS_PACKED = 'packed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_BACKORDERED = 'backordered';
    const STATUS_SUBSTITUTED = 'substituted';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'vendor_product_id',
        'status',
        'sku',
        'name',
        'description',
        'quantity',
        'unit_price',
        'cost_price',
        'discount_amount',
        'tax_amount',
        'total',
        'unit_of_measure',
        'weight',
        'dimensions',
        'notes',
        'is_substitution',
        'original_item_id',
        'substitution_reason',
        'batch_number',
        'expiry_date',
        'location_code',
        'picked_quantity',
        'packed_quantity',
        'delivered_quantity',
        'returned_quantity',
        'damaged_quantity',
        'metadata'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'is_substitution' => 'boolean',
        'expiry_date' => 'date',
        'picked_quantity' => 'decimal:3',
        'packed_quantity' => 'decimal:3',
        'delivered_quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
        'damaged_quantity' => 'decimal:3',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->total)) {
                $item->calculateTotal();
            }
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'discount_amount', 'tax_amount'])) {
                $item->calculateTotal();
            }
        });
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class);
    }

    public function originalItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'original_item_id');
    }

    public function substitutedItem()
    {
        return $this->hasOne(OrderItem::class, 'original_item_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED
        ]);
    }

    public function scopeBackordered($query)
    {
        return $query->where('status', self::STATUS_BACKORDERED);
    }

    public function scopeSubstituted($query)
    {
        return $query->where('is_substitution', true);
    }

    public function scopeReadyForPicking($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING
        ]);
    }

    // Helper Methods
    public function calculateTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->tax_amount = $this->tax_amount ?: ($subtotal * 0.10); // 10% GST default
        $this->total = $subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function markAsPicked(float $quantity = null): void
    {
        $this->picked_quantity = $quantity ?? $this->quantity;
        $this->status = self::STATUS_PICKED;
        $this->save();
    }

    public function markAsPacked(float $quantity = null): void
    {
        $this->packed_quantity = $quantity ?? $this->picked_quantity ?? $this->quantity;
        $this->status = self::STATUS_PACKED;
        $this->save();
    }

    public function markAsDelivered(float $quantity = null): void
    {
        $this->delivered_quantity = $quantity ?? $this->packed_quantity ?? $this->quantity;
        $this->status = self::STATUS_DELIVERED;
        $this->save();
    }

    public function canBeSubstituted(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_BACKORDERED
        ]);
    }

    public function createSubstitution(int $productId, string $reason): OrderItem
    {
        $substituteProduct = Product::findOrFail($productId);
        
        $substitute = $this->replicate();
        $substitute->product_id = $productId;
        $substitute->name = $substituteProduct->name;
        $substitute->sku = $substituteProduct->sku;
        $substitute->is_substitution = true;
        $substitute->original_item_id = $this->id;
        $substitute->substitution_reason = $reason;
        $substitute->status = self::STATUS_PENDING;
        $substitute->save();

        $this->status = self::STATUS_SUBSTITUTED;
        $this->save();

        return $substitute;
    }

    public function isFullyDelivered(): bool
    {
        return $this->delivered_quantity >= $this->quantity;
    }

    public function isPartiallyDelivered(): bool
    {
        return $this->delivered_quantity > 0 && $this->delivered_quantity < $this->quantity;
    }

    public function getUndeliveredQuantity(): float
    {
        return max(0, $this->quantity - ($this->delivered_quantity ?? 0));
    }

    public function getAvailableForReturn(): float
    {
        return max(0, ($this->delivered_quantity ?? 0) - ($this->returned_quantity ?? 0));
    }

    public function hasVariations(): bool
    {
        return !is_null($this->product_variant_id);
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost_price > 0) {
            return (($this->unit_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    public function getProfitAttribute(): float
    {
        return ($this->unit_price - ($this->cost_price ?? 0)) * $this->quantity;
    }
}