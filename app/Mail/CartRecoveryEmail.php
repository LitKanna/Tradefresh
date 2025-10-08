<?php

namespace App\Mail;

use App\Models\CartAbandonmentRecovery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CartRecoveryEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected CartAbandonmentRecovery $recovery;
    protected string $emailType;

    /**
     * Create a new message instance.
     */
    public function __construct(CartAbandonmentRecovery $recovery, string $emailType)
    {
        $this->recovery = $recovery;
        $this->emailType = $emailType;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubject();
        $template = "emails.cart.recovery-{$this->emailType}";
        
        return $this->view($template)
            ->subject($subject)
            ->with([
                'recovery' => $this->recovery,
                'cart' => $this->recovery->cart,
                'buyer' => $this->recovery->buyer,
                'items' => $this->recovery->getTopProducts(3),
                'recoveryUrl' => $this->recovery->getRecoveryUrl(),
                'couponCode' => $this->recovery->recovery_coupon_code,
                'discount' => $this->recovery->recovery_discount_percentage,
                'cartValue' => $this->recovery->cart_value,
                'itemsCount' => $this->recovery->getCartItemsCount()
            ]);
    }

    /**
     * Get email subject based on type
     */
    protected function getSubject(): string
    {
        $buyerName = $this->recovery->buyer->name ?? 'Valued Customer';
        
        return match($this->emailType) {
            'first' => "Don't forget your cart, {$buyerName}!",
            'second' => "Still thinking about your order, {$buyerName}? Get 10% off!",
            'third' => "Last chance: 15% off your cart, {$buyerName}",
            default => "Complete your order at Sydney Markets"
        };
    }
}