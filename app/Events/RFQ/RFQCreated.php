<?php

namespace App\Events\RFQ;

use App\Modules\RFQ\Models\RFQ;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RFQCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rfq;

    /**
     * Create a new event instance.
     */
    public function __construct(RFQ $rfq)
    {
        $this->rfq = $rfq;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('rfqs'),
            new PrivateChannel('buyer.' . $this->rfq->buyer_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'rfq' => [
                'id' => $this->rfq->id,
                'reference_number' => $this->rfq->reference_number,
                'title' => $this->rfq->title,
                'status' => $this->rfq->status,
                'buyer_name' => $this->rfq->buyer->business_name ?? $this->rfq->buyer->name,
                'category' => $this->rfq->category?->name,
                'delivery_date' => $this->rfq->delivery_date?->toDateString(),
                'deadline' => $this->rfq->deadline?->toDateTimeString(),
                'budget_range' => $this->rfq->budget_range,
                'created_at' => $this->rfq->created_at?->toDateTimeString(),
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'rfq.created';
    }
}