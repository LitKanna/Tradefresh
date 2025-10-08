<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Delivery;
use App\Models\NotificationPreference;
use App\Channels\TwilioSmsChannel;
use App\Channels\PushNotificationChannel;
use App\Channels\DatabaseNotificationChannel;
use App\Channels\EmailNotificationChannel;

class DeliveryUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $delivery;
    protected $updateType;
    protected $additionalData;

    public function __construct(Delivery $delivery, string $updateType, array $additionalData = [])
    {
        $this->delivery = $delivery;
        $this->updateType = $updateType;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        if (NotificationPreference::isEnabled($notifiable, 'email', 'delivery_update')) {
            $channels[] = EmailNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'sms', 'delivery_update')) {
            $channels[] = TwilioSmsChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'push', 'delivery_update')) {
            $channels[] = PushNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'database', 'delivery_update')) {
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
            ->subject($this->getEmailSubject())
            ->markdown('emails.notifications.delivery-update', [
                'delivery' => $this->delivery,
                'order' => $this->delivery->order,
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
        return [
            'content' => $this->getSmsMessage(),
            'metadata' => [
                'delivery_id' => $this->delivery->id,
                'order_id' => $this->delivery->order->id,
                'tracking_number' => $this->delivery->tracking_number,
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
            'title' => 'Delivery Update',
            'body' => $this->getPushMessage(),
            'data' => [
                'type' => 'delivery_update',
                'delivery_id' => $this->delivery->id,
                'order_id' => $this->delivery->order->id,
                'tracking_number' => $this->delivery->tracking_number,
                'update_type' => $this->updateType,
                'url' => '/deliveries/' . $this->delivery->id
            ],
            'badge' => 1,
            'sound' => 'default',
            'icon' => 'truck'
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'delivery_update',
            'title' => 'Delivery Update',
            'message' => $this->getDatabaseMessage(),
            'data' => [
                'delivery_id' => $this->delivery->id,
                'order_id' => $this->delivery->order->id,
                'order_number' => $this->delivery->order->order_number,
                'tracking_number' => $this->delivery->tracking_number,
                'update_type' => $this->updateType,
                'status' => $this->delivery->status,
                'estimated_delivery' => $this->delivery->estimated_delivery_time,
                'additional_data' => $this->additionalData,
                'url' => '/deliveries/' . $this->delivery->id
            ],
            'priority' => $this->getPriority(),
            'icon' => 'truck',
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
     * Get email subject based on update type
     */
    protected function getEmailSubject(): string
    {
        $orderNumber = $this->delivery->order->order_number;
        
        switch ($this->updateType) {
            case 'dispatched':
                return "Order #{$orderNumber} Has Been Dispatched";
            case 'in_transit':
                return "Order #{$orderNumber} Is On Its Way";
            case 'out_for_delivery':
                return "Order #{$orderNumber} Out for Delivery";
            case 'delivered':
                return "Order #{$orderNumber} Delivered Successfully";
            case 'delayed':
                return "Order #{$orderNumber} Delivery Delayed";
            case 'failed':
                return "Order #{$orderNumber} Delivery Failed";
            case 'rescheduled':
                return "Order #{$orderNumber} Delivery Rescheduled";
            default:
                return "Order #{$orderNumber} Delivery Update";
        }
    }

    /**
     * Get SMS message based on update type
     */
    protected function getSmsMessage(): string
    {
        $orderNumber = $this->delivery->order->order_number;
        $trackingUrl = url('/track/' . $this->delivery->tracking_number);
        
        switch ($this->updateType) {
            case 'dispatched':
                return "Your order #{$orderNumber} has been dispatched! Track: {$trackingUrl}";
            case 'in_transit':
                return "Your order #{$orderNumber} is on its way. Track: {$trackingUrl}";
            case 'out_for_delivery':
                return "Your order #{$orderNumber} is out for delivery today. Track: {$trackingUrl}";
            case 'delivered':
                return "Great news! Your order #{$orderNumber} has been delivered successfully.";
            case 'delayed':
                $reason = $this->additionalData['reason'] ?? 'unexpected circumstances';
                return "Your order #{$orderNumber} is delayed due to {$reason}. We apologize for the inconvenience.";
            case 'failed':
                return "Delivery attempt for order #{$orderNumber} failed. We'll contact you to reschedule.";
            case 'rescheduled':
                $newDate = $this->additionalData['new_date'] ?? 'soon';
                return "Your order #{$orderNumber} delivery has been rescheduled for {$newDate}.";
            default:
                return "Your order #{$orderNumber} has been updated. Track: {$trackingUrl}";
        }
    }

    /**
     * Get push notification message
     */
    protected function getPushMessage(): string
    {
        $orderNumber = $this->delivery->order->order_number;
        
        switch ($this->updateType) {
            case 'dispatched':
                return "Order #{$orderNumber} dispatched";
            case 'in_transit':
                return "Order #{$orderNumber} is on its way";
            case 'out_for_delivery':
                return "Order #{$orderNumber} out for delivery";
            case 'delivered':
                return "Order #{$orderNumber} delivered!";
            case 'delayed':
                return "Order #{$orderNumber} delayed";
            case 'failed':
                return "Order #{$orderNumber} delivery failed";
            case 'rescheduled':
                return "Order #{$orderNumber} rescheduled";
            default:
                return "Order #{$orderNumber} updated";
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
            case 'failed':
            case 'delayed':
                return 'high';
            case 'out_for_delivery':
            case 'delivered':
                return 'normal';
            case 'dispatched':
            case 'in_transit':
            case 'rescheduled':
                return 'low';
            default:
                return 'normal';
        }
    }

    /**
     * Get notification color
     */
    protected function getColor(): string
    {
        switch ($this->updateType) {
            case 'delivered':
                return 'success';
            case 'failed':
            case 'delayed':
                return 'error';
            case 'out_for_delivery':
                return 'warning';
            case 'dispatched':
            case 'in_transit':
            case 'rescheduled':
                return 'info';
            default:
                return 'info';
        }
    }
}