<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Product;
use App\Models\ProductRecommendation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecentlyViewedProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductRecommendationService
{
    /**
     * Generate product recommendations for a buyer
     */
    public function generateRecommendations(Buyer $buyer)
    {
        // Clear old recommendations
        ProductRecommendation::where('buyer_id', $buyer->id)
            ->where('expires_at', '<', now())
            ->delete();

        // Generate different types of recommendations
        $this->generateHistoryBasedRecommendations($buyer);
        $this->generateFrequentlyBoughtRecommendations($buyer);
        $this->generateSimilarProductRecommendations($buyer);
        $this->generateTrendingRecommendations($buyer);
        $this->generateSeasonalRecommendations($buyer);
        $this->generateNewArrivalRecommendations($buyer);
    }

    /**
     * Get product recommendations for display
     */
    public function getProductRecommendations(Buyer $buyer, Product $currentProduct = null)
    {
        $query = ProductRecommendation::where('buyer_id', $buyer->id)
            ->active()
            ->with(['product.vendor', 'product.primaryImage']);

        if ($currentProduct) {
            // Exclude current product from recommendations
            $query->where('product_id', '!=', $currentProduct->id);
        }

        return $query->limit(12)->get();
    }

    /**
     * Generate recommendations based on order history
     */
    protected function generateHistoryBasedRecommendations(Buyer $buyer)
    {
        // Get buyer's frequently purchased categories
        $frequentCategories = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.buyer_id', $buyer->id)
            ->where('orders.created_at', '>', now()->subMonths(3))
            ->select('products.category_id', DB::raw('COUNT(*) as purchase_count'))
            ->groupBy('products.category_id')
            ->orderBy('purchase_count', 'desc')
            ->limit(5)
            ->pluck('category_id');

        // Get products from those categories that buyer hasn't purchased
        $purchasedProductIds = OrderItem::whereHas('order', function ($query) use ($buyer) {
            $query->where('buyer_id', $buyer->id);
        })->pluck('product_id');

        $recommendedProducts = Product::whereIn('category_id', $frequentCategories)
            ->whereNotIn('id', $purchasedProductIds)
            ->active()
            ->inStock()
            ->orderBy('views_count', 'desc')
            ->limit(20)
            ->get();

        foreach ($recommendedProducts as $product) {
            ProductRecommendation::updateOrCreate(
                [
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'recommendation_type' => ProductRecommendation::TYPE_BASED_ON_HISTORY
                ],
                [
                    'score' => $this->calculateRecommendationScore($product, $buyer),
                    'reason' => 'Based on your purchase history',
                    'expires_at' => now()->addDays(7)
                ]
            );
        }
    }

    /**
     * Generate frequently bought together recommendations
     */
    protected function generateFrequentlyBoughtRecommendations(Buyer $buyer)
    {
        // Get buyer's recent purchases
        $recentProductIds = OrderItem::whereHas('order', function ($query) use ($buyer) {
            $query->where('buyer_id', $buyer->id)
                  ->where('created_at', '>', now()->subMonth());
        })->pluck('product_id')->unique();

        foreach ($recentProductIds as $productId) {
            // Find products frequently bought with this product
            $frequentlyBought = DB::table('order_items as oi1')
                ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
                ->where('oi1.product_id', $productId)
                ->where('oi2.product_id', '!=', $productId)
                ->select('oi2.product_id', DB::raw('COUNT(*) as frequency'))
                ->groupBy('oi2.product_id')
                ->orderBy('frequency', 'desc')
                ->limit(5)
                ->pluck('product_id');

            foreach ($frequentlyBought as $recommendedProductId) {
                ProductRecommendation::updateOrCreate(
                    [
                        'buyer_id' => $buyer->id,
                        'product_id' => $recommendedProductId,
                        'recommendation_type' => ProductRecommendation::TYPE_FREQUENTLY_BOUGHT
                    ],
                    [
                        'score' => 0.8,
                        'reason' => 'Frequently bought together',
                        'metadata' => ['related_product_id' => $productId],
                        'expires_at' => now()->addDays(14)
                    ]
                );
            }
        }
    }

    /**
     * Generate similar product recommendations
     */
    protected function generateSimilarProductRecommendations(Buyer $buyer)
    {
        // Get recently viewed products
        $recentlyViewed = RecentlyViewedProduct::where('buyer_id', $buyer->id)
            ->orderBy('viewed_at', 'desc')
            ->limit(10)
            ->pluck('product_id');

        foreach ($recentlyViewed as $productId) {
            $product = Product::find($productId);
            if (!$product) continue;

            // Find similar products
            $similarProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $productId)
                ->active()
                ->inStock()
                ->when($product->brand, function ($query) use ($product) {
                    $query->orWhere('brand', $product->brand);
                })
                ->orderBy('views_count', 'desc')
                ->limit(5)
                ->get();

            foreach ($similarProducts as $similarProduct) {
                ProductRecommendation::updateOrCreate(
                    [
                        'buyer_id' => $buyer->id,
                        'product_id' => $similarProduct->id,
                        'recommendation_type' => ProductRecommendation::TYPE_SIMILAR_PRODUCTS
                    ],
                    [
                        'score' => $this->calculateSimilarityScore($product, $similarProduct),
                        'reason' => "Similar to {$product->name}",
                        'metadata' => ['similar_to' => $productId],
                        'expires_at' => now()->addDays(7)
                    ]
                );
            }
        }
    }

    /**
     * Generate trending product recommendations
     */
    protected function generateTrendingRecommendations(Buyer $buyer)
    {
        // Get trending products based on recent views and orders
        $trendingProducts = Product::active()
            ->inStock()
            ->withCount([
                'orderItems as recent_orders' => function ($query) {
                    $query->whereHas('order', function ($q) {
                        $q->where('created_at', '>', now()->subWeek());
                    });
                }
            ])
            ->orderBy('recent_orders', 'desc')
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get();

        foreach ($trendingProducts as $product) {
            ProductRecommendation::updateOrCreate(
                [
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'recommendation_type' => ProductRecommendation::TYPE_TRENDING
                ],
                [
                    'score' => 0.7,
                    'reason' => 'Trending this week',
                    'expires_at' => now()->addDays(3)
                ]
            );
        }
    }

    /**
     * Generate seasonal recommendations
     */
    protected function generateSeasonalRecommendations(Buyer $buyer)
    {
        $currentMonth = now()->month;
        
        // Define seasonal categories (customize based on business)
        $seasonalCategories = $this->getSeasonalCategories($currentMonth);
        
        $seasonalProducts = Product::whereIn('category_id', $seasonalCategories)
            ->active()
            ->inStock()
            ->featured()
            ->limit(10)
            ->get();

        foreach ($seasonalProducts as $product) {
            ProductRecommendation::updateOrCreate(
                [
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'recommendation_type' => ProductRecommendation::TYPE_SEASONAL
                ],
                [
                    'score' => 0.6,
                    'reason' => 'Seasonal favorite',
                    'expires_at' => now()->addDays(30)
                ]
            );
        }
    }

    /**
     * Generate new arrival recommendations
     */
    protected function generateNewArrivalRecommendations(Buyer $buyer)
    {
        // Get buyer's preferred categories
        $preferredCategories = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.buyer_id', $buyer->id)
            ->distinct()
            ->pluck('products.category_id');

        $newArrivals = Product::whereIn('category_id', $preferredCategories)
            ->where('created_at', '>', now()->subWeeks(2))
            ->active()
            ->inStock()
            ->limit(10)
            ->get();

        foreach ($newArrivals as $product) {
            ProductRecommendation::updateOrCreate(
                [
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'recommendation_type' => ProductRecommendation::TYPE_NEW_ARRIVAL
                ],
                [
                    'score' => 0.65,
                    'reason' => 'New arrival in your preferred category',
                    'expires_at' => now()->addDays(14)
                ]
            );
        }
    }

    /**
     * Calculate recommendation score based on various factors
     */
    protected function calculateRecommendationScore(Product $product, Buyer $buyer): float
    {
        $score = 0.5; // Base score

        // Factor in product rating
        if ($product->average_rating > 4) {
            $score += 0.1;
        }

        // Factor in product views
        if ($product->views_count > 100) {
            $score += 0.05;
        }

        // Factor in if vendor is a favorite
        if ($buyer->favoriteVendors()->where('vendor_id', $product->vendor_id)->exists()) {
            $score += 0.15;
        }

        // Factor in price range preference
        $avgOrderValue = $buyer->orders()->avg('total_amount');
        if ($avgOrderValue && abs($product->price - $avgOrderValue) < $avgOrderValue * 0.2) {
            $score += 0.1;
        }

        // Factor in if featured product
        if ($product->is_featured) {
            $score += 0.1;
        }

        return min($score, 1.0); // Cap at 1.0
    }

    /**
     * Calculate similarity score between two products
     */
    protected function calculateSimilarityScore(Product $product1, Product $product2): float
    {
        $score = 0.3; // Base score for being in same category

        // Same brand
        if ($product1->brand && $product1->brand === $product2->brand) {
            $score += 0.2;
        }

        // Similar price range (within 20%)
        $priceDiff = abs($product1->price - $product2->price);
        $avgPrice = ($product1->price + $product2->price) / 2;
        if ($avgPrice > 0 && ($priceDiff / $avgPrice) < 0.2) {
            $score += 0.2;
        }

        // Same vendor
        if ($product1->vendor_id === $product2->vendor_id) {
            $score += 0.15;
        }

        // Similar origin
        if ($product1->origin && $product1->origin === $product2->origin) {
            $score += 0.1;
        }

        // Both featured
        if ($product1->is_featured && $product2->is_featured) {
            $score += 0.05;
        }

        return min($score, 1.0);
    }

    /**
     * Get seasonal categories based on month
     */
    protected function getSeasonalCategories(int $month): array
    {
        // This should be customized based on your business
        // Example implementation for a food market
        $seasonal = [
            1 => [1, 5, 8],  // January - Winter produce
            2 => [1, 5, 8],  // February
            3 => [2, 6, 9],  // March - Spring items
            4 => [2, 6, 9],  // April
            5 => [2, 6, 9],  // May
            6 => [3, 7, 10], // June - Summer produce
            7 => [3, 7, 10], // July
            8 => [3, 7, 10], // August
            9 => [4, 8, 11], // September - Fall items
            10 => [4, 8, 11], // October
            11 => [4, 8, 11], // November
            12 => [1, 5, 8], // December - Winter produce
        ];

        return $seasonal[$month] ?? [];
    }
}