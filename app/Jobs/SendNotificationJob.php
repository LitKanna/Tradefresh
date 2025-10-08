<?php

namespace App\Jobs;

use App\Models\EnhancedNotification;
use App\Models\NotificationAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, and 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(EnhancedNotification $notification)
    {
        $this->notification = $notification;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->notification->channels || empty($this->notification->channels)) {
            $this->notification->update(['status' => 'failed']);
            return;
        }

        $failedChannels = [];
        $successChannels = [];

        foreach ($this->notification->channels as $channel) {
            try {
                $this->sendViaChannel($channel);
                $successChannels[] = $channel;
                
                // Record successful delivery
                NotificationAnalytics::createEvent(
                    $this->notification,
                    $channel,
                    'delivered'
                );
                
            } catch (\Exception $e) {
                $failedChannels[] = $channel;
                
                // Record failure
                NotificationAnalytics::createEvent(
                    $this->notification,
                    $channel,
                    'failed',
                    ['error' => $e->getMessage()]
                );
                
                Log::error("Notification delivery failed", [
                    'notification_id' => $this->notification->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update notification status
        if (empty($failedChannels)) {
            $this->notification->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } elseif (empty($successChannels)) {
            $this->notification->update(['status' => 'failed']);
        } else {
            // Partial success - keep as sent but log failures
            Log::warning("Notification partially delivered", [
                'notification_id' => $this->notification->id,
                'success_channels' => $successChannels,
                'failed_channels' => $failedChannels,
            ]);
        }
    }

    /**
     * Send notification via specific channel.
     */
    protected function sendViaChannel(string $channel): void
    {
        switch ($channel) {
            case 'email':
                $this->sendEmail();
                break;
            case 'sms':
                $this->sendSMS();
                break;
            case 'push':
                $this->sendPush();
                break;
            case 'database':
                // Database notification is already created
                break;
            default:
                throw new \InvalidArgumentException("Unsupported channel: {$channel}");
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(): void
    {
        $notifiable = $this->notification->notifiable;
        
        if (!$notifiable || !method_exists($notifiable, 'getEmailForNotifications')) {
            throw new \Exception('Notifiable does not support email notifications');
        }

        $email = $notifiable->getEmailForNotifications();
        if (!$email) {
            throw new \Exception('No email address available');
        }

        // Create email content
        $emailData = [
            'notification' => $this->notification,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'metadata' => $this->notification->metadata,
        ];

        // Use template if available
        if ($this->notification->template) {
            $rendered = $this->notification->template->render($this->notification->data);
            $emailData['rendered_content'] = $rendered;
        }

        // Send email using Laravel Mail
        Mail::send('emails.notifications.enhanced', $emailData, function ($message) use ($email) {
            $message->to($email)
                    ->subject($this->notification->title)
                    ->priority($this->getEmailPriority());
                    
            // Add tracking headers
            $message->getSwiftMessage()->getHeaders()
                    ->addTextHeader('X-Notification-ID', $this->notification->id);
        });
    }

    /**
     * Send SMS notification.
     */
    protected function sendSMS(): void
    {
        $notifiable = $this->notification->notifiable;
        
        if (!$notifiable || !method_exists($notifiable, 'getPhoneForNotifications')) {
            throw new \Exception('Notifiable does not support SMS notifications');
        }

        $phone = $notifiable->getPhoneForNotifications();
        if (!$phone) {
            throw new \Exception('No phone number available');
        }

        // Prepare SMS content (keep it short)
        $content = $this->notification->title;
        if (strlen($content) > 140) {
            $content = substr($content, 0, 137) . '...';
        }

        // Add action URL if available and space permits
        if ($this->notification->action_url && strlen($content) < 100) {
            $content .= ' ' . $this->notification->action_url;
        }

        // Send via Twilio (or your SMS provider)
        $this->sendViaTwilio($phone, $content);
    }

    /**
     * Send push notification.
     */
    protected function sendPush(): void
    {
        $notifiable = $this->notification->notifiable;
        
        // Get push tokens from user preferences or device registrations
        $preferences = $notifiable->notificationPreferences ?? 
                      $notifiable->notificationPreferencesEnhanced;
        
        if (!$preferences || !$preferences->push_token) {
            throw new \Exception('No push token available');
        }

        $pushData = [
            'title' => $this->notification->title,
            'body' => strip_tags($this->notification->content),
            'data' => [
                'notification_id' => $this->notification->id,
                'category' => $this->notification->category,
                'priority' => $this->notification->priority,
                'action_url' => $this->notification->action_url,
            ],
            'priority' => $this->notification->priority === 'urgent' ? 'high' : 'normal',
        ];

        // Send via FCM (Firebase Cloud Messaging)
        $this->sendViaFCM($preferences->push_token, $pushData);
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendViaTwilio(string $phone, string $message): void
    {
        $twilioSid = config('services.twilio.sid');
        $twilioToken = config('services.twilio.token');
        $twilioFrom = config('services.twilio.from');

        if (!$twilioSid || !$twilioToken) {
            // Mock SMS sending in development
            if (app()->environment(['local', 'testing'])) {
                Log::info('Mock SMS sent', [
                    'to' => $phone,
                    'message' => $message,
                ]);
                return;
            }
            
            throw new \Exception('Twilio credentials not configured');
        }

        $response = Http::withBasicAuth($twilioSid, $twilioToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                'To' => $phone,
                'From' => $twilioFrom,
                'Body' => $message,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Twilio API error: ' . $response->body());
        }

        // Record SMS analytics
        NotificationAnalytics::createEvent(
            $this->notification,
            'sms',
            'sent',
            [
                'sms_id' => $response->json('sid'),
                'phone_number' => $phone,
                'provider' => 'twilio',
            ]
        );
    }

    /**
     * Send push notification via Firebase Cloud Messaging.
     */
    protected function sendViaFCM(string $token, array $data): void
    {
        $fcmServerKey = config('services.fcm.server_key');

        if (!$fcmServerKey) {
            // Mock push notification in development
            if (app()->environment(['local', 'testing'])) {
                Log::info('Mock push notification sent', [
                    'token' => $token,
                    'data' => $data,
                ]);
                return;
            }
            
            throw new \Exception('FCM server key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => "key={$fcmServerKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'icon' => 'notification_icon',
                'sound' => 'default',
            ],
            'data' => $data['data'],
            'priority' => $data['priority'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('FCM API error: ' . $response->body());
        }

        // Record push analytics
        NotificationAnalytics::createEvent(
            $this->notification,
            'push',
            'sent',
            [
                'push_id' => $response->json('multicast_id'),
                'push_token' => $token,
                'provider' => 'fcm',
            ]
        );
    }

    /**
     * Get email priority header value.
     */
    protected function getEmailPriority(): int
    {
        return match ($this->notification->priority) {
            'urgent' => 1,
            'high' => 2,
            'medium' => 3,
            'low' => 4,
            default => 3,
        };
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->notification->update(['status' => 'failed']);
        
        Log::error('Notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts,
        ]);

        // Record failure analytics for all channels
        foreach ($this->notification->channels as $channel) {
            NotificationAnalytics::createEvent(
                $this->notification,
                $channel,
                'failed',
                [
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts,
                ]
            );
        }
    }
}