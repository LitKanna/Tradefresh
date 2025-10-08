<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\RFQ;
use App\Models\NotificationPreference;
use App\Channels\TwilioSmsChannel;
use App\Channels\PushNotificationChannel;
use App\Channels\DatabaseNotificationChannel;
use App\Channels\EmailNotificationChannel;

class RfqUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rfq;
    protected $updateType;
    protected $additionalData;

    public function __construct(RFQ $rfq, string $updateType, array $additionalData = [])
    {
        $this->rfq = $rfq;
        $this->updateType = $updateType;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        if (NotificationPreference::isEnabled($notifiable, 'email', 'rfq_update')) {
            $channels[] = EmailNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'sms', 'rfq_update')) {
            $channels[] = TwilioSmsChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'push', 'rfq_update')) {
            $channels[] = PushNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'database', 'rfq_update')) {
            $channels[] = DatabaseNotificationChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $subject = $this->getEmailSubject();
        
        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.notifications.rfq-update', [
                'rfq' => $this->rfq,
                'updateType' => $this->updateType,
                'additionalData' => $this->additionalData,
                'recipient' => $notifiable
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $message = $this->getSmsMessage();
        
        return [
            'content' => $message,
            'metadata' => [
                'rfq_id' => $this->rfq->id,
                'rfq_number' => $this->rfq->rfq_number,
                'update_type' => $this->updateType
            ]
        ];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable)
    {
        return [
            'title' => 'RFQ Update',
            'body' => $this->getPushMessage(),
            'data' => [
                'type' => 'rfq_update',
                'rfq_id' => $this->rfq->id,
                'rfq_number' => $this->rfq->rfq_number,
                'update_type' => $this->updateType,
                'url' => $this->getRfqUrl()
            ],
            'badge' => 1,
            'sound' => 'default',
            'icon' => 'rfq-update'
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'rfq_update',
            'title' => 'RFQ Update',
            'message' => $this->getDatabaseMessage(),
            'data' => [
                'rfq_id' => $this->rfq->id,
                'rfq_number' => $this->rfq->rfq_number,
                'update_type' => $this->updateType,
                'additional_data' => $this->additionalData,
                'url' => $this->getRfqUrl()
            ],
            'priority' => $this->getPriority(),
            'icon' => 'document',
            'color' => 'info'
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
     * Get email subject based on update type
     */
    protected function getEmailSubject(): string
    {
        switch ($this->updateType) {
            case 'created':
                return "New RFQ #{$this->rfq->rfq_number} Received";
            case 'quote_received':
                return "New Quote for RFQ #{$this->rfq->rfq_number}";
            case 'status_changed':
                return "RFQ #{$this->rfq->rfq_number} Status Updated";
            case 'deadline_reminder':
                return "RFQ #{$this->rfq->rfq_number} Deadline Reminder";
            case 'closed':
                return "RFQ #{$this->rfq->rfq_number} Closed";
            default:
                return "RFQ #{$this->rfq->rfq_number} Update";
        }
    }

    /**
     * Get SMS message based on update type
     */
    protected function getSmsMessage(): string
    {
        switch ($this->updateType) {
            case 'created':
                return "New RFQ #{$this->rfq->rfq_number} has been created. View details at " . $this->getRfqUrl();
            case 'quote_received':
                return "You received a new quote for RFQ #{$this->rfq->rfq_number}. Check it out at " . $this->getRfqUrl();
            case 'status_changed':
                $status = $this->additionalData['new_status'] ?? 'updated';
                return "RFQ #{$this->rfq->rfq_number} status changed to {$status}. View at " . $this->getRfqUrl();
            case 'deadline_reminder':
                return "Reminder: RFQ #{$this->rfq->rfq_number} expires soon. Don't miss out!";
            case 'closed':
                return "RFQ #{$this->rfq->rfq_number} has been closed.";
            default:
                return "RFQ #{$this->rfq->rfq_number} has been updated.";
        }
    }

    /**
     * Get push notification message
     */
    protected function getPushMessage(): string
    {
        switch ($this->updateType) {
            case 'created':
                return "New RFQ #{$this->rfq->rfq_number} is ready for quotes";
            case 'quote_received':
                return "New quote received for RFQ #{$this->rfq->rfq_number}";
            case 'status_changed':
                return "RFQ #{$this->rfq->rfq_number} status updated";
            case 'deadline_reminder':
                return "RFQ #{$this->rfq->rfq_number} expires soon";
            case 'closed':
                return "RFQ #{$this->rfq->rfq_number} has been closed";
            default:
                return "RFQ #{$this->rfq->rfq_number} updated";
        }
    }

    /**
     * Get database notification message
     */
    protected function getDatabaseMessage(): string
    {
        return $this->getPushMessage();
    }

    /**
     * Get notification priority
     */
    protected function getPriority(): string
    {
        switch ($this->updateType) {
            case 'deadline_reminder':
                return 'high';
            case 'quote_received':
            case 'created':
                return 'normal';
            case 'closed':
            case 'status_changed':
                return 'low';
            default:
                return 'normal';
        }
    }

    /**
     * Get RFQ URL
     */
    protected function getRfqUrl(): string
    {
        return url('/rfqs/' . $this->rfq->id);
    }
}