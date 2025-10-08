<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Quote;
use App\Models\NotificationPreference;
use App\Channels\TwilioSmsChannel;
use App\Channels\PushNotificationChannel;
use App\Channels\DatabaseNotificationChannel;
use App\Channels\EmailNotificationChannel;

class QuoteReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        if (NotificationPreference::isEnabled($notifiable, 'email', 'quote_received')) {
            $channels[] = EmailNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'sms', 'quote_received')) {
            $channels[] = TwilioSmsChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'push', 'quote_received')) {
            $channels[] = PushNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'database', 'quote_received')) {
            $channels[] = DatabaseNotificationChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Quote Received - RFQ #' . $this->quote->rfq->rfq_number)
            ->markdown('emails.notifications.quote-received', [
                'quote' => $this->quote,
                'rfq' => $this->quote->rfq,
                'vendor' => $this->quote->vendor,
                'buyer' => $notifiable,
                'total' => $this->quote->total_amount,
                'items' => $this->quote->items
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        return [
            'content' => "New quote received from {$this->quote->vendor->company_name} for RFQ #{$this->quote->rfq->rfq_number}. Total: $" . number_format($this->quote->total_amount, 2) . ". View at " . url('/quotes/' . $this->quote->id),
            'metadata' => [
                'quote_id' => $this->quote->id,
                'rfq_id' => $this->quote->rfq->id,
                'vendor_id' => $this->quote->vendor->id
            ]
        ];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable)
    {
        return [
            'title' => 'New Quote Received!',
            'body' => "{$this->quote->vendor->company_name} sent a quote for $" . number_format($this->quote->total_amount, 2),
            'data' => [
                'type' => 'quote_received',
                'quote_id' => $this->quote->id,
                'rfq_id' => $this->quote->rfq->id,
                'vendor_id' => $this->quote->vendor->id,
                'url' => '/quotes/' . $this->quote->id
            ],
            'badge' => 1,
            'sound' => 'default',
            'icon' => 'quote'
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'quote_received',
            'title' => 'New Quote Received',
            'message' => "You received a new quote from {$this->quote->vendor->company_name} for RFQ #{$this->quote->rfq->rfq_number}",
            'data' => [
                'quote_id' => $this->quote->id,
                'rfq_id' => $this->quote->rfq->id,
                'rfq_number' => $this->quote->rfq->rfq_number,
                'vendor_id' => $this->quote->vendor->id,
                'vendor_name' => $this->quote->vendor->company_name,
                'total_amount' => $this->quote->total_amount,
                'items_count' => $this->quote->items->count(),
                'valid_until' => $this->quote->valid_until,
                'url' => '/quotes/' . $this->quote->id
            ],
            'priority' => 'normal',
            'icon' => 'document-text',
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
}