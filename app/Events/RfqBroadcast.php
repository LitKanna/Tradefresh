<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RfqBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rfq;
    public $matchedVendors;
    public $buyer;

    /**
     * Create a new event instance.
     */
    public function __construct($rfq, $matchedVendors, $buyer)
    {
        $this->rfq = $rfq;
        $this->matchedVendors = $matchedVendors;
        $this->buyer = $buyer;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Send to buyer's private channel
        $channels[] = new PrivateChannel('buyer.' . $this->buyer->id);
        
        // Send to each matched vendor's private channel
        foreach ($this->matchedVendors as $vendor) {
            $channels[] = new PrivateChannel('vendor.' . $vendor->id);
        }
        
        return $channels;
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'rfq.broadcast';
    }

    /**
     * Get the data that should be broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'rfq_id' => $this->rfq->id,
            'buyer_name' => $this->buyer->business_name,
            'products_requested' => $this->rfq->items ?? [],
            'delivery_date' => $this->rfq->required_by,
            'message' => "New RFQ: {$this->buyer->business_name} is requesting quotes",
            'match_score' => 1.0,
            'timestamp' => now()->toISOString(),
            'type' => 'rfq_request'
        ];
    }
}