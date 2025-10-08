<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $product,
        public float $oldPrice,
        public float $newPrice,
        public int $vendorId,
        public string $vendorName
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('market.prices'), // Public channel for all users
        ];
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'price.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $changePercent = round((($this->newPrice - $this->oldPrice) / $this->oldPrice) * 100, 2);

        return [
            'product' => $this->product,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'change' => $this->newPrice - $this->oldPrice,
            'change_percent' => $changePercent,
            'direction' => $this->newPrice > $this->oldPrice ? 'up' : 'down',
            'timestamp' => now()->toISOString(),
            'alert' => abs($changePercent) > 10 ? 'significant' : 'normal',
        ];
    }
}