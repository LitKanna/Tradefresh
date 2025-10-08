<?php

namespace App\Events;

use App\Models\Quote;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quote;
    public $buyerId;

    /**
     * Create a new event instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote->load(['vendor', 'rfq']);
        $this->buyerId = $quote->buyer_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('buyers.' . $this->buyerId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quote.submitted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'quote' => [
                'id' => $this->quote->id,
                'rfq_id' => $this->quote->rfq_id,
                'vendor_id' => $this->quote->vendor_id,
                'vendor_name' => $this->quote->vendor->business_name ?? 'Unknown Vendor',
                'total_amount' => $this->quote->total_amount,
                'status' => $this->quote->status,
                'validity_date' => $this->quote->validity_date,
                'submitted_at' => $this->quote->submitted_at,
                'message' => 'New quote received from ' . ($this->quote->vendor->business_name ?? 'vendor'),
            ]
        ];
    }
}