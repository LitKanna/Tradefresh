<?php

namespace App\Events\Order;

use App\Models\Order;
use App\Models\PickupBay;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PickupReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public ?PickupBay $bay;
    public string $pickupCode;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?PickupBay $bay = null, array $metadata = [])
    {
        $this->order = $order->load(['buyer', 'vendor', 'items', 'pickupSlot.bay']);
        $this->bay = $bay ?? $order->pickupSlot?->bay;
        $this->pickupCode = $order->pickup_code ?? $this->generatePickupCode();
        $this->metadata = array_merge([
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'pickup.ready',
            'bay_number' => $this->bay?->bay_number,
            'pickup_window_start' => $order->pickupSlot?->scheduled_time?->toIso8601String(),
            'pickup_window_end' => $order->pickupSlot?->scheduled_time?->addMinutes(30)->toIso8601String(),
            'ready_at' => now()->toIso8601String()
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
            new PrivateChannel('pickup.bay.' . $this->bay?->id),
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
                'status' => 'ready_for_pickup',
                'pickup_code' => $this->pickupCode,
                'qr_code' => $this->order->pickup_qr_code
            ],
            'pickup' => [
                'bay_id' => $this->bay?->id,
                'bay_number' => $this->bay?->bay_number,
                'bay_location' => $this->bay?->location,
                'pickup_instructions' => $this->bay?->pickup_instructions,
                'window_start' => $this->metadata['pickup_window_start'],
                'window_end' => $this->metadata['pickup_window_end'],
                'map_url' => $this->generateMapUrl()
            ],
            'notification' => [
                'title' => 'Order Ready for Pickup',
                'message' => "Your order #{$this->order->order_number} is ready for pickup at Bay {$this->bay?->bay_number}",
                'priority' => 'high'
            ],
            'metadata' => $this->metadata
        ];
    }

    /**
     * Generate pickup code
     */
    protected function generatePickupCode(): string
    {
        return strtoupper(substr(md5($this->order->id . now()->timestamp), 0, 6));
    }

    /**
     * Generate map URL for pickup location
     */
    protected function generateMapUrl(): ?string
    {
        if (!$this->bay || !$this->bay->latitude || !$this->bay->longitude) {
            return null;
        }

        return sprintf(
            'https://maps.google.com/maps?q=%s,%s&z=18',
            $this->bay->latitude,
            $this->bay->longitude
        );
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pickup.ready';
    }
}