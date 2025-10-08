<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\BroadcastMessage;
use App\Models\NotificationPreference;
use App\Channels\TwilioSmsChannel;
use App\Channels\PushNotificationChannel;
use App\Channels\DatabaseNotificationChannel;
use App\Channels\EmailNotificationChannel;

class BroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $broadcastMessage;
    protected $enabledChannels;

    public function __construct(BroadcastMessage $broadcastMessage, array $enabledChannels = [])
    {
        $this->broadcastMessage = $broadcastMessage;
        $this->enabledChannels = $enabledChannels ?: $broadcastMessage->channels;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        foreach ($this->enabledChannels as $channel) {
            if (NotificationPreference::isEnabled($notifiable, $channel, 'broadcast')) {
                switch ($channel) {
                    case 'email':
                        $channels[] = EmailNotificationChannel::class;
                        break;
                    case 'sms':
                        $channels[] = TwilioSmsChannel::class;
                        break;
                    case 'push':
                        $channels[] = PushNotificationChannel::class;
                        break;
                    case 'database':
                        $channels[] = DatabaseNotificationChannel::class;
                        break;
                }
            }
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->broadcastMessage->title)
            ->markdown('emails.notifications.broadcast', [
                'broadcast' => $this->broadcastMessage,
                'recipient' => $notifiable,
                'title' => $this->broadcastMessage->title,
                'message' => $this->broadcastMessage->message,
                'priority' => $this->broadcastMessage->priority
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $message = $this->broadcastMessage->message;
        
        // Truncate SMS message if too long
        if (strlen($message) > 140) {
            $message = substr($message, 0, 137) . '...';
        }
        
        return [
            'content' => $message,
            'metadata' => [
                'broadcast_id' => $this->broadcastMessage->id,
                'priority' => $this->broadcastMessage->priority,
                'type' => 'broadcast'
            ]
        ];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable)
    {
        return [
            'title' => $this->broadcastMessage->title,
            'body' => $this->broadcastMessage->message,
            'data' => [
                'type' => 'broadcast',
                'broadcast_id' => $this->broadcastMessage->id,
                'priority' => $this->broadcastMessage->priority,
                'admin_id' => $this->broadcastMessage->admin_id
            ],
            'badge' => 1,
            'sound' => $this->getPushSound(),
            'icon' => 'notification'
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'broadcast',
            'title' => $this->broadcastMessage->title,
            'message' => $this->broadcastMessage->message,
            'data' => [
                'broadcast_id' => $this->broadcastMessage->id,
                'admin_id' => $this->broadcastMessage->admin_id,
                'admin_name' => $this->broadcastMessage->admin->name ?? 'System',
                'target_audience' => $this->broadcastMessage->target_audience,
                'sent_at' => now()->toISOString()
            ],
            'priority' => $this->broadcastMessage->priority,
            'icon' => 'speakerphone',
            'color' => $this->getColor()
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get push notification sound based on priority
     */
    protected function getPushSound(): string
    {
        switch ($this->broadcastMessage->priority) {
            case 'urgent':
                return 'urgent.wav';
            case 'high':
                return 'high.wav';
            case 'normal':
                return 'default';
            case 'low':
                return 'soft.wav';
            default:
                return 'default';
        }
    }

    /**
     * Get notification color based on priority
     */
    protected function getColor(): string
    {
        switch ($this->broadcastMessage->priority) {
            case 'urgent':
                return 'error';
            case 'high':
                return 'warning';
            case 'normal':
                return 'info';
            case 'low':
                return 'secondary';
            default:
                return 'info';
        }
    }
}