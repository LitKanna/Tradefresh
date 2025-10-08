<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quote;
    public $vendor;
    public $buyer;
    public $attachments;

    /**
     * Create a new event instance.
     */
    public function __construct($quote, $vendor, $buyer, $attachments = [])
    {
        $this->quote = $quote;
        $this->vendor = $vendor;
        $this->buyer = $buyer;
        $this->attachments = $attachments;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('buyer.' . $this->buyer->id),
            new PrivateChannel('vendor.' . $this->vendor->id)
        ];
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'quote.received';
    }

    /**
     * Get the data that should be broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'rfq_id' => $this->quote->rfq_id,
            'vendor_name' => $this->vendor->business_name,
            'vendor_id' => $this->vendor->id,
            'total_amount' => $this->quote->final_amount,
            'currency' => 'AUD',
            'delivery_date' => $this->quote->proposed_delivery_date,
            'validity_date' => $this->quote->validity_date,
            'line_items' => $this->quote->line_items ?? [],
            'attachments' => $this->attachments,
            'vendor_message' => $this->quote->vendor_message,
            'message' => "New quote from {$this->vendor->business_name}: \${$this->quote->final_amount}",
            'timestamp' => now()->toISOString(),
            'type' => 'quote_response',
            'can_purchase' => true
        ];
    }
}