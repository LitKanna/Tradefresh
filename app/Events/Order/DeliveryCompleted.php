<?php

namespace App\Events\Order;

use App\Models\Order;
use App\Models\DeliveryProof;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public ?DeliveryProof $proof;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?DeliveryProof $proof = null, array $metadata = [])
    {
        $this->order = $order->load(['buyer', 'vendor', 'delivery', 'items']);
        $this->proof = $proof;
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'delivery.completed',
            'delivered_at' => $order->delivery?->delivered_at?->toIso8601String(),
            'delivery_time' => $this->calculateDeliveryTime(),
            'recipient_name' => $proof?->recipient_name,
            'signature_captured' => $proof?->signature_url !== null,
            'photo_captured' => $proof?->photo_url !== null
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
                'status' => 'delivered',
                'delivered_at' => $this->order->delivery?->delivered_at?->toIso8601String(),
                'total_amount' => $this->order->total_amount
            ],
            'delivery' => [
                'tracking_number' => $this->order->delivery?->tracking_number,
                'delivered_to' => $this->proof?->recipient_name,
                'delivery_location' => $this->proof?->delivery_location,
                'delivery_notes' => $this->proof?->notes,
                'proof' => [
                    'signature_url' => $this->proof?->signature_url,
                    'photo_url' => $this->proof?->photo_url,
                    'timestamp' => $this->proof?->created_at?->toIso8601String()
                ]
            ],
            'performance' => [
                'delivery_time_minutes' => $this->calculateDeliveryTime(),
                'on_time' => $this->wasDeliveredOnTime(),
                'customer_notified' => true
            ],
            'notification' => [
                'title' => 'Delivery Completed',
                'message' => "Your order #{$this->order->order_number} has been successfully delivered",
                'priority' => 'normal'
            ],
            'feedback' => [
                'request_feedback' => true,
                'feedback_url' => $this->generateFeedbackUrl()
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * Calculate total delivery time in minutes
     */
    protected function calculateDeliveryTime(): ?int
    {
        if (!$this->order->delivery?->picked_up_at || !$this->order->delivery?->delivered_at) {
            return null;
        }

        return $this->order->delivery->picked_up_at->diffInMinutes($this->order->delivery->delivered_at);
    }

    /**
     * Check if delivery was on time
     */
    protected function wasDeliveredOnTime(): bool
    {
        if (!$this->order->estimated_delivery_time || !$this->order->delivery?->delivered_at) {
            return true;
        }

        return $this->order->delivery->delivered_at <= $this->order->estimated_delivery_time;
    }

    /**
     * Generate feedback URL
     */
    protected function generateFeedbackUrl(): string
    {
        return route('order.feedback', [
            'order' => $this->order->id,
            'token' => encrypt($this->order->id . '|' . $this->order->buyer_id)
        ]);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'delivery.completed';
    }
}