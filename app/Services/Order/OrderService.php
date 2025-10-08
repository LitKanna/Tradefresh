<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Buyer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order
     */
    public function createOrder(array $data, Buyer $buyer): Order
    {
        return DB::transaction(function () use ($data, $buyer) {
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'vendor_id' => $data['vendor_id'] ?? null,
                'status' => 'pending',
                'subtotal' => $data['subtotal'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'total' => $data['total'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? $buyer->shipping_address,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['quantity'] * $item['price'],
                    ]);
                }
            }

            return $order->fresh();
        });
    }

    /**
     * Update order status
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);
        return $order;
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order): bool
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return false;
        }

        $order->update(['status' => 'cancelled']);
        return true;
    }

    /**
     * Get orders for a buyer
     */
    public function getBuyerOrders(Buyer $buyer, array $filters = [])
    {
        $query = Order::where('buyer_id', $buyer->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Calculate order totals
     */
    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['price'];
        }

        $tax = $subtotal * 0.1; // 10% GST
        $total = $subtotal + $tax;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ];
    }
}