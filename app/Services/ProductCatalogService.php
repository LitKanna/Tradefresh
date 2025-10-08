<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductPriceTier;
use App\Models\ProductInventory;
use App\Models\RecentlyViewedProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProductCatalogService
{
    /**
     * Search and filter products
     */
    public function searchProducts(array $filters = [], $perPage = 24)
    {
        $query = Product::with(['vendor', 'category', 'primaryImage', 'priceTiers'])
            ->active();

        // Apply search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply filters
        $query->withFilters($filters);

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'relevance';
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'rating':
                $query->withAvg('approvedReviews', 'rating')
                    ->orderByDesc('approved_reviews_avg_rating');
                break;
            default:
                $query->orderBy('featured', 'desc')
                    ->orderBy('views_count', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 8)
    {
        return Product::with(['vendor', 'category', 'primaryImage'])
            ->active()
            ->featured()
            ->inStock()
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId, $limit = 12)
    {
        return Product::with(['vendor', 'primaryImage'])
            ->active()
            ->byCategory($categoryId)
            ->inStock()
            ->orderBy('featured', 'desc')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently viewed products for a buyer
     */
    public function getRecentlyViewed($buyerId, $limit = 10)
    {
        return RecentlyViewedProduct::getRecentlyViewed($buyerId, $limit);
    }

    /**
     * Record product view
     */
    public function recordProductView($productId, $buyerId = null)
    {
        $product = Product::find($productId);
        
        if ($product) {
            $product->incrementViewCount();
            
            if ($buyerId) {
                RecentlyViewedProduct::recordView($buyerId, $productId);
            }
        }
        
        return $product;
    }

    /**
     * Get product details with all relationships
     */
    public function getProductDetails($productId)
    {
        return Product::with([
            'vendor',
            'category',
            'productImages',
            'variations' => function($query) {
                $query->where('is_active', true);
            },
            'priceTiers',
            'attributes',
            'approvedReviews.buyer',
            'inventory'
        ])->findOrFail($productId);
    }

    /**
     * Create or update product
     */
    public function saveProduct(array $data, $productId = null)
    {
        DB::beginTransaction();
        
        try {
            $product = $productId 
                ? Product::findOrFail($productId)
                : new Product();
            
            $product->fill($data);
            $product->save();
            
            // Handle images
            if (!empty($data['images'])) {
                $this->handleProductImages($product, $data['images']);
            }
            
            // Handle variations
            if (!empty($data['variations'])) {
                $this->handleProductVariations($product, $data['variations']);
            }
            
            // Handle price tiers
            if (!empty($data['price_tiers'])) {
                $this->handlePriceTiers($product, $data['price_tiers']);
            }
            
            // Handle attributes
            if (!empty($data['attributes'])) {
                $this->handleProductAttributes($product, $data['attributes']);
            }
            
            // Handle inventory
            if (!empty($data['inventory'])) {
                $this->handleProductInventory($product, $data['inventory']);
            }
            
            DB::commit();
            
            return $product->fresh();
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle product images
     */
    protected function handleProductImages(Product $product, array $images)
    {
        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $path = $image->store('products/' . $product->id, 'public');
                
                // Create thumbnail
                $thumbnailPath = $this->createThumbnail($image, $product->id);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                    'alt_text' => $product->name
                ]);
            }
        }
    }

    /**
     * Create image thumbnail
     */
    protected function createThumbnail(UploadedFile $image, $productId)
    {
        $thumbnailName = 'thumb_' . Str::random(10) . '.' . $image->extension();
        $thumbnailPath = 'products/' . $productId . '/thumbnails/' . $thumbnailName;
        
        $img = Image::make($image)->fit(300, 300);
        Storage::disk('public')->put($thumbnailPath, $img->encode());
        
        return $thumbnailPath;
    }

    /**
     * Handle product variations
     */
    protected function handleProductVariations(Product $product, array $variations)
    {
        // Delete removed variations
        $variationIds = array_filter(array_column($variations, 'id'));
        $product->variations()->whereNotIn('id', $variationIds)->delete();
        
        foreach ($variations as $variationData) {
            $variation = isset($variationData['id']) 
                ? ProductVariation::find($variationData['id'])
                : new ProductVariation();
            
            $variation->fill($variationData);
            $variation->product_id = $product->id;
            $variation->save();
            
            // Handle variation price tiers
            if (!empty($variationData['price_tiers'])) {
                $this->handleVariationPriceTiers($variation, $variationData['price_tiers']);
            }
        }
    }

    /**
     * Handle price tiers
     */
    protected function handlePriceTiers(Product $product, array $priceTiers)
    {
        $product->priceTiers()->whereNull('variation_id')->delete();
        
        foreach ($priceTiers as $tier) {
            ProductPriceTier::create([
                'product_id' => $product->id,
                'min_quantity' => $tier['min_quantity'],
                'max_quantity' => $tier['max_quantity'] ?? null,
                'price' => $tier['price'],
                'discount_percentage' => $tier['discount_percentage'] ?? 0
            ]);
        }
    }

    /**
     * Handle variation price tiers
     */
    protected function handleVariationPriceTiers(ProductVariation $variation, array $priceTiers)
    {
        $variation->priceTiers()->delete();
        
        foreach ($priceTiers as $tier) {
            ProductPriceTier::create([
                'product_id' => $variation->product_id,
                'variation_id' => $variation->id,
                'min_quantity' => $tier['min_quantity'],
                'max_quantity' => $tier['max_quantity'] ?? null,
                'price' => $tier['price'],
                'discount_percentage' => $tier['discount_percentage'] ?? 0
            ]);
        }
    }

    /**
     * Handle product attributes
     */
    protected function handleProductAttributes(Product $product, array $attributes)
    {
        $product->attributes()->delete();
        
        foreach ($attributes as $name => $value) {
            $product->attributes()->create([
                'attribute_name' => $name,
                'attribute_value' => $value
            ]);
        }
    }

    /**
     * Handle product inventory
     */
    protected function handleProductInventory(Product $product, array $inventoryData)
    {
        $inventory = $product->inventory()->firstOrNew([
            'vendor_id' => $inventoryData['vendor_id'] ?? $product->vendor_id
        ]);
        
        $inventory->fill($inventoryData);
        $inventory->save();
    }

    /**
     * Get filter options for products
     */
    public function getFilterOptions()
    {
        return [
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'vendors' => \App\Models\Vendor::active()->orderBy('business_name')->get(),
            'brands' => Product::distinct()->whereNotNull('brand')->pluck('brand'),
            'origins' => Product::distinct()->whereNotNull('origin')->pluck('origin'),
            'unit_types' => ['piece', 'kg', 'box', 'carton', 'pallet', 'bundle', 'pack'],
            'price_ranges' => [
                ['min' => 0, 'max' => 50, 'label' => 'Under $50'],
                ['min' => 50, 'max' => 100, 'label' => '$50 - $100'],
                ['min' => 100, 'max' => 250, 'label' => '$100 - $250'],
                ['min' => 250, 'max' => 500, 'label' => '$250 - $500'],
                ['min' => 500, 'max' => null, 'label' => 'Over $500']
            ]
        ];
    }

    /**
     * Get bulk pricing calculator
     */
    public function calculateBulkPrice($productId, $quantity, $variationId = null)
    {
        $product = Product::findOrFail($productId);
        
        if ($variationId) {
            $variation = ProductVariation::findOrFail($variationId);
            $unitPrice = $variation->getPriceForQuantity($quantity);
            $discount = $variation->getDiscountForQuantity($quantity);
        } else {
            $unitPrice = $product->getPriceForQuantity($quantity);
            $tier = $product->priceTiers()
                ->where('min_quantity', '<=', $quantity)
                ->whereNull('variation_id')
                ->orderBy('min_quantity', 'desc')
                ->first();
            
            $discount = $tier ? $tier->discount_percentage : 0;
        }
        
        return [
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'discount_percentage' => $discount,
            'savings' => ($product->price - $unitPrice) * $quantity
        ];
    }

    /**
     * Get recommended products
     */
    public function getRecommendedProducts($productId, $limit = 4)
    {
        $product = Product::findOrFail($productId);
        
        return Product::with(['vendor', 'primaryImage'])
            ->active()
            ->inStock()
            ->where('id', '!=', $productId)
            ->where(function($query) use ($product) {
                $query->where('category_id', $product->category_id)
                    ->orWhere('vendor_id', $product->vendor_id);
            })
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get frequently bought together products
     */
    public function getFrequentlyBoughtTogether(Product $product, $limit = 4)
    {
        return DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->join('products', 'oi2.product_id', '=', 'products.id')
            ->where('oi1.product_id', $product->id)
            ->where('oi2.product_id', '!=', $product->id)
            ->where('products.is_active', true)
            ->where('products.in_stock', true)
            ->select('products.*', DB::raw('COUNT(*) as frequency'))
            ->groupBy('products.id')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return Product::hydrate([$item])->first();
            });
    }

    /**
     * Build comparison data for products
     */
    public function buildComparisonData($products)
    {
        $comparisonData = [
            'basic' => [],
            'pricing' => [],
            'specifications' => [],
            'availability' => [],
            'ratings' => []
        ];

        $allAttributes = [];
        foreach ($products as $product) {
            foreach ($product->attributes as $attr) {
                if (!in_array($attr->attribute_name, $allAttributes)) {
                    $allAttributes[] = $attr->attribute_name;
                }
            }
        }

        foreach ($products as $product) {
            // Basic info
            $comparisonData['basic'][$product->id] = [
                'name' => $product->name,
                'sku' => $product->sku,
                'vendor' => $product->vendor->business_name,
                'category' => $product->category->name,
                'brand' => $product->brand,
                'origin' => $product->origin
            ];

            // Pricing
            $comparisonData['pricing'][$product->id] = [
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'discount' => $product->discount_percentage,
                'bulk_available' => $product->priceTiers->count() > 0
            ];

            // Specifications
            $comparisonData['specifications'][$product->id] = [
                'unit' => $product->unit,
                'unit_type' => $product->unit_type,
                'min_order' => $product->min_order_quantity,
                'max_order' => $product->max_order_quantity,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions
            ];

            // Add custom attributes
            foreach ($allAttributes as $attrName) {
                $attr = $product->attributes->where('attribute_name', $attrName)->first();
                $comparisonData['specifications'][$product->id][$attrName] = $attr ? $attr->attribute_value : '-';
            }

            // Availability
            $comparisonData['availability'][$product->id] = [
                'in_stock' => $product->in_stock,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => $product->is_active
            ];

            // Ratings
            $comparisonData['ratings'][$product->id] = [
                'average_rating' => $product->average_rating,
                'review_count' => $product->review_count
            ];
        }

        return $comparisonData;
    }

    /**
     * Get inventory status for a product
     */
    public function getInventoryStatus(Product $product)
    {
        $status = [
            'in_stock' => $product->in_stock,
            'stock_quantity' => $product->stock_quantity,
            'low_stock' => $product->is_low_stock,
            'availability_message' => '',
            'can_order' => true
        ];

        if (!$product->in_stock) {
            $status['availability_message'] = 'Out of Stock';
            $status['can_order'] = false;
        } elseif ($product->is_low_stock) {
            $status['availability_message'] = "Only {$product->stock_quantity} left in stock";
        } else {
            $status['availability_message'] = 'In Stock';
        }

        // Check for upcoming restock
        $inventory = $product->inventory()->first();
        if ($inventory && $inventory->last_restocked_at) {
            $daysSinceRestock = now()->diffInDays($inventory->last_restocked_at);
            if ($daysSinceRestock < 7) {
                $status['recently_restocked'] = true;
            }
        }

        return $status;
    }

    /**
     * Process bulk order
     */
    public function processBulkOrder($buyer, array $data)
    {
        DB::beginTransaction();

        try {
            $cart = $buyer->cart ?? $buyer->cart()->create();
            $addedCount = 0;
            $errors = [];

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    $errors[] = "Product ID {$item['product_id']} not found";
                    continue;
                }

                if (!$product->canBeOrderedBy($buyer, $item['quantity'])) {
                    $errors[] = "{$product->name}: Cannot order {$item['quantity']} units";
                    continue;
                }

                // Get bulk pricing
                $price = $product->getPriceForQuantity($item['quantity']);

                // Add to cart
                $cart->items()->create([
                    'product_id' => $product->id,
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'notes' => $data['notes'] ?? null
                ]);

                $addedCount++;
            }

            if ($addedCount === 0) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => 'No items could be added to cart',
                    'errors' => $errors
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "{$addedCount} items added to cart successfully",
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'Failed to process bulk order: ' . $e->getMessage()
            ];
        }
    }
}