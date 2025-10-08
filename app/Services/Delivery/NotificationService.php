<?php

namespace App\Services\Delivery;

use App\Models\Delivery;
use App\Models\Driver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

class NotificationService
{
    protected ?TwilioClient $twilioClient;
    protected string $twilioFrom;
    protected string $whatsappFrom;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('services.notifications.enabled', true);
        
        if ($this->enabled && config('services.twilio.sid')) {
            try {
                $this->twilioClient = new TwilioClient(
                    config('services.twilio.sid'),
                    config('services.twilio.token')
                );
                $this->twilioFrom = config('services.twilio.from');
                $this->whatsappFrom = 'whatsapp:' . config('services.twilio.whatsapp_from');
            } catch (\Exception $e) {
                Log::error('Failed to initialize Twilio client: ' . $e->getMessage());
                $this->twilioClient = null;
            }
        } else {
            $this->twilioClient = null;
        }
    }

    public function sendDeliveryCreated(Delivery $delivery): void
    {
        if (!$this->shouldSendNotification($delivery, 'created')) {
            return;
        }

        $message = "Your order #{$delivery->order->order_number} has been scheduled for delivery. ";
        $message .= "Track it here: " . $delivery->getTrackingUrl();
        
        if ($delivery->scheduled_date) {
            $message .= " Scheduled for: " . $delivery->scheduled_date->format('M d, g:i A');
        }

        $this->sendMultiChannel($delivery, 'created', $message);
    }

    public function sendDriverAssigned(Delivery $delivery): void
    {
        if (!$this->shouldSendNotification($delivery, 'assigned')) {
            return;
        }

        $driver = $delivery->driver;
        $message = "Driver {$driver->first_name} has been assigned to your delivery. ";
        $message .= "Vehicle: {$driver->vehicle_type}. ";
        
        if ($delivery->parking_location) {
            $message .= "Pickup location: {$delivery->parking_location->full_location}. ";
        }
        
        $message .= "Track delivery: " . $delivery->getTrackingUrl();

        $this->sendMultiChannel($delivery, 'assigned', $message);
    }

    public function sendStatusUpdate(Delivery $delivery, string $oldStatus, string $newStatus): void
    {
        $statusMessages = [
            'picked_up' => "Your order has been picked up and is on the way!",
            'in_transit' => "Your delivery is in transit.",
            'delivered' => "Your order has been delivered successfully. Thank you!",
            'failed' => "We couldn't complete your delivery. We'll contact you shortly.",
            'cancelled' => "Your delivery has been cancelled."
        ];

        $message = $statusMessages[$newStatus] ?? "Your delivery status has been updated to: {$newStatus}";
        
        if ($newStatus === 'picked_up' && $delivery->driver) {
            $eta = $delivery->getEstimatedArrival();
            if ($eta) {
                $message .= " ETA: " . $eta->format('g:i A');
            }
        }

        if (in_array($newStatus, ['picked_up', 'in_transit'])) {
            $message .= " Track: " . $delivery->getTrackingUrl();
        }

        $this->sendMultiChannel($delivery, $newStatus, $message);
    }

    public function sendNearArrival(Delivery $delivery): void
    {
        if (!$this->shouldSendNotification($delivery, 'near_arrival')) {
            return;
        }

        $message = "Your delivery will arrive in approximately 10 minutes. ";
        $message .= "Order: #{$delivery->order->order_number}. ";
        
        if ($delivery->driver) {
            $message .= "Driver: {$delivery->driver->first_name} ({$delivery->driver->phone})";
        }

        $this->sendMultiChannel($delivery, 'near_arrival', $message);
    }

    public function sendDriverArrived(Delivery $delivery): void
    {
        if (!$this->shouldSendNotification($delivery, 'arrived')) {
            return;
        }

        $message = "Your delivery driver has arrived! ";
        $message .= "Order: #{$delivery->order->order_number}. ";
        
        if ($delivery->proof_type === 'contactless') {
            $message .= "This is a contactless delivery. The driver will leave your order at the designated location.";
        } else {
            $message .= "Please be ready to receive your order.";
        }

        $this->sendMultiChannel($delivery, 'arrived', $message);
    }

    public function sendDelayNotification(Delivery $delivery, int $minutes, string $reason): void
    {
        if (!$this->shouldSendNotification($delivery, 'delay')) {
            return;
        }

        $message = "Your delivery has been delayed by approximately {$minutes} minutes";
        
        if ($reason) {
            $message .= " due to {$reason}";
        }
        
        $message .= ". We apologize for the inconvenience.";
        
        $newEta = $delivery->getEstimatedArrival();
        if ($newEta) {
            $message .= " New ETA: " . $newEta->format('g:i A');
        }

        $this->sendMultiChannel($delivery, 'delay', $message);
    }

    public function sendDriverArrivingAtPickup(Delivery $delivery): void
    {
        // Internal notification - could be sent to warehouse staff
        Log::info("Driver arriving at pickup for delivery {$delivery->tracking_code}");
        
        // You could send notification to warehouse staff here
        if ($delivery->parking_location) {
            $message = "Driver arriving at {$delivery->parking_location->full_location} for order #{$delivery->order->order_number}";
            // Send to warehouse notification channel
        }
    }

    public function sendTemperatureAlert(Delivery $delivery, float $temperature): void
    {
        $message = "TEMPERATURE ALERT: Delivery {$delivery->tracking_code} ";
        $message .= "recorded temperature of {$temperature}°C ";
        $message .= "(acceptable range: {$delivery->min_temperature}°C - {$delivery->max_temperature}°C)";

        // Send to driver
        if ($delivery->driver) {
            $this->sendSMS($delivery->driver->phone, $message);
        }

        // Send to customer if critical
        if (abs($temperature - $delivery->min_temperature) > 5 || abs($temperature - $delivery->max_temperature) > 5) {
            $customerMessage = "Temperature issue detected with your cold chain delivery. ";
            $customerMessage .= "We're taking immediate action to resolve this.";
            $this->sendMultiChannel($delivery, 'temperature_alert', $customerMessage);
        }

        // Log for internal review
        Log::warning($message);
    }

    protected function sendMultiChannel(Delivery $delivery, string $event, string $message): void
    {
        $sent = [];

        // SMS
        if ($delivery->sms_enabled && $delivery->contact_phone) {
            if ($this->sendSMS($delivery->contact_phone, $message)) {
                $sent[] = 'sms';
            }
        }

        // WhatsApp
        if ($delivery->whatsapp_enabled && $delivery->contact_phone) {
            if ($this->sendWhatsApp($delivery->contact_phone, $message)) {
                $sent[] = 'whatsapp';
            }
        }

        // Email
        if ($delivery->email_enabled && $delivery->order->customer->email) {
            if ($this->sendEmail($delivery, $event, $message)) {
                $sent[] = 'email';
            }
        }

        // Log notification
        $delivery->sendNotification($event, $message);
    }

    protected function sendSMS(string $to, string $message): bool
    {
        if (!$this->enabled || !$this->twilioClient) {
            Log::info("SMS (simulated): To {$to} - {$message}");
            return true;
        }

        try {
            $this->twilioClient->messages->create(
                $this->formatPhoneNumber($to),
                [
                    'from' => $this->twilioFrom,
                    'body' => $message
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS: " . $e->getMessage());
            return false;
        }
    }

    protected function sendWhatsApp(string $to, string $message): bool
    {
        if (!$this->enabled || !$this->twilioClient) {
            Log::info("WhatsApp (simulated): To {$to} - {$message}");
            return true;
        }

        try {
            $this->twilioClient->messages->create(
                'whatsapp:' . $this->formatPhoneNumber($to),
                [
                    'from' => $this->whatsappFrom,
                    'body' => $message
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp: " . $e->getMessage());
            return false;
        }
    }

    protected function sendEmail(Delivery $delivery, string $event, string $message): bool
    {
        if (!$this->enabled) {
            Log::info("Email (simulated): {$event} - {$message}");
            return true;
        }

        try {
            Mail::send('emails.delivery-notification', [
                'delivery' => $delivery,
                'event' => $event,
                'message' => $message,
                'trackingUrl' => $delivery->getTrackingUrl()
            ], function ($mail) use ($delivery, $event) {
                $mail->to($delivery->order->customer->email)
                    ->subject($this->getEmailSubject($event, $delivery));
            });
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage());
            return false;
        }
    }

    protected function getEmailSubject(string $event, Delivery $delivery): string
    {
        $subjects = [
            'created' => "Delivery Scheduled - Order #{$delivery->order->order_number}",
            'assigned' => "Driver Assigned - Order #{$delivery->order->order_number}",
            'picked_up' => "Order Picked Up - #{$delivery->order->order_number}",
            'in_transit' => "Delivery In Transit - Order #{$delivery->order->order_number}",
            'near_arrival' => "Delivery Arriving Soon - Order #{$delivery->order->order_number}",
            'arrived' => "Driver Has Arrived - Order #{$delivery->order->order_number}",
            'delivered' => "Delivery Complete - Order #{$delivery->order->order_number}",
            'delay' => "Delivery Delayed - Order #{$delivery->order->order_number}",
            'failed' => "Delivery Issue - Order #{$delivery->order->order_number}",
            'temperature_alert' => "Temperature Alert - Order #{$delivery->order->order_number}"
        ];

        return $subjects[$event] ?? "Delivery Update - Order #{$delivery->order->order_number}";
    }

    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add Australian country code if not present
        if (strlen($phone) === 9 && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }

        if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '61' . substr($phone, 1);
        }

        if (strlen($phone) === 11 && substr($phone, 0, 2) !== '61') {
            $phone = '61' . $phone;
        }

        return '+' . $phone;
    }

    protected function shouldSendNotification(Delivery $delivery, string $event): bool
    {
        if (!$this->enabled) {
            return true; // Always log in development
        }

        $settings = $delivery->notification_settings ?? [];
        
        $eventMap = [
            'created' => 'on_create',
            'assigned' => 'on_assign',
            'picked_up' => 'on_pickup',
            'in_transit' => 'on_way',
            'near_arrival' => 'near_arrival',
            'arrived' => 'on_arrival',
            'delivered' => 'delivered',
            'delay' => 'issues',
            'temperature_alert' => 'issues'
        ];

        $settingKey = $eventMap[$event] ?? $event;
        
        return $settings[$settingKey] ?? true;
    }

    public function sendDriverNotification(Driver $driver, string $message, string $type = 'info'): bool
    {
        if (!$driver->phone) {
            return false;
        }

        $formattedMessage = "[Sydney Markets] {$message}";
        
        return $this->sendSMS($driver->phone, $formattedMessage);
    }

    public function sendBulkNotification(array $deliveries, string $message): array
    {
        $results = [];
        
        foreach ($deliveries as $delivery) {
            $results[$delivery->id] = [
                'success' => false,
                'channels' => []
            ];

            try {
                $this->sendMultiChannel($delivery, 'bulk', $message);
                $results[$delivery->id]['success'] = true;
            } catch (\Exception $e) {
                Log::error("Failed to send bulk notification to delivery {$delivery->id}: " . $e->getMessage());
            }
        }

        return $results;
    }
}