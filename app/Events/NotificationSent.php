<?php

namespace App\Events;

use App\Models\EnhancedNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(EnhancedNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Private channel for the notification recipient
        $channels[] = new PrivateChannel(
            'user.' . $this->notification->notifiable_type . '.' . $this->notification->notifiable_id
        );

        // Team channel if notification is shared with a team
        if ($this->notification->team_id) {
            $channels[] = new PrivateChannel('team.' . $this->notification->team_id);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'category' => $this->notification->category,
            'priority' => $this->notification->priority,
            'is_actionable' => $this->notification->is_actionable,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'icon' => $this->notification->icon,
            'created_at' => $this->notification->created_at->toISOString(),
            'time_ago' => $this->notification->time_ago,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }
}