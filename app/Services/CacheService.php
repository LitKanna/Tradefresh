<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use App\Models\Category;
use App\Models\Vendor;

class CacheService
{
    // Cache stores
    const STORE_PRODUCTS = 'products';
    const STORE_API = 'api';
    const STORE_COMPUTED = 'computed';

    // Cache tags
    const TAG_PRODUCTS = 'products';
    const TAG_CATEGORIES = 'categories';
    const TAG_VENDORS = 'vendors';
    const TAG_PRICING = 'pricing';
    const TAG_INVENTORY = 'inventory';

    /**
     * Get or cache product catalog data
     */
    public function getProductCatalog($filters = [], $page = 1, $perPage = 24)
    {
        $cacheKey = $this->buildCacheKey('product_catalog', $filters, ['page' => $page, 'per_page' => $perPage]);
        
        return Cache::store(self::STORE_PRODUCTS)
            ->tags([self::TAG_PRODUCTS, self::TAG_PRICING])
            ->remember($cacheKey, config('cache.ttl.products_list'), function() use ($filters, $page, $perPage) {
                $query = Product::select([
                    'id', 'vendor_id', 'category_id', 'name', 'sku', 'price', 'compare_price',
                    'stock_quantity', 'is_active', 'is_featured', 'image', 'created_at', 'views_count'
                ])
                ->with([
                    'vendor:id,business_name,status,rating',
                    'category:id,name,slug'
                ])
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0);

                // Apply filters
                $this->applyProductFilters($query, $filters);

                return $query->paginate($perPage, ['*'], 'page', $page);
            });
    }

    /**
     * Cache product search results with relevance scoring
     */
    public function getProductSearch($searchTerm, $filters = [], $page = 1, $perPage = 15)
    {
        $cacheKey = $this->buildCacheKey('product_search', compact('searchTerm', 'filters'), ['page' => $page]);
        
        return Cache::store(self::STORE_API)
            ->tags([self::TAG_PRODUCTS])
            ->remember($cacheKey, config('cache.ttl.product_search'), function() use ($searchTerm, $filters, $page, $perPage) {
                $query = Product::select([
                    'id', 'vendor_id', 'category_id', 'name', 'sku', 'price', 'compare_price',
                    'stock_quantity', 'is_active', 'brand', 'image', 'created_at'
                ])
                ->with([
                    'vendor:id,business_name,status,rating',
                    'category:id,name,slug'
                ])
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0);

                // Apply search with relevance scoring
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "{$searchTerm}%")
                      ->orWhere('sku', '=', $searchTerm)
                      ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('brand', 'LIKE', "%{$searchTerm}%");
                });

                // Apply additional filters
                $this->applyProductFilters($query, $filters);

                // Order by relevance
                $query->orderByRaw("CASE 
                    WHEN name = ? THEN 1
                    WHEN sku = ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN brand LIKE ? THEN 4
                    ELSE 5 END, name ASC", 
                    [$searchTerm, $searchTerm, "{$searchTerm}%", "%{$searchTerm}%"]
                );

                return $query->paginate($perPage, ['*'], 'page', $page);
            });
    }

    /**
     * Cache product details with relationships
     */
    public function getProductDetails($productId)
    {
        $cacheKey = "product_details:{$productId}";
        
        return Cache::store(self::STORE_PRODUCTS)
            ->tags([self::TAG_PRODUCTS, self::TAG_PRICING, self::TAG_INVENTORY])
            ->remember($cacheKey, config('cache.ttl.product_details'), function() use ($productId) {
                return Product::with([
                    'vendor:id,business_name,status,rating,description',
                    'category:id,name,slug,description',
                    'primaryImage',
                    'productImages',
                    'priceTiers',
                    'variations.attributes',
                    'reviews' => function($query) {
                        $query->approved()->latest()->limit(10);
                    }
                ])->findOrFail($productId);
            });
    }

    /**
     * Cache categories with product counts
     */
    public function getCategories()
    {
        return Cache::store(self::STORE_COMPUTED)
            ->tags([self::TAG_CATEGORIES])
            ->remember('categories_with_counts', config('cache.ttl.categories'), function() {
                return Category::withCount(['products' => function($query) {
                    $query->active()->inStock();
                }])
                ->active()
                ->with('children')
                ->root()
                ->ordered()
                ->get();
            });
    }

    /**
     * Cache active vendors
     */
    public function getActiveVendors()
    {
        return Cache::store(self::STORE_COMPUTED)
            ->tags([self::TAG_VENDORS])
            ->remember('active_vendors', config('cache.ttl.vendors'), function() {
                return Vendor::select(['id', 'business_name', 'rating', 'total_sales'])
                    ->active()
                    ->orderBy('business_name')
                    ->get();
            });
    }

    /**
     * Cache filter options (brands, origins, quality grades)
     */
    public function getFilterOptions()
    {
        return Cache::store(self::STORE_COMPUTED)
            ->tags([self::TAG_PRODUCTS])
            ->remember('product_filter_options', config('cache.ttl.filter_options'), function() {
                return [
                    'brands' => Product::where('is_active', true)->distinct()->pluck('brand')->filter()->sort()->values(),
                    'origins' => Product::where('is_active', true)->distinct()->pluck('origin_country')->filter()->sort()->values(),
                    'quality_grades' => Product::where('is_active', true)->distinct()->pluck('quality_grade')->filter()->sort()->values(),
                ];
            });
    }

    /**
     * Cache price ranges
     */
    public function getPriceRange()
    {
        return Cache::store(self::STORE_COMPUTED)
            ->tags([self::TAG_PRICING])
            ->remember('product_price_range', config('cache.ttl.price_ranges'), function() {
                $prices = Product::where('is_active', true)
                    ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                    ->first();
                
                return [
                    'min' => $prices->min_price ?? 0,
                    'max' => $prices->max_price ?? 1000
                ];
            });
    }

    /**
     * Cache popular products
     */
    public function getPopularProducts($limit = 10)
    {
        return Cache::store(self::STORE_PRODUCTS)
            ->tags([self::TAG_PRODUCTS])
            ->remember("popular_products:{$limit}", 1800, function() use ($limit) {
                return Product::select([
                    'id', 'name', 'price', 'image', 'views_count', 'vendor_id'
                ])
                ->with('vendor:id,business_name')
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->get();
            });
    }

    /**
     * Cache featured products
     */
    public function getFeaturedProducts($limit = 8)
    {
        return Cache::store(self::STORE_PRODUCTS)
            ->tags([self::TAG_PRODUCTS])
            ->remember("featured_products:{$limit}", config('cache.ttl.products_list'), function() use ($limit) {
                return Product::select([
                    'id', 'name', 'price', 'compare_price', 'image', 'vendor_id'
                ])
                ->with('vendor:id,business_name')
                ->where('is_featured', true)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->latest('updated_at')
                ->limit($limit)
                ->get();
            });
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags)
    {
        foreach ([self::STORE_PRODUCTS, self::STORE_API, self::STORE_COMPUTED] as $store) {
            Cache::store($store)->tags($tags)->flush();
        }
    }

    /**
     * Invalidate product-related cache when product changes
     */
    public function invalidateProductCache($productId = null)
    {
        $this->invalidateByTags([self::TAG_PRODUCTS, self::TAG_PRICING, self::TAG_INVENTORY]);
        
        if ($productId) {
            Cache::store(self::STORE_PRODUCTS)->forget("product_details:{$productId}");
        }
    }

    /**
     * Warm up critical cache entries
     */
    public function warmCache()
    {
        // Warm popular queries
        $this->getCategories();
        $this->getActiveVendors();
        $this->getFilterOptions();
        $this->getPriceRange();
        $this->getPopularProducts();
        $this->getFeaturedProducts();

        // Warm first page of products
        $this->getProductCatalog([], 1, 24);
    }

    /**
     * Build consistent cache key
     */
    private function buildCacheKey($prefix, $filters, $extra = [])
    {
        $data = array_merge($filters, $extra);
        return $prefix . ':' . md5(serialize($data));
    }

    /**
     * Apply product filters to query
     */
    private function applyProductFilters($query, $filters)
    {
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
            $query->whereBetween('price', [$filters['min_price'], $filters['max_price']]);
        } elseif (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        } elseif (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['brand'])) {
            $query->whereIn('brand', (array)$filters['brand']);
        }

        if (!empty($filters['origin'])) {
            $query->whereIn('origin_country', (array)$filters['origin']);
        }

        if (!empty($filters['quality_grade'])) {
            $query->whereIn('quality_grade', (array)$filters['quality_grade']);
        }

        if (!empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        return $query;
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        $redis = Redis::connection('cache');
        
        return [
            'memory_usage' => $redis->info('memory')['used_memory_human'] ?? 'N/A',
            'total_keys' => $redis->dbsize(),
            'hit_rate' => $this->calculateHitRate(),
        ];
    }

    /**
     * Calculate cache hit rate (simplified)
     */
    private function calculateHitRate()
    {
        $redis = Redis::connection('cache');
        $info = $redis->info('stats');
        
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}