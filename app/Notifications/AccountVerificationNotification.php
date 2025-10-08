<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\NotificationPreference;
use App\Channels\EmailNotificationChannel;
use App\Channels\DatabaseNotificationChannel;

class AccountVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verificationUrl;
    protected $userType;

    public function __construct(string $verificationUrl, string $userType = 'user')
    {
        $this->verificationUrl = $verificationUrl;
        $this->userType = $userType;
    }

    /**
     * Get the notification's delivery channels.
     * Account verification is critical - always send via email and database
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
            ->subject('Verify Your Sydney Markets Account')
            ->markdown('emails.notifications.account-verification', [
                'user' => $notifiable,
                'verificationUrl' => $this->verificationUrl,
                'userType' => $this->userType
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'account_verification',
            'title' => 'Verify Your Account',
            'message' => 'Please verify your email address to complete your registration.',
            'data' => [
                'verification_url' => $this->verificationUrl,
                'user_type' => $this->userType,
                'expires_at' => now()->addHours(24)->toISOString()
            ],
            'priority' => 'high',
            'icon' => 'mail',
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