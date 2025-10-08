<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Business;
use App\Models\PickupBooking;
use App\Models\DeliveryRoute;
use App\Services\Cart\ShoppingCartService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Inventory\StockManagementService;
use App\Services\Delivery\RouteOptimizationService;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\OrderConfirmed;
use App\Events\OrderReadyForPickup;
use App\Events\OrderDelivered;
use App\Notifications\OrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class OrderProcessingService
{
    protected ShoppingCartService $cartService;
    protected PaymentGatewayService $paymentService;
    protected StockManagementService $stockService;
    protected RouteOptimizationService $routeService;

    public function __construct(
        ShoppingCartService $cartService,
        PaymentGatewayService $paymentService,
        StockManagementService $stockService,
        RouteOptimizationService $routeService
    ) {
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->stockService = $stockService;
        $this->routeService = $routeService;
    }

    /**
     * Create order from cart with full validation and processing
     */
    public function createOrderFromCart(int $buyerId, array $options = []): Order
    {
        return DB::transaction(function () use ($buyerId, $options) {
            try {
                // Get cart items
                $cartItems = $this->cartService->getCartItems($buyerId);
                
                if ($cartItems->isEmpty()) {
                    throw new Exception('Cart is empty');
                }

                // Group items by vendor
                $itemsByVendor = $cartItems->groupBy('vendor_id');
                $orders = collect();

                foreach ($itemsByVendor as $vendorId => $vendorItems) {
                    // Validate minimum order requirements
                    $this->validateMinimumOrder($vendorId, $vendorItems);

                    // Check stock availability
                    $stockValidation = $this->validateStockAvailability($vendorItems);
                    
                    if (!$stockValidation['available']) {
                        // Handle backorders if enabled
                        if ($options['allow_backorder'] ?? false) {
                            $this->processBackorders($stockValidation['unavailable_items']);
                        } else {
                            throw new Exception('Some items are out of stock: ' . 
                                implode(', ', $stockValidation['unavailable_items']->pluck('product_name')->toArray()));
                        }
                    }

                    // Create order
                    $order = $this->createOrder($buyerId, $vendorId, $vendorItems, $options);
                    
                    // Reserve stock
                    $this->reserveOrderStock($order);
                    
                    // Apply business-specific pricing
                    $this->applyBusinessPricing($order);
                    
                    // Calculate delivery fees
                    if ($options['delivery_type'] === 'delivery') {
                        $this->calculateDeliveryFees($order, $options['delivery_address'] ?? null);
                    }
                    
                    // Process payment if required
                    if ($options['process_payment'] ?? false) {
                        $this->processOrderPayment($order, $options['payment_method'] ?? 'credit_terms');
                    }
                    
                    // Schedule pickup or delivery
                    if ($options['delivery_type'] === 'pickup') {
                        $this->schedulePickup($order, $options['pickup_slot'] ?? null);
                    } else {
                        $this->scheduleDelivery($order, $options['delivery_date'] ?? null);
                    }
                    
                    $orders->push($order);
                }

                // Clear cart after successful order creation
                $this->cartService->clearCart($buyerId);

                // Send notifications
                $this->sendOrderNotifications($orders);

                // Return single order or collection based on vendor count
                return $orders->count() === 1 ? $orders->first() : $orders;

            } catch (Exception $e) {
                Log::error('Order creation failed: ' . $e->getMessage(), [
                    'buyer_id' => $buyerId,
                    'options' => $options,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create a single order
     */
    protected function createOrder(int $buyerId, int $vendorId, Collection $items, array $options): Order
    {
        $buyer = Buyer::findOrFail($buyerId);
        $vendor = Vendor::findOrFail($vendorId);
        
        // Calculate totals
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $taxAmount = $subtotal * 0.10; // 10% GST
        $shippingAmount = $options['shipping_amount'] ?? 0;
        $discountAmount = $this->calculateDiscounts($buyer, $vendor, $subtotal);
        $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

        // Create order
        $order = Order::create([
            'buyer_id' => $buyerId,
            'vendor_id' => $vendorId,
            'order_number' => Order::generateOrderNumber(),
            'status' => OrderStatus::STATUS_DRAFT,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => $options['currency'] ?? 'AUD',
            'payment_method' => $options['payment_method'] ?? 'credit_terms',
            'payment_terms' => $vendor->payment_terms ?? 'net30',
            'delivery_address' => $options['delivery_address'] ?? $buyer->default_delivery_address,
            'billing_address' => $options['billing_address'] ?? $buyer->billing_address,
            'delivery_notes' => $options['delivery_notes'] ?? null,
            'expected_delivery' => $this->calculateExpectedDelivery($vendor, $options),
            'is_urgent' => $options['is_urgent'] ?? false,
            'source' => $options['source'] ?? 'web',
            'requires_approval' => $this->requiresApproval($buyer, $totalAmount),
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'platform' => $options['platform'] ?? 'web',
                'business_abn' => $buyer->business->abn ?? null,
                'vendor_abn' => $vendor->business->abn ?? null
            ]
        ]);

        // Create order items
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'quantity' => $item->quantity,
                'unit' => $item->product->unit,
                'unit_price' => $item->unit_price,
                'original_price' => $item->product->price,
                'discount_amount' => $item->discount_amount ?? 0,
                'tax_rate' => 0.10,
                'specifications' => $item->specifications ?? [],
                'notes' => $item->notes ?? null,
                'batch_number' => $item->product->batch_number ?? null,
                'expiry_date' => $item->product->expiry_date ?? null,
                'location_in_warehouse' => $item->product->warehouse_location ?? null
            ]);
        }

        // Update order status to submitted
        $this->updateOrderStatus($order, OrderStatus::STATUS_SUBMITTED);

        // Fire event
        event(new OrderCreated($order));

        return $order->fresh(['items', 'buyer', 'vendor']);
    }

    /**
     * Update order status with validation
     */
    public function updateOrderStatus(Order $order, string $newStatus, array $metadata = []): bool
    {
        // Validate status transition
        if (!OrderStatus::canTransitionTo($order->status, $newStatus)) {
            throw new Exception("Cannot transition from {$order->status} to {$newStatus}");
        }

        $oldStatus = $order->status;
        
        // Update order
        $order->update([
            'status' => $newStatus,
            'metadata' => array_merge($order->metadata ?? [], $metadata)
        ]);

        // Record status change
        $order->recordStatusChange($oldStatus, $newStatus, $metadata['comment'] ?? null);

        // Handle status-specific actions
        $this->handleStatusActions($order, $newStatus);

        // Fire event
        event(new OrderStatusChanged($order, $oldStatus, $newStatus));

        return true;
    }

    /**
     * Handle actions for specific status changes
     */
    protected function handleStatusActions(Order $order, string $status): void
    {
        switch ($status) {
            case OrderStatus::STATUS_CONFIRMED:
                $this->handleOrderConfirmation($order);
                break;
                
            case OrderStatus::STATUS_PREPARING:
                $this->handleOrderPreparation($order);
                break;
                
            case OrderStatus::STATUS_READY_FOR_PICKUP:
                $this->handleReadyForPickup($order);
                break;
                
            case OrderStatus::STATUS_IN_TRANSIT:
                $this->handleInTransit($order);
                break;
                
            case OrderStatus::STATUS_DELIVERED:
                $this->handleDelivered($order);
                break;
                
            case OrderStatus::STATUS_CANCELLED:
                $this->handleCancellation($order);
                break;
        }
    }

    /**
     * Handle order confirmation
     */
    protected function handleOrderConfirmation(Order $order): void
    {
        // Confirm stock reservation
        $this->stockService->confirmReservation($order->id);
        
        // Generate invoice
        $order->createInvoice();
        
        // Send confirmation to vendor
        $order->vendor->notify(new OrderNotification($order, 'confirmed'));
        
        // Send confirmation to buyer
        $order->buyer->notify(new OrderNotification($order, 'confirmed'));
        
        // Set preparation timer
        Cache::put("order_prep_{$order->id}", now()->addMinutes(30), 3600);
        
        event(new OrderConfirmed($order));
    }

    /**
     * Handle ready for pickup status
     */
    protected function handleReadyForPickup(Order $order): void
    {
        // Notify driver/buyer
        if ($order->pickup_booking_id) {
            $booking = PickupBooking::find($order->pickup_booking_id);
            if ($booking) {
                $booking->update(['status' => 'ready']);
                // Send SMS/Push notification
                $this->sendPickupReadyNotification($order, $booking);
            }
        }
        
        // Update warehouse location
        $this->updateWarehouseStatus($order, 'ready_bay');
        
        event(new OrderReadyForPickup($order));
    }

    /**
     * Handle delivered status
     */
    protected function handleDelivered(Order $order): void
    {
        // Update delivery confirmation
        $order->update([
            'delivered_at' => now(),
            'delivery_confirmed_by' => auth()->id()
        ]);
        
        // Release any remaining reserved stock
        $this->stockService->releaseReservation($order->id);
        
        // Update payment status if COD
        if ($order->payment_method === 'cod') {
            $this->paymentService->markAsPaid($order);
        }
        
        // Request rating
        $this->requestOrderRating($order);
        
        event(new OrderDelivered($order));
    }

    /**
     * Handle order cancellation
     */
    protected function handleCancellation(Order $order): void
    {
        // Release reserved stock
        $this->stockService->releaseAllReservations($order->id);
        
        // Cancel any scheduled pickups/deliveries
        if ($order->pickup_booking_id) {
            PickupBooking::find($order->pickup_booking_id)?->cancel();
        }
        
        if ($order->delivery_route_id) {
            $this->routeService->removeOrderFromRoute($order->id, $order->delivery_route_id);
        }
        
        // Process refund if paid
        if ($order->isPaid()) {
            $this->paymentService->processRefund($order);
        }
        
        // Notify parties
        $order->vendor->notify(new OrderNotification($order, 'cancelled'));
        $order->buyer->notify(new OrderNotification($order, 'cancelled'));
    }

    /**
     * Validate minimum order requirements
     */
    protected function validateMinimumOrder(int $vendorId, Collection $items): bool
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return true;
        }

        $orderTotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        if ($vendor->minimum_order_value && $orderTotal < $vendor->minimum_order_value) {
            throw new Exception("Minimum order value for {$vendor->business_name} is \${$vendor->minimum_order_value}");
        }

        // Check minimum quantity per product
        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            if ($product && $product->minimum_order_quantity) {
                if ($item->quantity < $product->minimum_order_quantity) {
                    throw new Exception("Minimum order quantity for {$product->name} is {$product->minimum_order_quantity} {$product->unit}");
                }
            }
        }

        return true;
    }

    /**
     * Validate stock availability
     */
    protected function validateStockAvailability(Collection $items): array
    {
        $unavailableItems = collect();
        $partialItems = collect();

        foreach ($items as $item) {
            $availableStock = $this->stockService->getAvailableStock($item->product_id);
            
            if ($availableStock <= 0) {
                $unavailableItems->push($item);
            } elseif ($availableStock < $item->quantity) {
                $item->available_quantity = $availableStock;
                $partialItems->push($item);
            }
        }

        return [
            'available' => $unavailableItems->isEmpty(),
            'unavailable_items' => $unavailableItems,
            'partial_items' => $partialItems
        ];
    }

    /**
     * Reserve stock for order
     */
    protected function reserveOrderStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $this->stockService->reserveStock(
                $item->product_id,
                $item->quantity,
                $order->id,
                $order->expected_delivery
            );
        }
    }

    /**
     * Apply business-specific pricing
     */
    protected function applyBusinessPricing(Order $order): void
    {
        $buyer = $order->buyer;
        $business = $buyer->business;
        
        if (!$business) {
            return;
        }

        // Check for contract pricing
        $contractPricing = DB::table('business_vendor_contracts')
            ->where('business_id', $business->id)
            ->where('vendor_id', $order->vendor_id)
            ->where('active', true)
            ->first();

        if ($contractPricing) {
            $discountRate = $contractPricing->discount_rate ?? 0;
            $order->discount_amount = $order->subtotal * $discountRate;
            $order->total_amount = $order->subtotal + $order->tax_amount + $order->shipping_amount - $order->discount_amount;
            $order->save();
        }

        // Apply volume discounts
        $this->applyVolumeDiscounts($order);
    }

    /**
     * Apply volume-based discounts
     */
    protected function applyVolumeDiscounts(Order $order): void
    {
        $monthlyVolume = Order::where('buyer_id', $order->buyer_id)
            ->where('vendor_id', $order->vendor_id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_amount');

        $volumeTiers = [
            50000 => 0.05,  // 5% discount for $50k+ monthly
            100000 => 0.08, // 8% discount for $100k+ monthly
            250000 => 0.12, // 12% discount for $250k+ monthly
        ];

        foreach ($volumeTiers as $threshold => $discountRate) {
            if ($monthlyVolume >= $threshold) {
                $additionalDiscount = $order->subtotal * $discountRate;
                $order->discount_amount += $additionalDiscount;
                $order->metadata = array_merge($order->metadata ?? [], [
                    'volume_discount' => $discountRate,
                    'volume_discount_amount' => $additionalDiscount
                ]);
            }
        }

        $order->total_amount = $order->subtotal + $order->tax_amount + $order->shipping_amount - $order->discount_amount;
        $order->save();
    }

    /**
     * Calculate delivery fees based on zones
     */
    protected function calculateDeliveryFees(Order $order, ?array $deliveryAddress): void
    {
        if (!$deliveryAddress) {
            $deliveryAddress = $order->delivery_address;
        }

        $deliveryFee = $this->routeService->calculateDeliveryFee(
            $order->vendor->warehouse_address,
            $deliveryAddress,
            $order->total_weight ?? 0,
            $order->is_urgent
        );

        $order->update([
            'shipping_amount' => $deliveryFee,
            'total_amount' => $order->subtotal + $order->tax_amount + $deliveryFee - $order->discount_amount
        ]);
    }

    /**
     * Schedule pickup for order
     */
    protected function schedulePickup(Order $order, ?array $pickupSlot): void
    {
        if (!$pickupSlot) {
            // Find next available slot
            $pickupSlot = $this->findNextAvailablePickupSlot($order->vendor_id);
        }

        $booking = PickupBooking::create([
            'order_id' => $order->id,
            'vendor_id' => $order->vendor_id,
            'buyer_id' => $order->buyer_id,
            'pickup_date' => $pickupSlot['date'],
            'pickup_time_slot' => $pickupSlot['time_slot'],
            'pickup_bay' => $pickupSlot['bay'],
            'status' => 'scheduled',
            'confirmation_code' => strtoupper(uniqid('PU')),
            'metadata' => [
                'estimated_duration' => $pickupSlot['duration'] ?? 15,
                'special_instructions' => $order->delivery_notes
            ]
        ]);

        $order->update([
            'pickup_booking_id' => $booking->id,
            'expected_delivery' => Carbon::parse($pickupSlot['date'] . ' ' . $pickupSlot['time_slot'])
        ]);
    }

    /**
     * Schedule delivery for order
     */
    protected function scheduleDelivery(Order $order, ?Carbon $deliveryDate): void
    {
        if (!$deliveryDate) {
            $deliveryDate = now()->addDays($order->vendor->standard_delivery_days ?? 2);
        }

        // Find or create route
        $route = $this->routeService->assignOrderToRoute(
            $order,
            $deliveryDate,
            $order->is_urgent ? 'express' : 'standard'
        );

        $order->update([
            'delivery_route_id' => $route->id,
            'expected_delivery' => $deliveryDate
        ]);
    }

    /**
     * Process order payment
     */
    protected function processOrderPayment(Order $order, string $paymentMethod): void
    {
        try {
            $result = $this->paymentService->processPayment($order, $paymentMethod);
            
            if ($result['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $result['transaction_id']
                ]);
            } else {
                // Handle payment failure
                Log::warning('Payment failed for order ' . $order->order_number, $result);
                
                if ($paymentMethod === 'credit_terms') {
                    // Allow order to proceed with credit terms
                    $order->update([
                        'payment_status' => 'pending',
                        'payment_due_date' => now()->addDays(30)
                    ]);
                } else {
                    throw new Exception('Payment processing failed: ' . $result['message']);
                }
            }
        } catch (Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate expected delivery date
     */
    protected function calculateExpectedDelivery(Vendor $vendor, array $options): Carbon
    {
        if (isset($options['expected_delivery'])) {
            return Carbon::parse($options['expected_delivery']);
        }

        $baseDeliveryDays = $vendor->standard_delivery_days ?? 2;
        
        if ($options['is_urgent'] ?? false) {
            $baseDeliveryDays = max(1, $baseDeliveryDays - 1);
        }

        // Skip weekends
        $deliveryDate = now();
        $daysAdded = 0;
        
        while ($daysAdded < $baseDeliveryDays) {
            $deliveryDate->addDay();
            if (!$deliveryDate->isWeekend()) {
                $daysAdded++;
            }
        }

        return $deliveryDate;
    }

    /**
     * Check if order requires approval
     */
    protected function requiresApproval(Buyer $buyer, float $totalAmount): bool
    {
        // Check buyer's approval limit
        if ($buyer->approval_limit && $totalAmount > $buyer->approval_limit) {
            return true;
        }

        // Check business approval rules
        if ($buyer->business) {
            $approvalRules = $buyer->business->approval_rules ?? [];
            
            if (isset($approvalRules['max_order_amount']) && $totalAmount > $approvalRules['max_order_amount']) {
                return true;
            }

            if (isset($approvalRules['require_approval_for_new_vendors']) && $approvalRules['require_approval_for_new_vendors']) {
                // Check if this is first order with vendor
                $previousOrders = Order::where('buyer_id', $buyer->id)
                    ->where('vendor_id', $buyer->vendor_id)
                    ->where('status', '!=', 'cancelled')
                    ->count();
                    
                if ($previousOrders === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculate discounts
     */
    protected function calculateDiscounts(Buyer $buyer, Vendor $vendor, float $subtotal): float
    {
        $discountAmount = 0;

        // Early payment discount
        if ($buyer->payment_method === 'immediate') {
            $discountAmount += $subtotal * 0.02; // 2% for immediate payment
        }

        // Loyalty discount
        $orderCount = Order::where('buyer_id', $buyer->id)
            ->where('vendor_id', $vendor->id)
            ->where('status', 'completed')
            ->count();
            
        if ($orderCount >= 50) {
            $discountAmount += $subtotal * 0.03; // 3% for 50+ orders
        } elseif ($orderCount >= 20) {
            $discountAmount += $subtotal * 0.02; // 2% for 20+ orders
        } elseif ($orderCount >= 10) {
            $discountAmount += $subtotal * 0.01; // 1% for 10+ orders
        }

        return $discountAmount;
    }

    /**
     * Find next available pickup slot
     */
    protected function findNextAvailablePickupSlot(int $vendorId): array
    {
        $vendor = Vendor::find($vendorId);
        $pickupHours = $vendor->pickup_hours ?? ['start' => '06:00', 'end' => '14:00'];
        
        // Check next 7 days
        for ($days = 0; $days < 7; $days++) {
            $date = now()->addDays($days);
            
            if ($date->isWeekend()) {
                continue;
            }

            // Check available time slots
            $slots = $this->generateTimeSlots($pickupHours['start'], $pickupHours['end'], 30);
            
            foreach ($slots as $slot) {
                $bookingCount = PickupBooking::where('vendor_id', $vendorId)
                    ->where('pickup_date', $date->format('Y-m-d'))
                    ->where('pickup_time_slot', $slot)
                    ->count();
                    
                if ($bookingCount < ($vendor->max_concurrent_pickups ?? 3)) {
                    return [
                        'date' => $date->format('Y-m-d'),
                        'time_slot' => $slot,
                        'bay' => $this->assignPickupBay($vendorId, $date, $slot),
                        'duration' => 15
                    ];
                }
            }
        }

        throw new Exception('No pickup slots available in the next 7 days');
    }

    /**
     * Generate time slots
     */
    protected function generateTimeSlots(string $start, string $end, int $intervalMinutes): array
    {
        $slots = [];
        $current = Carbon::parse($start);
        $endTime = Carbon::parse($end);
        
        while ($current->lt($endTime)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($intervalMinutes);
        }
        
        return $slots;
    }

    /**
     * Assign pickup bay
     */
    protected function assignPickupBay(int $vendorId, Carbon $date, string $timeSlot): string
    {
        // Get available bays for vendor
        $vendor = Vendor::find($vendorId);
        $availableBays = $vendor->pickup_bays ?? ['A1', 'A2', 'A3', 'B1', 'B2', 'B3'];
        
        // Find occupied bays
        $occupiedBays = PickupBooking::where('vendor_id', $vendorId)
            ->where('pickup_date', $date->format('Y-m-d'))
            ->where('pickup_time_slot', $timeSlot)
            ->pluck('pickup_bay')
            ->toArray();
            
        $freeBays = array_diff($availableBays, $occupiedBays);
        
        if (empty($freeBays)) {
            throw new Exception('No pickup bays available for selected time slot');
        }
        
        return array_values($freeBays)[0];
    }

    /**
     * Send order notifications
     */
    protected function sendOrderNotifications($orders): void
    {
        $ordersCollection = $orders instanceof Collection ? $orders : collect([$orders]);
        
        foreach ($ordersCollection as $order) {
            // Notify vendor
            $order->vendor->notify(new OrderNotification($order, 'new_order'));
            
            // Notify buyer
            $order->buyer->notify(new OrderNotification($order, 'order_placed'));
            
            // Notify admins if high value
            if ($order->total_amount > 10000) {
                $this->notifyAdmins($order, 'high_value_order');
            }
        }
    }

    /**
     * Process backorders
     */
    protected function processBackorders(Collection $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            
            if ($product) {
                DB::table('backorder_items')->insert([
                    'product_id' => $product->id,
                    'quantity_requested' => $item->quantity,
                    'buyer_id' => $item->buyer_id,
                    'expected_availability' => $product->next_restock_date,
                    'priority' => $item->is_urgent ? 'high' : 'normal',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Update warehouse status
     */
    protected function updateWarehouseStatus(Order $order, string $status): void
    {
        foreach ($order->items as $item) {
            DB::table('warehouse_items')
                ->where('product_id', $item->product_id)
                ->where('order_id', $order->id)
                ->update([
                    'status' => $status,
                    'location' => $status === 'ready_bay' ? $order->pickup_bay : null,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Send pickup ready notification
     */
    protected function sendPickupReadyNotification(Order $order, PickupBooking $booking): void
    {
        $message = "Your order {$order->order_number} is ready for pickup. ";
        $message .= "Bay: {$booking->pickup_bay}, Code: {$booking->confirmation_code}";
        
        // Send SMS
        if ($order->buyer->mobile_number) {
            // SMS service implementation
        }
        
        // Send push notification
        if ($order->buyer->device_token) {
            // Push notification service implementation
        }
        
        // Send email
        $order->buyer->notify(new OrderNotification($order, 'ready_for_pickup', [
            'bay' => $booking->pickup_bay,
            'code' => $booking->confirmation_code
        ]));
    }

    /**
     * Request order rating
     */
    protected function requestOrderRating(Order $order): void
    {
        // Schedule rating request for 24 hours after delivery
        dispatch(function () use ($order) {
            $order->buyer->notify(new OrderNotification($order, 'request_rating'));
        })->delay(now()->addDay());
    }

    /**
     * Notify admins
     */
    protected function notifyAdmins(Order $order, string $type): void
    {
        $admins = DB::table('users')
            ->where('role', 'admin')
            ->where('notifications_enabled', true)
            ->get();
            
        foreach ($admins as $admin) {
            // Send notification based on type
        }
    }
}