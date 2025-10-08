<?php

namespace App\Services;

use App\Repositories\QuoteRepository;
use App\Repositories\OrderRepository;
use App\Events\OrderCreated;
use App\Events\QuoteAccepted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\BusinessException;

/**
 * OrderService - Manages order creation and processing
 *
 * This service demonstrates:
 * - Transaction management for data consistency
 * - Event-driven architecture
 * - Error handling and logging
 * - Business rule validation
 * - Performance optimization through caching
 */
class OrderService
{
    protected $quoteRepository;
    protected $orderRepository;

    public function __construct(
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository ?: new OrderRepository();
    }

    /**
     * Create order from accepted quote
     * This is the main business transaction
     */
    public function createOrderFromQuote(int $quoteId, int $buyerId): array
    {
        return DB::transaction(function() use ($quoteId, $buyerId) {
            // Step 1: Validate quote
            $quote = $this->validateQuoteForOrder($quoteId, $buyerId);

            // Step 2: Accept the quote
            $this->quoteRepository->updateStatus($quoteId, 'accepted');

            // Step 3: Reject other quotes for same RFQ
            $this->quoteRepository->rejectOtherQuotesForRfq($quote->rfq_id, $quoteId);

            // Step 4: Close the RFQ
            $this->closeRfq($quote->rfq_id, $quoteId);

            // Step 5: Create the order
            $order = $this->createOrder($quote, $buyerId);

            // Step 6: Create order items
            $this->createOrderItems($order['id'], $quoteId);

            // Step 7: Fire events for other systems
            event(new QuoteAccepted($quote, $order));
            event(new OrderCreated($order));

            // Step 8: Clear caches
            $this->clearRelevantCaches($buyerId, $quote->vendor_id);

            // Step 9: Log success
            Log::info('Order created from quote', [
                'order_id' => $order['id'],
                'quote_id' => $quoteId,
                'buyer_id' => $buyerId,
                'amount' => $quote->total_amount
            ]);

            return $order;
        });
    }

    /**
     * Validate quote can be converted to order
     */
    protected function validateQuoteForOrder(int $quoteId, int $buyerId)
    {
        $quote = $this->quoteRepository->findWithDetails($quoteId, $buyerId);

        if (!$quote) {
            throw new BusinessException('Quote not found or access denied');
        }

        if ($quote->status !== 'pending') {
            throw new BusinessException('Quote is no longer available. Status: ' . $quote->status);
        }

        if ($quote->valid_until < now()) {
            throw new BusinessException('Quote has expired');
        }

        if ($quote->buyer_id !== $buyerId) {
            throw new BusinessException('Unauthorized access to quote');
        }

        return $quote;
    }

