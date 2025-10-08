<?php

namespace App\Services;

use App\Models\User;
use App\Modules\RFQ\Models\RFQ;
use App\Modules\Quote\Models\Quote;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $whatsappApiUrl;
    protected $whatsappApiKey;
    protected $wechatApiUrl;
    protected $wechatApiKey;
    protected $smsApiUrl;
    protected $smsApiKey;

    public function __construct()
    {
        $this->whatsappApiUrl = config('services.whatsapp.api_url');
        $this->whatsappApiKey = config('services.whatsapp.api_key');
        $this->wechatApiUrl = config('services.wechat.api_url');
        $this->wechatApiKey = config('services.wechat.api_key');
        $this->smsApiUrl = config('services.sms.api_url');
        $this->smsApiKey = config('services.sms.api_key');
    }

    /**
     * Send notification through multiple channels
     */
    public function sendNotification(User $user, string $type, array $data): void
    {
        // In-app notification
        $this->sendInAppNotification($user, $type, $data);

        // Email notification
        if ($this->shouldSendEmail($user, $type)) {
            $this->sendEmailNotification($user, $type, $data);
        }

        // SMS notification
        if ($this->shouldSendSMS($user, $type)) {
            $this->sendSMSNotification($user, $type, $data);
        }

        // WhatsApp notification
        if ($this->shouldSendWhatsApp($user, $type)) {
            $this->sendWhatsAppNotification($user, $type, $data);
        }

        // WeChat notification
        if ($this->shouldSendWeChat($user, $type)) {
            $this->sendWeChatNotification($user, $type, $data);
        }
    }

    /**
     * Send in-app notification
     */
    protected function sendInAppNotification(User $user, string $type, array $data): void
    {
        $user->notifications()->create([
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);

        // Broadcast real-time notification if user is online
        $this->broadcastRealtimeNotification($user, $type, $data);
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(User $user, string $type, array $data): void
    {
        try {
            $emailClass = $this->getEmailClass($type);
            
            if ($emailClass) {
                Mail::to($user->email)->send(new $emailClass($data));
            }
        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SMS notification
     */
    protected function sendSMSNotification(User $user, string $type, array $data): void
    {
        if (!$user->phone) {
            return;
        }

        try {
            $message = $this->getSMSMessage($type, $data);
            
            $response = Http::post($this->smsApiUrl, [
                'api_key' => $this->smsApiKey,
                'to' => $user->phone,
                'message' => $message
            ]);

            if (!$response->successful()) {
                throw new \Exception('SMS API request failed');
            }

            // Log SMS sent
            $this->logNotification($user->id, 'sms', $type, $response->json());
        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsAppNotification(User $user, string $type, array $data): void
    {
        if (!$user->whatsapp_number) {
            return;
        }

        try {
            $message = $this->getWhatsAppMessage($type, $data);
            
            $response = Http::post($this->whatsappApiUrl . '/messages', [
                'api_key' => $this->whatsappApiKey,
                'to' => $user->whatsapp_number,
                'type' => 'template',
                'template' => [
                    'name' => $this->getWhatsAppTemplate($type),
                    'language' => ['code' => 'en'],
                    'components' => $this->getWhatsAppComponents($type, $data)
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('WhatsApp API request failed');
            }

            // Log WhatsApp sent
            $this->logNotification($user->id, 'whatsapp', $type, $response->json());
        } catch (\Exception $e) {
            Log::error('WhatsApp notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send WeChat notification
     */
    protected function sendWeChatNotification(User $user, string $type, array $data): void
    {
        if (!$user->wechat_openid) {
            return;
        }

        try {
            $template = $this->getWeChatTemplate($type);
            
            $response = Http::post($this->wechatApiUrl . '/message/template/send', [
                'access_token' => $this->getWeChatAccessToken(),
                'touser' => $user->wechat_openid,
                'template_id' => $template['id'],
                'url' => $template['url'] ?? '',
                'data' => $this->formatWeChatData($type, $data)
            ]);

            if (!$response->successful()) {
                throw new \Exception('WeChat API request failed');
            }

            // Log WeChat sent
            $this->logNotification($user->id, 'wechat', $type, $response->json());
        } catch (\Exception $e) {
            Log::error('WeChat notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast real-time notification
     */
    protected function broadcastRealtimeNotification(User $user, string $type, array $data): void
    {
        try {
            broadcast(new \App\Events\UserNotification($user, $type, $data))->toOthers();
        } catch (\Exception $e) {
            Log::error('Broadcast notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if email should be sent
     */
    protected function shouldSendEmail(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];
        return $preferences['email'][$type] ?? true;
    }

    /**
     * Check if SMS should be sent
     */
    protected function shouldSendSMS(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];
        $criticalTypes = ['rfq_deadline_approaching', 'quote_accepted', 'order_confirmed'];
        
        return in_array($type, $criticalTypes) && ($preferences['sms'][$type] ?? false);
    }

    /**
     * Check if WhatsApp should be sent
     */
    protected function shouldSendWhatsApp(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];
        return $user->whatsapp_number && ($preferences['whatsapp'][$type] ?? false);
    }

    /**
     * Check if WeChat should be sent
     */
    protected function shouldSendWeChat(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];
        return $user->wechat_openid && ($preferences['wechat'][$type] ?? false);
    }

    /**
     * Get email class for notification type
     */
    protected function getEmailClass(string $type): ?string
    {
        $classes = [
            'rfq_posted' => \App\Mail\RFQPosted::class,
            'quote_received' => \App\Mail\QuoteReceived::class,
            'quote_accepted' => \App\Mail\QuoteAccepted::class,
            'quote_rejected' => \App\Mail\QuoteRejected::class,
            'rfq_deadline_approaching' => \App\Mail\RFQDeadlineApproaching::class,
        ];

        return $classes[$type] ?? null;
    }

    /**
     * Get SMS message for notification type
     */
    protected function getSMSMessage(string $type, array $data): string
    {
        $messages = [
            'rfq_posted' => "New RFQ: {$data['title']}. Deadline: {$data['deadline']}. Check Sydney Markets app.",
            'quote_received' => "New quote received for RFQ: {$data['rfq_title']}. Amount: \${$data['total_price']}",
            'quote_accepted' => "Congratulations! Your quote for {$data['rfq_title']} has been accepted.",
            'rfq_deadline_approaching' => "RFQ deadline approaching: {$data['title']} expires in {$data['hours_remaining']} hours.",
        ];

        return $messages[$type] ?? "You have a new notification. Check Sydney Markets app.";
    }

    /**
     * Get WhatsApp template name
     */
    protected function getWhatsAppTemplate(string $type): string
    {
        $templates = [
            'rfq_posted' => 'rfq_notification',
            'quote_received' => 'quote_notification',
            'quote_accepted' => 'quote_accepted',
            'rfq_deadline_approaching' => 'deadline_reminder',
        ];

        return $templates[$type] ?? 'general_notification';
    }

    /**
     * Get WhatsApp message components
     */
    protected function getWhatsAppComponents(string $type, array $data): array
    {
        // Format components based on WhatsApp template requirements
        return [
            [
                'type' => 'body',
                'parameters' => array_map(function ($value) {
                    return ['type' => 'text', 'text' => (string)$value];
                }, array_values($data))
            ]
        ];
    }

    /**
     * Get WeChat template
     */
    protected function getWeChatTemplate(string $type): array
    {
        $templates = [
            'rfq_posted' => [
                'id' => 'TEMPLATE_ID_RFQ_POSTED',
                'url' => config('app.url') . '/rfqs'
            ],
            'quote_received' => [
                'id' => 'TEMPLATE_ID_QUOTE_RECEIVED',
                'url' => config('app.url') . '/quotes'
            ],
            // Add more templates
        ];

        return $templates[$type] ?? ['id' => 'TEMPLATE_ID_DEFAULT'];
    }

    /**
     * Format data for WeChat
     */
    protected function formatWeChatData(string $type, array $data): array
    {
        // Format data according to WeChat template requirements
        $formatted = [];
        
        foreach ($data as $key => $value) {
            $formatted[$key] = [
                'value' => (string)$value,
                'color' => '#173177'
            ];
        }

        return $formatted;
    }

    /**
     * Get WeChat access token
     */
    protected function getWeChatAccessToken(): string
    {
        // Implementation would cache and refresh WeChat access token
        return cache()->remember('wechat_access_token', 7000, function () {
            $response = Http::get($this->wechatApiUrl . '/token', [
                'grant_type' => 'client_credential',
                'appid' => config('services.wechat.app_id'),
                'secret' => config('services.wechat.app_secret')
            ]);

            return $response->json()['access_token'] ?? '';
        });
    }

    /**
     * Log notification
     */
    protected function logNotification($userId, string $channel, string $type, array $response): void
    {
        \DB::table('notification_logs')->insert([
            'user_id' => $userId,
            'channel' => $channel,
            'type' => $type,
            'response' => json_encode($response),
            'created_at' => now()
        ]);
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotification(array $userIds, string $type, array $data): void
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->sendNotification($user, $type, $data);
        }
    }

    /**
     * Schedule notification
     */
    public function scheduleNotification(User $user, string $type, array $data, \DateTime $sendAt): void
    {
        \App\Jobs\SendScheduledNotification::dispatch($user, $type, $data)
            ->delay($sendAt);
    }

    /**
     * Send RFQ deadline reminders
     */
    public function sendRFQDeadlineReminders(): void
    {
        // Find RFQs expiring in next 2 hours
        $expiringRFQs = RFQ::where('status', 'open')
            ->whereBetween('deadline', [now(), now()->addHours(2)])
            ->whereNull('deadline_reminder_sent_at')
            ->get();

        foreach ($expiringRFQs as $rfq) {
            // Notify buyer
            $this->sendNotification($rfq->buyer, 'rfq_deadline_approaching', [
                'rfq_id' => $rfq->id,
                'title' => $rfq->title,
                'deadline' => $rfq->deadline->format('Y-m-d H:i'),
                'hours_remaining' => $rfq->deadline->diffInHours(now()),
                'quotes_count' => $rfq->quotes()->count()
            ]);

            // Notify vendors who haven't quoted yet
            $vendors = $rfq->getMatchingVendors();
            $quotedVendorIds = $rfq->quotes()->pluck('vendor_id');
            
            foreach ($vendors as $vendor) {
                if (!$quotedVendorIds->contains($vendor->id)) {
                    $this->sendNotification($vendor, 'rfq_deadline_approaching', [
                        'rfq_id' => $rfq->id,
                        'title' => $rfq->title,
                        'deadline' => $rfq->deadline->format('Y-m-d H:i'),
                        'hours_remaining' => $rfq->deadline->diffInHours(now())
                    ]);
                }
            }

            $rfq->update(['deadline_reminder_sent_at' => now()]);
        }
    }

    /**
     * Send quote expiry reminders
     */
    public function sendQuoteExpiryReminders(): void
    {
        // Find quotes expiring in next 24 hours
        $expiringQuotes = Quote::where('status', 'submitted')
            ->whereBetween('expires_at', [now(), now()->addHours(24)])
            ->whereNull('expiry_reminder_sent_at')
            ->get();

        foreach ($expiringQuotes as $quote) {
            // Notify buyer
            $this->sendNotification($quote->rfq->buyer, 'quote_expiring_soon', [
                'quote_id' => $quote->id,
                'vendor_name' => $quote->vendor->name,
                'rfq_title' => $quote->rfq->title,
                'expires_at' => $quote->expires_at->format('Y-m-d H:i'),
                'hours_remaining' => $quote->expires_at->diffInHours(now())
            ]);

            $quote->update(['expiry_reminder_sent_at' => now()]);
        }
    }
}