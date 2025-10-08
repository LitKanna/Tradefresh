<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardCacheService
{
    const CACHE_TTL = 300; // 5 minutes default

    const STATS_CACHE_TTL = 60; // 1 minute for stats

    /**
     * Get cached products with eager loading
     */
    public function getCachedProducts($limit = 16)
    {
        return Cache::remember('dashboard.products', self::CACHE_TTL, function () use ($limit) {
            // Try to get fruits category first
            $fruitsCategory = \App\Models\Category::where('name', 'LIKE', '%fruit%')
                ->orWhere('name', 'LIKE', '%Fruit%')
                ->first();

            $query = Product::query()
                ->where('is_active', true)
                ->with(['category', 'vendor:id,business_name'])
                ->select(['id', 'name', 'price', 'unit', 'category_id', 'vendor_id', 'stock_quantity']);

            // If fruits category exists, prioritize fruits
            if ($fruitsCategory) {
                $query->where('category_id', $fruitsCategory->id);
            }

            return $query->limit($limit)->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'unit' => $product->unit,
                        'category' => $product->category->name ?? 'Uncategorized',
                        'vendor_id' => $product->vendor_id,
                        'vendor_name' => $product->vendor->business_name ?? '',
                        'in_stock' => $product->stock_quantity > 0,
                        'price_change' => 0,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get buyer quotes with optimized eager loading
     */
    public function getBuyerQuotes($buyerId, $bypassCache = false)
    {
        $cacheKey = "buyer.{$buyerId}.quotes";

        // Allow bypassing cache for real-time updates
        if ($bypassCache) {
            Cache::forget($cacheKey);
        }

        // For quotes, use very short cache time (10 seconds) since they're time-sensitive
        return Cache::remember($cacheKey, 10, function () use ($buyerId) {
            return Quote::query()
                ->where('buyer_id', $buyerId)
                ->where('status', 'submitted')
                ->with([
                    'vendor:id,business_name,email',
                    'rfq:id,product_name,quantity,unit',
                ])
                ->select(['id', 'vendor_id', 'buyer_id', 'rfq_id', 'total_amount', 'final_amount', 'status', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($quote) {
                    $createdAt = $quote->created_at;
                    // Extend quote visibility to 2 hours for better UX
                    $expiryTime = $createdAt->copy()->addHours(2);
                    $now = now();
                    // Check if expired (expiry time is in the past)
                    $isExpired = $expiryTime->isPast();
                    // Calculate remaining seconds for acceptance (30 minutes)
                    $acceptanceDeadline = $createdAt->copy()->addMinutes(30);
                    $canAccept = $acceptanceDeadline > $now;
                    $remainingSeconds = $canAccept ? $now->diffInSeconds($acceptanceDeadline) : 0;

                    return [
                        'id' => $quote->id,
                        'vendor_id' => $quote->vendor_id,
                        'vendor_name' => $quote->vendor->business_name ?? 'Unknown Vendor',
                        'rfq_id' => $quote->rfq_id,
                        'product_name' => $quote->rfq->product_name ?? '',
                        'quantity' => $quote->rfq->quantity ?? 0,
                        'unit' => $quote->rfq->unit ?? '',
                        'total_amount' => floatval($quote->total_amount ?? 0),
                        'final_amount' => floatval($quote->final_amount ?? $quote->total_amount ?? 0),
                        'status' => $quote->status,
                        'created_at' => $quote->created_at->toISOString(),
                        'remaining_time' => $canAccept ? sprintf('%d:%02d', floor($remainingSeconds / 60), $remainingSeconds % 60) : 'Expired',
                        'expires_at' => $acceptanceDeadline->timestamp * 1000,
                        'is_expired' => ! $canAccept, // Only mark as expired if can't accept
                        'is_visible' => ! $isExpired, // Still visible for 2 hours
                    ];
                })
                // Filter to only show quotes less than 2 hours old
                ->filter(function ($quote) {
                    return $quote['is_visible'] ?? true;
                })
                ->values() // Reset array keys after filtering
                ->toArray();
        });
    }

    /**
     * Get dashboard statistics with caching
     */
    public function getDashboardStats($buyerId)
    {
        $cacheKey = "buyer.{$buyerId}.stats";

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($buyerId) {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            // Use select to only fetch needed columns
            $ordersQuery = Order::where('buyer_id', $buyerId)
                ->select(['id', 'status', 'total_amount', 'created_at']);

            return [
                'total_orders' => (clone $ordersQuery)->count(),
                'pending_orders' => (clone $ordersQuery)->where('status', 'pending')->count(),
                'today_orders' => (clone $ordersQuery)->where('created_at', '>=', $today)->count(),
                'week_orders' => (clone $ordersQuery)->where('created_at', '>=', $thisWeek)->count(),
                'month_orders' => (clone $ordersQuery)->where('created_at', '>=', $thisMonth)->count(),
                'total_spent' => (clone $ordersQuery)->sum('total_amount'),
                'month_spent' => (clone $ordersQuery)->where('created_at', '>=', $thisMonth)->sum('total_amount'),
            ];
        });
    }

    /**
     * Clear buyer-specific cache
     */
    public function clearBuyerCache($buyerId)
    {
        Cache::forget("buyer.{$buyerId}.quotes");
        Cache::forget("buyer.{$buyerId}.stats");
        Log::info("Cleared cache for buyer: {$buyerId}");
    }

    /**
     * Clear all dashboard caches
     */
    public function clearAllCache()
    {
        Cache::forget('dashboard.products');
        Cache::tags(['dashboard'])->flush();
        Log::info('Cleared all dashboard caches');
    }

    /**
     * Warm up cache for a specific buyer
     */
    public function warmUpCache($buyerId)
    {
        $this->getCachedProducts();
        $this->getBuyerQuotes($buyerId);
        $this->getDashboardStats($buyerId);
        Log::info("Warmed up cache for buyer: {$buyerId}");
    }
}
