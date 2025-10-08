<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'reorder_point',
        'reorder_quantity',
        'location',
        'bin_number',
        'last_restocked',
        'last_counted',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_available' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'last_restocked' => 'date',
        'last_counted' => 'date',
    ];

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Check if needs reorder
     */
    public function getNeedsReorderAttribute(): bool
    {
        return $this->quantity_available <= $this->reorder_point;
    }

    /**
     * Update available quantity
     */
    public function updateAvailableQuantity(): void
    {
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->save();
    }

    /**
     * Reserve quantity
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->quantity_reserved += $quantity;
        $this->updateAvailableQuantity();
        
        return true;
    }

    /**
     * Release reserved quantity
     */
    public function releaseQuantity(int $quantity): void
    {
        $this->quantity_reserved = max(0, $this->quantity_reserved - $quantity);
        $this->updateAvailableQuantity();
    }

    /**
     * Adjust on-hand quantity
     */
    public function adjustQuantity(int $adjustment, string $reason = null): void
    {
        $this->quantity_on_hand += $adjustment;
        $this->updateAvailableQuantity();
        
        if ($adjustment > 0) {
            $this->last_restocked = now();
        }
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_available <= reorder_point');
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_available', '<=', 0);
    }
}