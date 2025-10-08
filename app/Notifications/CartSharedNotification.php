<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CartShare;

class CartSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $cartShare;

    /**
     * Create a new notification instance.
     */
    public function __construct(CartShare $cartShare)
    {
        $this->cartShare = $cartShare;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $sharedBy = $this->cartShare->sharedBy;
        $permissionText = $this->getPermissionText();
        
        return (new MailMessage)
            ->subject('Shopping Cart Shared With You')
            ->greeting('Hello!')
            ->line("{$sharedBy->name} has shared a shopping cart with you.")
            ->line("Permission: {$permissionText}")
            ->when($this->cartShare->message, function ($message) {
                return $message->line("Message: {$this->cartShare->message}");
            })
            ->line("Cart contains {$this->cartShare->cart->items_count} items totaling \${$this->cartShare->cart->total_amount}")
            ->action('View Shared Cart', $this->cartShare->share_url)
            ->line('This link will expire in 7 days.')
            ->line('Thank you for using our marketplace!');
    }

    /**
     * Get permission text description
     */
    protected function getPermissionText()
    {
        switch ($this->cartShare->permission) {
            case 'view':
                return 'View Only';
            case 'edit':
                return 'Can Edit Items';
            case 'approve':
                return 'Can Approve and Order';
            default:
                return 'View Only';
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'cart_share_id' => $this->cartShare->id,
            'shared_by' => $this->cartShare->shared_by,
            'permission' => $this->cartShare->permission,
            'share_url' => $this->cartShare->share_url,
        ];
    }
}