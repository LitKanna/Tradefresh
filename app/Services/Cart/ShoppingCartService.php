<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Business;
use App\Models\BulkDiscountTier;
use App\Services\Inventory\StockManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ShoppingCartService
{
    protected StockManagementService $stockService;
    
    public function __construct(StockManagementService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get or create cart for user/session
     */
    public function getCart($userId = null, $sessionId = null): Cart
    {
        if ($userId) {
            return Cart::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                [
                    'session_id' => $sessionId ?? Session::getId(),
                    'expires_at' => Carbon::now()->addDays(30)
                ]
            );
        }
        
        $sessionId = $sessionId ?? Session::getId();
        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'status' => 'active', 'user_id' => null],
            ['expires_at' => Carbon::now()->addHours(24)]
        );
    }

    /**
     * Add item to cart with real-time stock validation
     */
    public function addItem(array $data): CartItem
    {
        return DB::transaction(function () use ($data) {
            $cart = $this->getCart($data['user_id'] ?? null, $data['session_id'] ?? null);
            $product = Product::findOrFail($data['product_id']);
            
            // Check stock availability
            $stockAvailable = $this->stockService->checkAvailability(
                $product->id,
                $data['quantity'],
                $data['variant_id'] ?? null
            );
            
            if (!$stockAvailable['available']) {
                throw new \Exception($stockAvailable['message'] ?? 'Product out of stock');
            }
            
            // Check for existing item
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('variant_id', $data['variant_id'] ?? null)
                ->first();
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem->quantity + $data['quantity'];
                
                // Validate against max order quantity
                if ($product->max_order_quantity && $newQuantity > $product->max_order_quantity) {
                    throw new \Exception("Maximum order quantity is {$product->max_order_quantity}");
                }
                
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $this->calculateUnitPrice($product, $newQuantity, $cart->user_id)
                ]);
                
                $cartItem = $existingItem;
            } else {
                // Create new item
                $cartItem = $cart->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $data['variant_id'] ?? null,
                    'vendor_id' => $product->vendor_id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $this->calculateUnitPrice($product, $data['quantity'], $cart->user_id),
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image' => $product->main_image,
                    'notes' => $data['notes'] ?? null,
                    'metadata' => [
                        'added_at' => now(),
                        'source' => $data['source'] ?? 'web',
                        'promo_code' => $data['promo_code'] ?? null
                    ]
                ]);
            }
            
            // Update cart totals
            $this->updateCartTotals($cart);
            
            // Clear cart cache
            Cache::forget("cart_{$cart->id}");
            
            return $cartItem;
        });
    }

    /**
     * Update cart item quantity
     */
    public function updateItemQuantity(int $itemId, float $quantity): CartItem
    {
        return DB::transaction(function () use ($itemId, $quantity) {
            $item = CartItem::findOrFail($itemId);
            
            if ($quantity <= 0) {
                return $this->removeItem($itemId);
            }
            
            // Check stock availability
            $stockAvailable = $this->stockService->checkAvailability(
                $item->product_id,
                $quantity,
                $item->variant_id
            );
            
            if (!$stockAvailable['available']) {
                throw new \Exception($stockAvailable['message'] ?? 'Requested quantity not available');
            }
            
            // Check max order quantity
            if ($item->product->max_order_quantity && $quantity > $item->product->max_order_quantity) {
                throw new \Exception("Maximum order quantity is {$item->product->max_order_quantity}");
            }
            
            // Update with new pricing
            $item->update([
                'quantity' => $quantity,
                'unit_price' => $this->calculateUnitPrice($item->product, $quantity, $item->cart->user_id)
            ]);
            
            // Update cart totals
            $this->updateCartTotals($item->cart);
            
            return $item;
        });
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $itemId): bool
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;
        
        $deleted = $item->delete();
        
        if ($deleted) {
            $this->updateCartTotals($cart);
            Cache::forget("cart_{$cart->id}");
        }
        
        return $deleted;
    }

    /**
     * Clear entire cart
     */
    public function clearCart($userId = null, $sessionId = null): bool
    {
        $cart = $this->getCart($userId, $sessionId);
        
        DB::transaction(function () use ($cart) {
            $cart->items()->delete();
            $cart->update([
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'metadata' => null
            ]);
        });
        
        Cache::forget("cart_{$cart->id}");
        
        return true;
    }

    /**
     * Merge session cart with user cart on login
     */
    public function mergeCarts(string $sessionId, int $userId): Cart
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $sessionCart = Cart::where('session_id', $sessionId)
                ->where('status', 'active')
                ->whereNull('user_id')
                ->first();
            
            $userCart = $this->getCart($userId);
            
            if ($sessionCart && $sessionCart->items->count() > 0) {
                foreach ($sessionCart->items as $sessionItem) {
                    // Check if item exists in user cart
                    $existingItem = $userCart->items()
                        ->where('product_id', $sessionItem->product_id)
                        ->where('variant_id', $sessionItem->variant_id)
                        ->first();
                    
                    if ($existingItem) {
                        // Merge quantities
                        $newQuantity = $existingItem->quantity + $sessionItem->quantity;
                        
                        // Check stock
                        $stockAvailable = $this->stockService->checkAvailability(
                            $sessionItem->product_id,
                            $newQuantity,
                            $sessionItem->variant_id
                        );
                        
                        if ($stockAvailable['available']) {
                            $existingItem->update(['quantity' => $newQuantity]);
                        }
                    } else {
                        // Move item to user cart
                        $sessionItem->update(['cart_id' => $userCart->id]);
                    }
                }
                
                // Mark session cart as merged
                $sessionCart->update(['status' => 'merged']);
            }
            
            // Update totals
            $this->updateCartTotals($userCart);
            
            return $userCart;
        });
    }

    /**
     * Get cart items with product details
     */
    public function getCartItems($userId = null, $sessionId = null): Collection
    {
        $cart = $this->getCart($userId, $sessionId);
        
        return Cache::remember("cart_items_{$cart->id}", 300, function () use ($cart) {
            return $cart->items()
                ->with(['product', 'product.vendor', 'variant'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'vendor_id' => $item->vendor_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'product_image' => $item->product_image,
                        'vendor_name' => $item->product->vendor->business_name ?? null,
                        'stock_status' => $this->stockService->getStockStatus($item->product_id, $item->variant_id),
                        'savings' => $this->calculateSavings($item),
                        'notes' => $item->notes
                    ];
                });
        });
    }

    /**
     * Calculate unit price with business-specific discounts
     */
    protected function calculateUnitPrice(Product $product, float $quantity, ?int $userId): float
    {
        $basePrice = $product->price;
        
        // Apply bulk discount tiers
        $bulkDiscount = BulkDiscountTier::where('product_id', $product->id)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc')
            ->first();
        
        if ($bulkDiscount) {
            if ($bulkDiscount->discount_type === 'percentage') {
                $basePrice = $basePrice * (1 - $bulkDiscount->discount_value / 100);
            } else {
                $basePrice = $basePrice - $bulkDiscount->discount_value;
            }
        }
        
        // Apply business-specific pricing if user is logged in
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && $user->business) {
                $businessPricing = $product->businessPricing()
                    ->where('business_id', $user->business->id)
                    ->first();
                
                if ($businessPricing) {
                    $basePrice = $businessPricing->special_price;
                }
            }
        }
        
        return max(0, $basePrice);
    }

    /**
     * Update cart totals
     */
    protected function updateCartTotals(Cart $cart): void
    {
        $items = $cart->items;
        
        $subtotal = 0;
        foreach ($items as $item) {
            $item->total = $item->quantity * $item->unit_price;
            $item->save();
            $subtotal += $item->total;
        }
        
        $taxRate = 0.10; // 10% GST
        $taxAmount = $subtotal * $taxRate;
        
        // Apply cart-level discounts
        $discountAmount = $this->calculateCartDiscount($cart, $subtotal);
        
        $total = $subtotal + $taxAmount - $discountAmount;
        
        $cart->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'item_count' => $items->count(),
            'updated_at' => now()
        ]);
    }

    /**
     * Calculate cart-level discounts
     */
    protected function calculateCartDiscount(Cart $cart, float $subtotal): float
    {
        $discount = 0;
        
        // Check for promo codes
        if ($cart->promo_code) {
            // Implementation would check promo code validity and apply discount
        }
        
        // Check for volume discounts
        if ($cart->user_id) {
            $user = \App\Models\User::find($cart->user_id);
            if ($user && $user->business) {
                // Apply business tier discounts based on monthly volume
                $monthlyVolume = Order::where('buyer_business_id', $user->business->id)
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount');
                
                if ($monthlyVolume > 50000) {
                    $discount = $subtotal * 0.05; // 5% discount for high volume
                } elseif ($monthlyVolume > 20000) {
                    $discount = $subtotal * 0.03; // 3% discount for medium volume
                }
            }
        }
        
        return $discount;
    }

    /**
     * Calculate savings for cart item
     */
    protected function calculateSavings(CartItem $item): float
    {
        $regularPrice = $item->product->regular_price ?? $item->product->price;
        $savings = ($regularPrice - $item->unit_price) * $item->quantity;
        return max(0, $savings);
    }

    /**
     * Validate cart for checkout
     */
    public function validateForCheckout($userId = null, $sessionId = null): array
    {
        $cart = $this->getCart($userId, $sessionId);
        $errors = [];
        $warnings = [];
        
        if ($cart->items->isEmpty()) {
            $errors[] = 'Cart is empty';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Group items by vendor for minimum order validation
        $itemsByVendor = $cart->items->groupBy('vendor_id');
        
        foreach ($itemsByVendor as $vendorId => $vendorItems) {
            $vendor = \App\Models\User::find($vendorId);
            $vendorTotal = $vendorItems->sum('total');
            
            // Check minimum order amount
            if ($vendor && $vendor->vendorProfile && $vendor->vendorProfile->min_order_amount) {
                if ($vendorTotal < $vendor->vendorProfile->min_order_amount) {
                    $errors[] = "Minimum order amount for {$vendor->business_name} is $" . 
                               number_format($vendor->vendorProfile->min_order_amount, 2);
                }
            }
            
            // Check stock availability
            foreach ($vendorItems as $item) {
                $stockCheck = $this->stockService->checkAvailability(
                    $item->product_id,
                    $item->quantity,
                    $item->variant_id
                );
                
                if (!$stockCheck['available']) {
                    $errors[] = "{$item->product_name}: {$stockCheck['message']}";
                } elseif ($stockCheck['low_stock']) {
                    $warnings[] = "{$item->product_name}: Only {$stockCheck['available_quantity']} available";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'cart' => $cart
        ];
    }

    /**
     * Convert cart to order
     */
    public function convertToOrder(Cart $cart): void
    {
        $cart->update([
            'status' => 'converted',
            'converted_at' => now()
        ]);
        
        Cache::forget("cart_{$cart->id}");
        Cache::forget("cart_items_{$cart->id}");
    }

    /**
     * Get abandoned carts
     */
    public function getAbandonedCarts(): Collection
    {
        return Cart::where('status', 'active')
            ->where('updated_at', '<', Carbon::now()->subHours(24))
            ->whereHas('items')
            ->with(['user', 'items'])
            ->get();
    }

    /**
     * Restore saved cart
     */
    public function restoreSavedCart(int $cartId, int $userId): Cart
    {
        $cart = Cart::findOrFail($cartId);
        
        if ($cart->user_id !== $userId) {
            throw new \Exception('Unauthorized access to cart');
        }
        
        if ($cart->status !== 'saved') {
            throw new \Exception('Cart is not saved');
        }
        
        $cart->update([
            'status' => 'active',
            'restored_at' => now()
        ]);
        
        return $cart;
    }
}