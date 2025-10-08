<?php

namespace App\Events\Order;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, array $metadata = [])
    {
        $this->order = $order->load(['buyer', 'vendor', 'items.product']);
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'order.submitted',
            'order_value' => $order->total_amount,
            'item_count' => $order->items->count(),
            'buyer_name' => $order->buyer->business_name,
            'vendor_name' => $order->vendor->business_name
        ], $metadata);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.' . $this->order->id),
            new PrivateChannel('vendor.' . $this->order->vendor_id),
            new PrivateChannel('buyer.' . $this->order->buyer_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'total_amount' => $this->order->total_amount,
                'item_count' => $this->order->items->count(),
                'created_at' => $this->order->created_at->toIso8601String(),
            ],
            'buyer' => [
                'id' => $this->order->buyer->id,
                'business_name' => $this->order->buyer->business_name,
                'abn' => $this->order->buyer->abn
            ],
            'vendor' => [
                'id' => $this->order->vendor->id,
                'business_name' => $this->order->vendor->business_name
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.submitted';
    }
}