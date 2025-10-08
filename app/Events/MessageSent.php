<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->message->load('sender');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to recipient's private channel
        $recipientChannel = new PrivateChannel(
            "messages.{$this->message->recipient_type}.{$this->message->recipient_id}"
        );

        // Also broadcast to quote channel if this is a quote-related message
        if ($this->message->quote_id) {
            return [
                $recipientChannel,
                new PrivateChannel('quote.'.$this->message->quote_id.'.messages'), // PRIVATE channel for security
            ];
        }

        return [$recipientChannel];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'quote_id' => $this->message->quote_id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'recipient_id' => $this->message->recipient_id,
            'recipient_type' => $this->message->recipient_type,
            'message' => $this->message->message,
            'sender_name' => $this->message->sender?->business_name ?? 'Unknown User',
            'created_at' => $this->message->created_at->toDateTimeString(),
            'is_read' => $this->message->is_read,
        ];
    }
}
