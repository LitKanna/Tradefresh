<?php

namespace App\Events\Quote;

use App\Modules\Quote\Models\Quote;
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

    /**
     * Create a new event instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('buyer.' . $this->quote->rfq->buyer_id),
            new PrivateChannel('rfq.' . $this->quote->rfq_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'quote' => [
                'id' => $this->quote->id,
                'reference_number' => $this->quote->reference_number,
                'rfq_id' => $this->quote->rfq_id,
                'vendor_id' => $this->quote->vendor_id,
                'vendor_name' => $this->quote->vendor->business_name ?? $this->quote->vendor->name,
                'total_price' => $this->quote->total_price,
                'delivery_date' => $this->quote->delivery_date?->toDateString(),
                'status' => $this->quote->status,
                'submitted_at' => $this->quote->created_at?->toDateTimeString(),
            ],
            'rfq' => [
                'id' => $this->quote->rfq->id,
                'reference_number' => $this->quote->rfq->reference_number,
                'title' => $this->quote->rfq->title,
                'total_quotes_received' => $this->quote->rfq->quotes()->count(),
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quote.submitted';
    }
}