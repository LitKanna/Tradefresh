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

class OrderConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public array $metadata;
    public ?array $pickupDetails;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?array $pickupDetails = null, array $metadata = [])
    {
        $this->order = $order->load(['buyer', 'vendor', 'items.product', 'pickupSlot']);
        $this->pickupDetails = $pickupDetails;
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'order.confirmed',
            'confirmed_by' => auth()->user()?->id,
            'pickup_time' => $order->pickupSlot?->scheduled_time,
            'preparation_time' => $order->estimated_preparation_time
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
                'status' => 'confirmed',
                'total_amount' => $this->order->total_amount,
                'confirmed_at' => now()->toIso8601String(),
                'estimated_ready_time' => $this->order->estimated_ready_time?->toIso8601String()
            ],
            'pickup' => $this->pickupDetails ?? [
                'slot_id' => $this->order->pickupSlot?->id,
                'bay_number' => $this->order->pickupSlot?->bay?->bay_number,
                'scheduled_time' => $this->order->pickupSlot?->scheduled_time?->toIso8601String(),
                'qr_code' => $this->order->pickup_qr_code
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.confirmed';
    }
}