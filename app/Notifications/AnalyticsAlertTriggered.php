<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AnalyticsAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alertData;
    protected $preferredChannel;

    public function __construct($alertData, $preferredChannel = null)
    {
        $this->alertData = $alertData;
        $this->preferredChannel = $preferredChannel;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];

        if ($this->preferredChannel) {
            return [$this->getChannelClass($this->preferredChannel)];
        }

        // Default channels based on alert configuration
        $alertChannels = $this->alertData['alert']->notification_channels ?? ['email'];

        foreach ($alertChannels as $channel) {
            $channels[] = $this->getChannelClass($channel);
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $alert = $this->alertData['alert'];
        $currentValue = $this->alertData['current_value'];
        $threshold = $this->alertData['threshold'];
        $metricName = $this->alertData['metric_name'];
        
        $subject = "Alert: {$alert->name}";
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Alert Triggered: {$alert->name}")
            ->line("Your analytics alert has been triggered.")
            ->line("**Alert Details:**")
            ->line("- Metric: {$metricName}")
            ->line("- Current Value: " . $this->formatValue($currentValue, $alert->metric))
            ->line("- Threshold: " . $this->formatValue($threshold, $alert->metric))
            ->line("- Priority: {$this->alertData['priority']}")
            ->line("- Triggered: {$this->alertData['triggered_at']->format('M j, Y H:i:s')}")
            ->action('View Alert Details', route('buyer.analytics.alerts.show', $alert->uuid))
            ->line('You can manage your alerts in the Analytics Dashboard.');

        // Add context based on alert type
        if ($alert->type === 'budget' && $currentValue > $threshold) {
            $variance = $currentValue - $threshold;
            $message->line("**Budget Variance:** " . $this->formatValue($variance, $alert->metric) . " over budget");
        }

        if ($alert->type === 'performance' && $currentValue < $threshold) {
            $performance = (($threshold - $currentValue) / $threshold) * 100;
            $message->line("**Performance Impact:** " . round($performance, 1) . "% below target");
        }

        return $message;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        $alert = $this->alertData['alert'];
        
        return [
            'type' => 'analytics_alert',
            'alert_uuid' => $alert->uuid,
            'alert_name' => $alert->name,
            'alert_type' => $alert->getFormattedType(),
            'metric_name' => $this->alertData['metric_name'],
            'current_value' => $this->alertData['current_value'],
            'threshold' => $this->alertData['threshold'],
            'priority' => $this->alertData['priority'],
            'priority_color' => $alert->getPriorityColor(),
            'triggered_at' => $this->alertData['triggered_at'],
            'message' => $this->generateShortMessage(),
            'action_url' => route('buyer.analytics.alerts.show', $alert->uuid)
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $alert = $this->alertData['alert'];
        $metricName = $this->alertData['metric_name'];
        $currentValue = $this->formatValue($this->alertData['current_value'], $alert->metric);
        
        return "Analytics Alert: {$alert->name} - {$metricName} is now {$currentValue}. Check your dashboard for details.";
    }

    /**
     * Get the push notification representation.
     */
    public function toPush($notifiable)
    {
        $alert = $this->alertData['alert'];
        
        return [
            'title' => "Alert: {$alert->name}",
            'body' => $this->generateShortMessage(),
            'data' => [
                'alert_uuid' => $alert->uuid,
                'type' => 'analytics_alert',
                'priority' => $alert->priority,
                'action_url' => route('buyer.analytics.alerts.show', $alert->uuid)
            ]
        ];
    }

    /**
     * Get channel class name
     */
    protected function getChannelClass($channel)
    {
        return match($channel) {
            'email' => 'mail',
            'sms' => 'nexmo', // or your SMS channel
            'push' => 'broadcast', // or your push notification channel
            'dashboard' => 'database',
            default => 'mail'
        };
    }

    /**
     * Format value based on metric type
     */
    protected function formatValue($value, $metric)
    {
        if (str_contains($metric, 'spending') || str_contains($metric, 'cost')) {
            return '$' . number_format($value, 2);
        }

        if (str_contains($metric, 'rate') || str_contains($metric, 'performance')) {
            return number_format($value, 1) . '%';
        }

        if (str_contains($metric, 'count')) {
            return number_format($value);
        }

        return number_format($value, 2);
    }

    /**
     * Generate short message for push/SMS
     */
    protected function generateShortMessage()
    {
        $alert = $this->alertData['alert'];
        $metricName = $this->alertData['metric_name'];
        $currentValue = $this->formatValue($this->alertData['current_value'], $alert->metric);
        $threshold = $this->formatValue($this->alertData['threshold'], $alert->metric);
        
        $operator = $alert->getOperator();
        $comparison = match($operator) {
            'greater_than' => 'exceeds',
            'less_than' => 'below',
            'greater_than_or_equal' => 'at or above',
            'less_than_or_equal' => 'at or below',
            default => 'different from'
        };
        
        return "{$metricName} ({$currentValue}) is {$comparison} threshold ({$threshold})";
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend($notifiable)
    {
        // Check if user has notification preferences that would block this
        $alert = $this->alertData['alert'];
        
        // Check if alert is still active
        if (!$alert->is_active) {
            return false;
        }
        
        // Check if user has disabled notifications for this priority level
        $userPreferences = $notifiable->notification_preferences ?? [];
        $priorityKey = 'alert_priority_' . $alert->priority;
        
        if (isset($userPreferences[$priorityKey]) && !$userPreferences[$priorityKey]) {
            return false;
        }
        
        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        \Log::error('Analytics alert notification failed', [
            'alert_uuid' => $this->alertData['alert']->uuid,
            'error' => $exception->getMessage()
        ]);
    }
}