<?php

namespace App\Events\Quote;

use App\Modules\Quote\Models\Quote;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Quote $quote;
    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Quote $quote, Order $order)
    {
        $this->quote = $quote;
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendor.' . $this->quote->vendor_id),
            new PrivateChannel('buyer.' . $this->quote->rfq->buyer_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'quote_reference' => $this->quote->reference_number,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->quote->total_price,
            'message' => 'Quote ' . $this->quote->reference_number . ' has been accepted.',
            'accepted_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'quote.accepted';
    }
}