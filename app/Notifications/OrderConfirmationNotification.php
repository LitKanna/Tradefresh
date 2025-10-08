<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;
use App\Models\NotificationPreference;
use App\Channels\TwilioSmsChannel;
use App\Channels\PushNotificationChannel;
use App\Channels\DatabaseNotificationChannel;
use App\Channels\EmailNotificationChannel;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        if (NotificationPreference::isEnabled($notifiable, 'email', 'order_confirmation')) {
            $channels[] = EmailNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'sms', 'order_confirmation')) {
            $channels[] = TwilioSmsChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'push', 'order_confirmation')) {
            $channels[] = PushNotificationChannel::class;
        }
        
        if (NotificationPreference::isEnabled($notifiable, 'database', 'order_confirmation')) {
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
            ->subject('Order Confirmation - #' . $this->order->order_number)
            ->markdown('emails.notifications.order-confirmation', [
                'order' => $this->order,
                'buyer' => $notifiable,
                'total' => $this->order->total_amount,
                'items' => $this->order->items,
                'vendor' => $this->order->vendor
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        return [
            'content' => "Your order #{$this->order->order_number} has been confirmed! Total: $" . number_format($this->order->total_amount, 2) . ". Track your order at " . url('/orders/' . $this->order->id),
            'metadata' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number
            ]
        ];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable)
    {
        return [
            'title' => 'Order Confirmed!',
            'body' => "Your order #{$this->order->order_number} for $" . number_format($this->order->total_amount, 2) . " has been confirmed.",
            'data' => [
                'type' => 'order_confirmation',
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'url' => '/orders/' . $this->order->id
            ],
            'badge' => 1,
            'sound' => 'default',
            'icon' => 'order-confirmed'
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'order_confirmation',
            'title' => 'Order Confirmed',
            'message' => "Your order #{$this->order->order_number} has been confirmed.",
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'total_amount' => $this->order->total_amount,
                'vendor_name' => $this->order->vendor->company_name,
                'items_count' => $this->order->items->count(),
                'url' => '/orders/' . $this->order->id
            ],
            'priority' => 'normal',
            'icon' => 'shopping-cart',
            'color' => 'success'
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