    /**
     * Close RFQ after quote acceptance
     */
    protected function closeRfq(int $rfqId, int $winningQuoteId)
    {
        DB::table('rfqs')
            ->where('id', $rfqId)
            ->update([
                'status' => 'closed',
                'winning_quote_id' => $winningQuoteId,
                'closed_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Create order record
     */
    protected function createOrder($quote, int $buyerId): array
    {
        $orderNumber = $this->generateOrderNumber();

        $orderId = DB::table('orders')->insertGetId([
            'order_number' => $orderNumber,
            'buyer_id' => $buyerId,
            'vendor_id' => $quote->vendor_id,
            'rfq_id' => $quote->rfq_id,
            'quote_id' => $quote->id,
            'total_amount' => $quote->total_amount,
            'tax_amount' => $quote->tax_amount ?? 0,
            'shipping_amount' => $quote->shipping_amount ?? 0,
            'discount_amount' => $quote->discount_amount ?? 0,
            'status' => 'pending',
            'payment_status' => 'pending',
            'delivery_date' => $quote->delivery_date,
            'delivery_address' => $quote->delivery_address,
            'payment_terms' => $quote->payment_terms,
            'notes' => $quote->notes,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $quote->total_amount,
            'vendor_id' => $quote->vendor_id,
            'buyer_id' => $buyerId,
            'status' => 'pending'
        ];
    }

    /**
     * Create order items from quote items
     */
    protected function createOrderItems(int $orderId, int $quoteId)
    {
        $quoteItems = DB::table('quote_items')
            ->where('quote_id', $quoteId)
            ->get();

        if ($quoteItems->isEmpty()) {
            // If no quote items exist, create a single line item
            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => null,
                'description' => 'Items as per quote',
                'quantity' => 1,
                'unit_price' => DB::table('quotes')->where('id', $quoteId)->value('total_amount'),
                'total_price' => DB::table('quotes')->where('id', $quoteId)->value('total_amount'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return;
        }

        foreach ($quoteItems as $item) {
            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'specifications' => $item->specifications,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        $orderNumber = "{$prefix}-{$date}-{$random}";

        // Ensure uniqueness
        while (DB::table('orders')->where('order_number', $orderNumber)->exists()) {
            $random = strtoupper(substr(uniqid(), -6));
            $orderNumber = "{$prefix}-{$date}-{$random}";
        }

        return $orderNumber;
    }

    /**
     * Clear relevant caches after order creation
     */
    protected function clearRelevantCaches(int $buyerId, int $vendorId)
    {
        // Clear buyer caches
        Cache::forget("quotes:buyer:{$buyerId}:pending");
        Cache::forget("quotes:buyer:{$buyerId}:stats");
        Cache::forget("orders:buyer:{$buyerId}:recent");
        Cache::forget("dashboard:buyer:{$buyerId}");

        // Clear vendor caches
        Cache::forget("quotes:vendor:{$vendorId}:pending");
        Cache::forget("orders:vendor:{$vendorId}:new");

        // Clear general caches
        Cache::forget("metrics:orders:today");
        Cache::forget("metrics:revenue:today");
    }

    /**
     * Get order details
     */
    public function getOrderDetails(int $orderId, int $buyerId = null)
    {
        $query = DB::table('orders')
            ->leftJoin('vendors', 'orders.vendor_id', '=', 'vendors.id')
            ->leftJoin('quotes', 'orders.quote_id', '=', 'quotes.id')
            ->leftJoin('rfqs', 'orders.rfq_id', '=', 'rfqs.id')
            ->where('orders.id', $orderId);

        if ($buyerId) {
            $query->where('orders.buyer_id', $buyerId);
        }

        $order = $query->select(
            'orders.*',
            'vendors.name as vendor_name',
            'vendors.email as vendor_email',
            'vendors.phone as vendor_phone',
            'quotes.total_amount as quote_amount',
            'rfqs.title as rfq_title'
        )->first();

        if ($order) {
            $order->items = DB::table('order_items')
                ->where('order_id', $orderId)
                ->get();
        }

        return $order;
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, int $buyerId, string $reason)
    {
        $order = DB::table('orders')
            ->where('id', $orderId)
            ->where('buyer_id', $buyerId)
            ->first();

        if (!$order) {
            throw new BusinessException('Order not found');
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new BusinessException('Order cannot be cancelled in current status');
        }

        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'updated_at' => now()
            ]);

        // Reopen the quote if needed
        if ($order->quote_id) {
            DB::table('quotes')
                ->where('id', $order->quote_id)
                ->update([
                    'status' => 'pending',
                    'updated_at' => now()
                ]);
        }

        // Clear caches
        $this->clearRelevantCaches($buyerId, $order->vendor_id);

        Log::info('Order cancelled', [
            'order_id' => $orderId,
            'buyer_id' => $buyerId,
            'reason' => $reason
        ]);

        return true;
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics(int $buyerId)
    {
        $cacheKey = "orders:buyer:{$buyerId}:statistics";

        return Cache::remember($cacheKey, 300, function() use ($buyerId) {
            return [
                'total_orders' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->count(),
                'pending_orders' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->where('status', 'pending')
                    ->count(),
                'completed_orders' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->where('status', 'completed')
                    ->count(),
                'total_spent' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                'average_order_value' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->where('status', '!=', 'cancelled')
                    ->avg('total_amount'),
                'this_month_orders' => DB::table('orders')
                    ->where('buyer_id', $buyerId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count()
            ];
        });
    }
}