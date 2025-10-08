<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Quote;

class NewQuoteRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $quote;

    /**
     * Create a new notification instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $urgencyLabel = match($this->quote->urgency_level) {
            'urgent' => '🔴 URGENT',
            'standard' => '🟢 Standard',
            'flexible' => '🔵 Flexible',
            default => 'Standard'
        };

        return (new MailMessage)
            ->subject($urgencyLabel . ' Quote Request: ' . $this->quote->quote_number)
            ->greeting('Hello ' . $notifiable->business_name . '!')
            ->line('You have received a new quote request.')
            ->line('**Quote Details:**')
            ->line('Quote Number: ' . $this->quote->quote_number)
            ->line('Buyer: ' . $this->quote->buyer->name)
            ->line('Total Amount: $' . number_format($this->quote->total_amount, 2))
            ->line('Delivery Date: ' . $this->quote->requested_delivery_date->format('M d, Y'))
            ->line('Payment Terms: ' . ucfirst(str_replace('_', ' ', $this->quote->payment_terms)))
            ->when($this->quote->special_requirements, function ($message) {
                return $message->line('Special Requirements: ' . $this->quote->special_requirements);
            })
            ->action('View Quote Request', url('/vendor/quotes/' . $this->quote->id))
            ->line('Please respond to this quote request at your earliest convenience.')
            ->line('Thank you for your business!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_quote_request',
            'quote_id' => $this->quote->id,
            'quote_number' => $this->quote->quote_number,
            'buyer_name' => $this->quote->buyer->name,
            'total_amount' => $this->quote->total_amount,
            'urgency_level' => $this->quote->urgency_level,
            'delivery_date' => $this->quote->requested_delivery_date->format('Y-m-d'),
            'message' => 'New quote request from ' . $this->quote->buyer->name
        ];
    }
}