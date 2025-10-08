<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'product_id',
        'variation_id',
        'notes',
        'priority',
        'notify_on_sale',
        'notify_on_stock'
    ];

    protected $casts = [
        'notify_on_sale' => 'boolean',
        'notify_on_stock' => 'boolean',
        'priority' => 'integer'
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 3);
    }
}