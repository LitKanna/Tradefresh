<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected Order $order;
    protected string $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $recipient = 'buyer')
    {
        $this->order = $order;
        $this->recipient = $recipient;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubject();
        $template = "emails.orders.confirmation-{$this->recipient}";
        
        return $this->view($template)
            ->subject($subject)
            ->with([
                'order' => $this->order,
                'buyer' => $this->order->buyer,
                'vendor' => $this->order->vendor,
                'items' => $this->order->items,
                'invoice' => $this->order->invoice,
                'payment' => $this->order->payments->first(),
                'deliveryAddress' => json_decode($this->order->delivery_address, true),
                'trackingUrl' => route('orders.tracking', ['order' => $this->order->order_number])
            ]);
    }

    /**
     * Get email subject based on recipient
     */
    protected function getSubject(): string
    {
        return match($this->recipient) {
            'buyer' => "Order Confirmation #{$this->order->order_number} - Sydney Markets",
            'vendor' => "New Order #{$this->order->order_number} from {$this->order->buyer->company_name}",
            default => "Order #{$this->order->order_number}"
        };
    }
}