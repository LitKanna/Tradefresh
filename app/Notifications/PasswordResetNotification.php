<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\EmailNotificationChannel;
use App\Channels\DatabaseNotificationChannel;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;
    protected $resetUrl;

    public function __construct(string $token, string $resetUrl)
    {
        $this->token = $token;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Get the notification's delivery channels.
     * Password reset is critical - always send via email and database
     */
    public function via($notifiable)
    {
        return [
            EmailNotificationChannel::class,
            DatabaseNotificationChannel::class
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reset Your Password - Sydney Markets')
            ->markdown('emails.notifications.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $this->resetUrl,
                'token' => $this->token,
                'expiresIn' => '60 minutes'
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'password_reset',
            'title' => 'Password Reset Requested',
            'message' => 'A password reset link has been sent to your email address.',
            'data' => [
                'reset_url' => $this->resetUrl,
                'expires_at' => now()->addHour()->toISOString()
            ],
            'priority' => 'high',
            'icon' => 'lock',
            'color' => 'warning'
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