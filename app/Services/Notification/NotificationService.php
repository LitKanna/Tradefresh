<?php

namespace App\Services\Notification;

use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Twilio\Rest\Client as TwilioClient;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;

class NotificationService
{
    protected TwilioClient $twilioClient;
    protected WhatsAppService $whatsAppService;
    protected $firebaseMessaging;
    protected array $channels = ['email', 'sms', 'push', 'in_app', 'whatsapp'];
    protected array $priorityLevels = ['critical', 'high', 'normal', 'low'];
    
    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->twilioClient = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->whatsAppService = $whatsAppService;
        
        if (config('services.firebase.credentials_path')) {
            $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials_path'));
            $this->firebaseMessaging = $factory->createMessaging();
        }
    }
    
    /**
     * Send notification through multiple channels
     */
    public function send(
        $recipient,
        string $type,
        array $data = [],
        array $channels = null,
        string $priority = 'normal'
    ): array {
        $results = [];
        
        // Check rate limiting for non-critical notifications
        if ($priority !== 'critical' && !$this->checkRateLimit($recipient, $type)) {
            Log::warning("Rate limit exceeded for notification", [
                'recipient' => $recipient instanceof User ? $recipient->id : $recipient,
                'type' => $type
            ]);
            return ['error' => 'Rate limit exceeded'];
        }
        
        // Get template
        $template = $this->getTemplate($type, $data);
        if (!$template) {
            Log::error("Notification template not found", ['type' => $type]);
            return ['error' => 'Template not found'];
        }
        
        // Determine channels based on preferences or defaults
        $channels = $channels ?? $this->getPreferredChannels($recipient, $type, $priority);
        
        // Queue based on priority
        $queueName = $this->getQueueName($priority);
        
        foreach ($channels as $channel) {
            if (!$this->isChannelEnabled($recipient, $channel)) {
                continue;
            }
            
            // Dispatch to queue
            Queue::pushOn($queueName, function () use ($recipient, $channel, $template, $data, $type, &$results) {
                try {
                    $result = $this->sendViaChannel($recipient, $channel, $template, $data);
                    $results[$channel] = $result;
                    
                    // Log notification
                    $this->logNotification($recipient, $type, $channel, $data, $result);
                } catch (\Exception $e) {
                    Log::error("Failed to send notification", [
                        'channel' => $channel,
                        'error' => $e->getMessage()
                    ]);
                    $results[$channel] = ['success' => false, 'error' => $e->getMessage()];
                    
                    // Implement failover for critical notifications
                    if ($priority === 'critical') {
                        $this->handleFailover($recipient, $template, $data, $channel);
                    }
                }
            });
        }
        
        return $results;
    }
    
    /**
     * Send via specific channel
     */
    protected function sendViaChannel($recipient, string $channel, NotificationTemplate $template, array $data): array
    {
        switch ($channel) {
            case 'email':
                return $this->sendEmail($recipient, $template, $data);
                
            case 'sms':
                return $this->sendSMS($recipient, $template, $data);
                
            case 'push':
                return $this->sendPushNotification($recipient, $template, $data);
                
            case 'in_app':
                return $this->sendInAppNotification($recipient, $template, $data);
                
            case 'whatsapp':
                return $this->sendWhatsApp($recipient, $template, $data);
                
            default:
                throw new \InvalidArgumentException("Unknown channel: {$channel}");
        }
    }
    
    /**
     * Send email notification
     */
    protected function sendEmail($recipient, NotificationTemplate $template, array $data): array
    {
        $email = $recipient instanceof User ? $recipient->email : $recipient;
        
        $content = $this->parseTemplate($template->email_template, $data);
        $subject = $this->parseTemplate($template->email_subject, $data);
        
        Mail::send([], [], function ($message) use ($email, $subject, $content) {
            $message->to($email)
                ->subject($subject)
                ->html($content)
                ->from(config('mail.from.address'), config('mail.from.name'));
        });
        
        return ['success' => true, 'message_id' => uniqid('email_')];
    }
    
    /**
     * Send SMS notification
     */
    protected function sendSMS($recipient, NotificationTemplate $template, array $data): array
    {
        $phone = $recipient instanceof User ? $recipient->phone : $recipient;
        
        if (!$phone) {
            throw new \Exception("No phone number available");
        }
        
        $content = $this->parseTemplate($template->sms_template, $data);
        
        // Ensure phone number is in international format
        $phone = $this->formatPhoneNumber($phone);
        
        $message = $this->twilioClient->messages->create(
            $phone,
            [
                'from' => config('services.twilio.phone'),
                'body' => $content,
                'statusCallback' => route('api.notification.sms.callback')
            ]
        );
        
        return [
            'success' => true,
            'message_id' => $message->sid,
            'status' => $message->status
        ];
    }
    
    /**
     * Send push notification
     */
    protected function sendPushNotification($recipient, NotificationTemplate $template, array $data): array
    {
        if (!$this->firebaseMessaging) {
            throw new \Exception("Firebase not configured");
        }
        
        $tokens = $this->getDeviceTokens($recipient);
        
        if (empty($tokens)) {
            throw new \Exception("No device tokens available");
        }
        
        $title = $this->parseTemplate($template->push_title, $data);
        $body = $this->parseTemplate($template->push_body, $data);
        
        $notification = FirebaseNotification::create($title, $body);
        
        $results = [];
        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);
                
                $response = $this->firebaseMessaging->send($message);
                $results[] = ['token' => $token, 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['token' => $token, 'success' => false, 'error' => $e->getMessage()];
                
                // Remove invalid tokens
                if (str_contains($e->getMessage(), 'Unregistered') || 
                    str_contains($e->getMessage(), 'InvalidRegistration')) {
                    $this->removeDeviceToken($recipient, $token);
                }
            }
        }
        
        return [
            'success' => !empty(array_filter($results, fn($r) => $r['success'])),
            'results' => $results
        ];
    }
    
    /**
     * Send in-app notification
     */
    protected function sendInAppNotification($recipient, NotificationTemplate $template, array $data): array
    {
        $userId = $recipient instanceof User ? $recipient->id : $recipient;
        
        $notification = \App\Models\Notification::create([
            'user_id' => $userId,
            'type' => $template->type,
            'title' => $this->parseTemplate($template->in_app_title, $data),
            'message' => $this->parseTemplate($template->in_app_message, $data),
            'data' => $data,
            'read' => false,
            'priority' => $data['priority'] ?? 'normal'
        ]);
        
        // Broadcast real-time update
        broadcast(new \App\Events\NotificationCreated($notification))->toOthers();
        
        return [
            'success' => true,
            'notification_id' => $notification->id
        ];
    }
    
    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsApp($recipient, NotificationTemplate $template, array $data): array
    {
        $phone = $recipient instanceof User ? $recipient->phone : $recipient;
        
        if (!$phone) {
            throw new \Exception("No phone number available");
        }
        
        $content = $this->parseTemplate($template->whatsapp_template, $data);
        
        return $this->whatsAppService->sendMessage($phone, $content, $data);
    }
    
    /**
     * Parse template with data
     */
    protected function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace("{{{$key}}}", $value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Get template for notification type
     */
    protected function getTemplate(string $type, array $data): ?NotificationTemplate
    {
        $cacheKey = "notification_template_{$type}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $data) {
            $query = NotificationTemplate::where('type', $type)
                ->where('active', true);
            
            // Check for locale-specific template
            if (isset($data['locale'])) {
                $query->where('locale', $data['locale']);
            }
            
            return $query->first();
        });
    }
    
    /**
     * Get preferred channels for recipient
     */
    protected function getPreferredChannels($recipient, string $type, string $priority): array
    {
        if ($priority === 'critical') {
            // Use all available channels for critical notifications
            return $this->channels;
        }
        
        if ($recipient instanceof User) {
            $preferences = $recipient->notificationPreferences()
                ->where('notification_type', $type)
                ->first();
            
            if ($preferences) {
                return $preferences->channels;
            }
        }
        
        // Default channels based on priority
        return match($priority) {
            'high' => ['email', 'sms', 'push'],
            'normal' => ['email', 'push'],
            'low' => ['in_app'],
            default => ['email']
        };
    }
    
    /**
     * Check if channel is enabled for recipient
     */
    protected function isChannelEnabled($recipient, string $channel): bool
    {
        if (!$recipient instanceof User) {
            return in_array($channel, ['email', 'sms', 'whatsapp']);
        }
        
        // Check global opt-out
        if ($recipient->notification_settings) {
            $settings = json_decode($recipient->notification_settings, true);
            if (isset($settings['channels'][$channel]) && !$settings['channels'][$channel]) {
                return false;
            }
        }
        
        // Check channel-specific requirements
        return match($channel) {
            'email' => !empty($recipient->email) && $recipient->email_verified_at !== null,
            'sms' => !empty($recipient->phone) && $recipient->phone_verified_at !== null,
            'whatsapp' => !empty($recipient->phone),
            'push' => $this->hasDeviceTokens($recipient),
            'in_app' => true,
            default => false
        };
    }
    
    /**
     * Check rate limiting
     */
    protected function checkRateLimit($recipient, string $type): bool
    {
        $key = sprintf(
            'notification_rate_%s_%s',
            $recipient instanceof User ? $recipient->id : md5($recipient),
            $type
        );
        
        // Allow 10 notifications per hour per type
        return RateLimiter::attempt(
            $key,
            10,
            function() {
                return true;
            },
            3600
        );
    }
    
    /**
     * Get queue name based on priority
     */
    protected function getQueueName(string $priority): string
    {
        return match($priority) {
            'critical' => 'notifications-critical',
            'high' => 'notifications-high',
            'normal' => 'notifications',
            'low' => 'notifications-low',
            default => 'notifications'
        };
    }
    
    /**
     * Log notification
     */
    protected function logNotification($recipient, string $type, string $channel, array $data, array $result): void
    {
        NotificationLog::create([
            'user_id' => $recipient instanceof User ? $recipient->id : null,
            'business_id' => $recipient instanceof Business ? $recipient->id : null,
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient instanceof User ? $recipient->email : $recipient,
            'content' => $data,
            'status' => $result['success'] ? 'sent' : 'failed',
            'error' => $result['error'] ?? null,
            'message_id' => $result['message_id'] ?? null,
            'sent_at' => now()
        ]);
    }
    
    /**
     * Handle failover for critical notifications
     */
    protected function handleFailover($recipient, NotificationTemplate $template, array $data, string $failedChannel): void
    {
        $fallbackChannels = array_diff($this->channels, [$failedChannel]);
        
        foreach ($fallbackChannels as $channel) {
            try {
                if ($this->isChannelEnabled($recipient, $channel)) {
                    $this->sendViaChannel($recipient, $channel, $template, $data);
                    Log::info("Failover notification sent", [
                        'failed_channel' => $failedChannel,
                        'fallback_channel' => $channel
                    ]);
                    break;
                }
            } catch (\Exception $e) {
                Log::error("Failover channel failed", [
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add Australian country code if not present
        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            $phone = '61' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '61' . substr($phone, 1);
        }
        
        return '+' . $phone;
    }
    
    /**
     * Get device tokens for recipient
     */
    protected function getDeviceTokens($recipient): array
    {
        if (!$recipient instanceof User) {
            return [];
        }
        
        return $recipient->deviceTokens()
            ->where('active', true)
            ->where('expires_at', '>', now())
            ->pluck('token')
            ->toArray();
    }
    
    /**
     * Check if recipient has device tokens
     */
    protected function hasDeviceTokens($recipient): bool
    {
        return !empty($this->getDeviceTokens($recipient));
    }
    
    /**
     * Remove invalid device token
     */
    protected function removeDeviceToken($recipient, string $token): void
    {
        if ($recipient instanceof User) {
            $recipient->deviceTokens()
                ->where('token', $token)
                ->update(['active' => false]);
        }
    }
    
    /**
     * Send bulk notifications
     */
    public function sendBulk(array $recipients, string $type, array $data = [], array $channels = null): array
    {
        $results = [];
        
        // Chunk recipients to avoid memory issues
        $chunks = array_chunk($recipients, 100);
        
        foreach ($chunks as $chunk) {
            foreach ($chunk as $recipient) {
                // Queue each notification
                Queue::push(function () use ($recipient, $type, $data, $channels, &$results) {
                    $results[$recipient] = $this->send($recipient, $type, $data, $channels);
                });
            }
            
            // Small delay between chunks
            usleep(100000); // 100ms
        }
        
        return $results;
    }
}