<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuyerStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $isOnline;
    public int $activeBuyersCount;

    /**
     * Create a new event instance.
     */
    public function __construct(bool $isOnline)
    {
        $this->isOnline = $isOnline;

        // Calculate current active buyers count
        $this->activeBuyersCount = \App\Models\Buyer::where('is_online', true)->count();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to all vendors so they can see updated buyer count
            new Channel('vendors.all'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'buyer.status';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->isOnline ? 'online' : 'offline',
            'activeBuyersCount' => $this->activeBuyersCount,
            'timestamp' => now()->toISOString(),
        ];
    }
}
