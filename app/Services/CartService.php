<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Buyer;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payment\PaymentProcessor;
use App\Services\Invoice\InvoiceGenerator;
use App\Services\NotificationService;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\ProcessCartAbandonmentRecovery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected PaymentProcessor $paymentProcessor;
    protected InvoiceGenerator $invoiceGenerator;
    protected NotificationService $notificationService;

    public function __construct(
        PaymentProcessor $paymentProcessor,
        InvoiceGenerator $invoiceGenerator,
        NotificationService $notificationService
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->invoiceGenerator = $invoiceGenerator;
        $this->notificationService = $notificationService;
    }

    /**
     * Get or create cart for buyer or session
     */
    public function getCart(?Buyer $buyer = null, ?string $sessionId = null): Cart
    {
        $sessionId = $sessionId ?? Session::getId();
        
        // If buyer is provided, look for their active cart
        if ($buyer) {
            $cart = Cart::active()
                ->where('buyer_id', $buyer->id)
                ->first();
            
            if ($cart) {
                // If we also have a session cart, merge it
                $sessionCart = Cart::active()
                    ->where('session_id', $sessionId)
                    ->whereNull('buyer_id')
                    ->first();
                
                if ($sessionCart && $sessionCart->id !== $cart->id) {
                    $cart->merge($sessionCart);
                }
                
                return $cart;
            }
            
            // If no buyer cart exists, check for session cart and convert it
            $sessionCart = Cart::active()
                ->where('session_id', $sessionId)
                ->whereNull('buyer_id')
                ->first();
            
            if ($sessionCart) {
                $sessionCart->update(['buyer_id' => $buyer->id]);
                return $sessionCart;
            }
            
            // Create new cart for buyer
            return Cart::create([
                'buyer_id' => $buyer->id,
                'session_id' => $sessionId
            ]);
        }
        
        // For guest users, use session-based cart
        $cart = Cart::active()
            ->where('session_id', $sessionId)
            ->whereNull('buyer_id')
            ->first();
        
        if (!$cart) {
            $cart = Cart::create(['session_id' => $sessionId]);
        }
        
        return $cart;
    }

    /**
     * Add product to cart
     */
    public function addToCart(
        Product $product, 
        float $quantity, 
        ?Buyer $buyer = null,
        array $options = []
    ): CartItem {
        $cart = $this->getCart($buyer);
        
        // Validate product availability
        $this->validateProductAvailability($product, $quantity);
        
        try {
            $item = $cart->addItem($product, $quantity, $options);
            
            // Track activity for abandonment recovery
            $this->trackCartActivity($cart);
            
            return $item;
        } catch (\Exception $e) {
            throw new \Exception("Could not add product to cart: " . $e->getMessage());
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem(int $cartItemId, float $quantity, ?Buyer $buyer = null): CartItem
    {
        $cart = $this->getCart($buyer);
        $item = $cart->items()->findOrFail($cartItemId);
        
        // Validate new quantity
        $this->validateProductAvailability($item->product, $quantity);
        
        $item->updateQuantity($quantity);
        $this->trackCartActivity($cart);
        
        return $item;
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(int $cartItemId, ?Buyer $buyer = null): void
    {
        $cart = $this->getCart($buyer);
        $cart->removeItem($cartItemId);
        $this->trackCartActivity($cart);
    }

    /**
     * Clear entire cart
     */
    public function clearCart(?Buyer $buyer = null): void
    {
        $cart = $this->getCart($buyer);
        $cart->clear();
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(string $couponCode, ?Buyer $buyer = null): array
    {
        $cart = $this->getCart($buyer);
        
        // Validate coupon (simplified for now)
        $discount = $this->validateAndCalculateCouponDiscount($couponCode, $cart);
        
        $cart->update([
            'coupon_code' => $couponCode,
            'discount_details' => $discount
        ]);
        
        $cart->recalculateTotals();
        
        return $discount;
    }

    /**
     * Remove coupon from cart
     */
    public function removeCoupon(?Buyer $buyer = null): void
    {
        $cart = $this->getCart($buyer);
        
        $cart->update([
            'coupon_code' => null,
            'discount_details' => null
        ]);
        
        $cart->recalculateTotals();
    }

    /**
     * Start checkout process
     */
    public function startCheckout(?Buyer $buyer = null): CheckoutSession
    {
        $cart = $this->getCart($buyer);
        
        if (!$buyer) {
            throw new \Exception("Must be logged in to checkout");
        }
        
        // Validate cart can be checked out
        $errors = $cart->canCheckout();
        if (!empty($errors)) {
            throw new \Exception("Cart cannot be checked out: " . implode(', ', $errors));
        }
        
        // Create or get existing checkout session
        $checkoutSession = CheckoutSession::where('cart_id', $cart->id)
            ->where('status', 'pending')
            ->first();
        
        if (!$checkoutSession) {
            $checkoutSession = CheckoutSession::create([
                'cart_id' => $cart->id,
                'buyer_id' => $buyer->id,
                'subtotal' => $cart->subtotal,
                'tax_amount' => $cart->tax_amount,
                'shipping_amount' => $cart->shipping_amount,
                'discount_amount' => $cart->discount_amount,
                'total_amount' => $cart->total_amount
            ]);
        }
        
        return $checkoutSession;
    }

    /**
     * Complete checkout and create order
     */
    public function completeCheckout(CheckoutSession $checkoutSession): Order
    {
        // Validate checkout session
        $errors = $checkoutSession->canBeCompleted();
        if (!empty($errors)) {
            throw new \Exception("Checkout cannot be completed: " . implode(', ', $errors));
        }
        
        return DB::transaction(function () use ($checkoutSession) {
            $cart = $checkoutSession->cart;
            $buyer = $checkoutSession->buyer;
            
            // Process payment if required
            $paymentResult = $this->processPayment($checkoutSession);
            
            // Create orders (one per vendor)
            $orders = [];
            $vendorGroups = $cart->getVendorGroups();
            
            foreach ($vendorGroups as $vendorGroup) {
                $order = $this->createOrder($checkoutSession, $vendorGroup, $paymentResult);
                $orders[] = $order;
                
                // Generate invoice
                $this->invoiceGenerator->generateForOrder($order);
                
                // Send order confirmation email
                SendOrderConfirmationEmail::dispatch($order);
                
                // Update product stock
                $this->updateProductStock($vendorGroup['items']);
            }
            
            // Mark cart as checked out
            $cart->markAsCheckedOut();
            $checkoutSession->markAsCompleted();
            
            // Send notifications
            $this->sendOrderNotifications($orders);
            
            return $orders[0]; // Return first order for redirect
        });
    }

    /**
     * Recover abandoned cart
     */
    public function recoverAbandonedCart(string $recoveryToken): Cart
    {
        $recovery = \App\Models\CartAbandonmentRecovery::where('recovery_token', $recoveryToken)
            ->where('status', '!=', 'expired')
            ->firstOrFail();
        
        $recovery->markAsClicked();
        
        $cart = $recovery->cart;
        
        // Apply recovery discount if available
        if ($recovery->recovery_discount_percentage > 0) {
            $couponCode = $recovery->generateRecoveryCoupon();
            $this->applyCoupon($couponCode, $recovery->buyer);
        }
        
        return $cart;
    }

    /**
     * Process cart abandonment
     */
    public function processCartAbandonment(): void
    {
        // Find abandoned carts
        $abandonedCarts = Cart::active()
            ->whereNotNull('buyer_id')
            ->where('last_activity_at', '<', now()->subHours(2))
            ->where('items_count', '>', 0)
            ->whereDoesntHave('abandonmentRecovery')
            ->get();
        
        foreach ($abandonedCarts as $cart) {
            $cart->markAsAbandoned();
            
            // Queue abandonment recovery process
            ProcessCartAbandonmentRecovery::dispatch($cart->abandonmentRecovery);
        }
    }

    /**
     * Get cart summary
     */
    public function getCartSummary(?Buyer $buyer = null): array
    {
        $cart = $this->getCart($buyer);
        
        return [
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image' => $item->product->image,
                        'sku' => $item->product->sku
                    ],
                    'vendor' => [
                        'id' => $item->vendor->id,
                        'name' => $item->vendor->name
                    ],
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'original_price' => $item->original_price,
                    'total_price' => $item->total_price,
                    'is_discounted' => $item->isDiscounted(),
                    'bulk_discount_tier' => $item->bulk_discount_tier,
                    'savings' => $item->getSavingsAmount(),
                    'next_tier' => $item->getNextTierInfo()
                ];
            }),
            'vendor_groups' => $cart->getVendorGroups(),
            'totals' => [
                'subtotal' => $cart->subtotal,
                'tax_amount' => $cart->tax_amount,
                'shipping_amount' => $cart->shipping_amount,
                'discount_amount' => $cart->discount_amount,
                'total_amount' => $cart->total_amount,
                'items_count' => $cart->items_count,
                'total_weight' => $cart->total_weight
            ],
            'coupon' => [
                'code' => $cart->coupon_code,
                'details' => $cart->discount_details
            ],
            'validation' => [
                'can_checkout' => empty($cart->canCheckout()),
                'errors' => $cart->canCheckout(),
                'has_minimum_order' => $cart->hasMinimumOrderValue()
            ]
        ];
    }

    /**
     * Calculate shipping options
     */
    public function getShippingOptions(Cart $cart, array $address): array
    {
        $options = [
            [
                'id' => 'standard',
                'name' => 'Standard Delivery',
                'description' => 'Next business day delivery',
                'cost' => $cart->calculateShipping($cart->total_weight),
                'estimated_days' => 1,
                'is_default' => true
            ],
            [
                'id' => 'express',
                'name' => 'Express Delivery',
                'description' => 'Same day delivery (before 2pm)',
                'cost' => $cart->calculateShipping($cart->total_weight) * 2,
                'estimated_days' => 0,
                'is_default' => false
            ],
            [
                'id' => 'pickup',
                'name' => 'Market Pickup',
                'description' => 'Pickup from Sydney Markets',
                'cost' => 0,
                'estimated_days' => 0,
                'is_default' => false
            ]
        ];
        
        // Filter options based on location and cart value
        return collect($options)->filter(function ($option) use ($cart, $address) {
            // Free shipping threshold
            if ($cart->subtotal >= 500 && in_array($option['id'], ['standard', 'express'])) {
                $option['cost'] = 0;
            }
            
            return true;
        })->values()->toArray();
    }

    // Protected helper methods
    
    protected function validateProductAvailability(Product $product, float $quantity): void
    {
        if (!$product->is_active) {
            throw new \Exception("Product is not available");
        }
        
        if (!$product->in_stock) {
            throw new \Exception("Product is out of stock");
        }
        
        if ($quantity < $product->min_order_quantity) {
            throw new \Exception("Minimum order quantity is {$product->min_order_quantity} {$product->unit}");
        }
        
        if ($product->max_order_quantity && $quantity > $product->max_order_quantity) {
            throw new \Exception("Maximum order quantity is {$product->max_order_quantity} {$product->unit}");
        }
        
        if ($quantity > $product->stock_quantity) {
            throw new \Exception("Only {$product->stock_quantity} {$product->unit} available");
        }
    }

    protected function trackCartActivity(Cart $cart): void
    {
        $cart->touch('last_activity_at');
    }

    protected function validateAndCalculateCouponDiscount(string $couponCode, Cart $cart): array
    {
        // Simplified coupon validation
        $validCoupons = [
            'WELCOME10' => ['type' => 'percentage', 'value' => 10, 'min_order' => 100],
            'BULK20' => ['type' => 'percentage', 'value' => 20, 'min_order' => 500],
            'RECOVERY5' => ['type' => 'percentage', 'value' => 5, 'min_order' => 0],
            'RECOVERY10' => ['type' => 'percentage', 'value' => 10, 'min_order' => 0],
            'RECOVERY15' => ['type' => 'percentage', 'value' => 15, 'min_order' => 0]
        ];
        
        if (!isset($validCoupons[$couponCode])) {
            throw new \Exception("Invalid coupon code");
        }
        
        $coupon = $validCoupons[$couponCode];
        
        if ($cart->subtotal < $coupon['min_order']) {
            throw new \Exception("Minimum order value for this coupon is ${$coupon['min_order']}");
        }
        
        $discountAmount = 0;
        if ($coupon['type'] === 'percentage') {
            $discountAmount = $cart->subtotal * ($coupon['value'] / 100);
        }
        
        return [
            'code' => $couponCode,
            'type' => $coupon['type'],
            'value' => $coupon['value'],
            'amount' => $discountAmount,
            'description' => "{$coupon['value']}% off your order"
        ];
    }

    protected function processPayment(CheckoutSession $checkoutSession): ?array
    {
        if ($checkoutSession->payment_method === 'credit_account') {
            // Use credit account, no immediate payment needed
            return ['status' => 'pending', 'method' => 'credit_account'];
        }
        
        if ($checkoutSession->payment_method === 'bank_transfer') {
            // Bank transfer, payment pending
            return ['status' => 'pending', 'method' => 'bank_transfer'];
        }
        
        // For COD or credit card, process with payment processor
        return $this->paymentProcessor->processPayment(
            $checkoutSession->total_amount,
            $checkoutSession->payment_method,
            $checkoutSession->payment_details
        );
    }

    protected function createOrder(
        CheckoutSession $checkoutSession, 
        array $vendorGroup, 
        ?array $paymentResult
    ): Order {
        $order = Order::create([
            'buyer_id' => $checkoutSession->buyer_id,
            'vendor_id' => $vendorGroup['vendor']->id,
            'status' => 'confirmed',
            'total_amount' => $vendorGroup['total'],
            'subtotal' => $vendorGroup['subtotal'],
            'tax_amount' => $vendorGroup['items']->sum('tax_amount'),
            'shipping_amount' => $checkoutSession->shipping_amount,
            'discount_amount' => 0, // Vendor-specific discounts would go here
            'delivery_address' => json_encode($checkoutSession->shipping_address),
            'delivery_notes' => $checkoutSession->delivery_instructions,
            'expected_delivery' => $checkoutSession->preferred_delivery_date ?? now()->addDay(),
            'payment_terms' => $checkoutSession->payment_terms,
            'payment_method' => $checkoutSession->payment_method,
            'payment_status' => $paymentResult['status'] ?? 'pending',
            'notes' => $checkoutSession->order_notes,
            'source' => 'web_cart'
        ]);
        
        // Create order items
        foreach ($vendorGroup['items'] as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
                'special_instructions' => $cartItem->special_instructions
            ]);
        }
        
        return $order;
    }

    protected function updateProductStock(Collection $cartItems): void
    {
        foreach ($cartItems as $item) {
            $item->product->updateStock($item->quantity, 'subtract');
        }
    }

    protected function sendOrderNotifications(array $orders): void
    {
        foreach ($orders as $order) {
            // Send to vendor
            $this->notificationService->sendOrderNotificationToVendor($order);
            
            // Send to admin
            $this->notificationService->sendOrderNotificationToAdmin($order);
        }
    }
}