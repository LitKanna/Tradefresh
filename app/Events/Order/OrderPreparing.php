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

class OrderPreparing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public int $preparationProgress;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, int $preparationProgress = 0, array $metadata = [])
    {
        $this->order = $order->load(['buyer', 'vendor', 'items.product']);
        $this->preparationProgress = $preparationProgress;
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'order.preparing',
            'preparation_started_at' => $order->preparation_started_at?->toIso8601String(),
            'estimated_completion' => $order->estimated_ready_time?->toIso8601String(),
            'items_prepared' => $order->items->where('status', 'prepared')->count(),
            'total_items' => $order->items->count()
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
                'status' => 'preparing',
                'preparation_progress' => $this->preparationProgress,
                'estimated_ready_time' => $this->order->estimated_ready_time?->toIso8601String()
            ],
            'preparation' => [
                'started_at' => $this->order->preparation_started_at?->toIso8601String(),
                'progress_percentage' => $this->preparationProgress,
                'items_prepared' => $this->metadata['items_prepared'],
                'total_items' => $this->metadata['total_items'],
                'estimated_minutes_remaining' => $this->calculateRemainingMinutes()
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * Calculate remaining preparation time in minutes
     */
    protected function calculateRemainingMinutes(): ?int
    {
        if (!$this->order->estimated_ready_time) {
            return null;
        }

        $remaining = now()->diffInMinutes($this->order->estimated_ready_time, false);
        return max(0, $remaining);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.preparing';
    }
}