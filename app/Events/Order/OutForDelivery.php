<?php

namespace App\Events\Order;

use App\Models\Order;
use App\Models\DeliveryRoute;
use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OutForDelivery implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public ?DeliveryRoute $route;
    public ?Driver $driver;
    public array $trackingData;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Order $order, 
        ?DeliveryRoute $route = null, 
        ?Driver $driver = null,
        array $trackingData = [],
        array $metadata = []
    ) {
        $this->order = $order->load(['buyer', 'vendor', 'delivery']);
        $this->route = $route;
        $this->driver = $driver;
        $this->trackingData = $trackingData;
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'delivery.out_for_delivery',
            'driver_id' => $driver?->id,
            'route_id' => $route?->id,
            'estimated_delivery_time' => $order->estimated_delivery_time?->toIso8601String(),
            'delivery_sequence' => $order->delivery?->sequence_number,
            'total_stops' => $route?->total_stops
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
            new PrivateChannel('delivery.tracking.' . $this->order->delivery?->tracking_number),
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
                'status' => 'out_for_delivery',
                'tracking_number' => $this->order->delivery?->tracking_number
            ],
            'delivery' => [
                'driver' => [
                    'id' => $this->driver?->id,
                    'name' => $this->driver?->name,
                    'phone' => $this->driver?->phone,
                    'vehicle' => $this->driver?->vehicle_details,
                    'photo_url' => $this->driver?->photo_url
                ],
                'tracking' => array_merge([
                    'tracking_url' => $this->generateTrackingUrl(),
                    'current_location' => $this->trackingData['current_location'] ?? null,
                    'estimated_arrival' => $this->order->estimated_delivery_time?->toIso8601String(),
                    'stops_before_delivery' => $this->calculateStopsBefore(),
                    'distance_remaining' => $this->trackingData['distance_remaining'] ?? null
                ], $this->trackingData),
                'route' => [
                    'id' => $this->route?->id,
                    'total_stops' => $this->route?->total_stops,
                    'current_stop' => $this->route?->current_stop,
                    'optimized_path' => $this->route?->optimized_path
                ]
            ],
            'notification' => [
                'title' => 'Order Out for Delivery',
                'message' => "Your order #{$this->order->order_number} is out for delivery. Track your delivery in real-time.",
                'priority' => 'high'
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * Generate tracking URL
     */
    protected function generateTrackingUrl(): string
    {
        return route('delivery.track', [
            'tracking_number' => $this->order->delivery?->tracking_number
        ]);
    }

    /**
     * Calculate stops before this delivery
     */
    protected function calculateStopsBefore(): int
    {
        if (!$this->route || !$this->order->delivery) {
            return 0;
        }

        return max(0, $this->order->delivery->sequence_number - $this->route->current_stop);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'delivery.out_for_delivery';
    }
}