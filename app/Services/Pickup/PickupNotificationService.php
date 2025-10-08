<?php

namespace App\Services\Pickup;

use App\Models\PickupBooking;
use App\Models\PickupNotification;
use App\Models\User;
use App\Mail\PickupConfirmationEmail;
use App\Mail\PickupReminderEmail;
use App\Mail\PickupCancellationEmail;
use App\Notifications\PickupSMSNotification;
use App\Notifications\PickupPushNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class PickupNotificationService
{
    /**
     * Send booking confirmation.
     */
    public function sendConfirmation(PickupBooking $booking): void
    {
        $this->createNotification($booking, 'booking_confirmation', function ($notification) use ($booking) {
            // Email
            if ($this->shouldSendEmail($booking->user, 'booking_confirmation')) {
                $this->sendEmail(
                    $booking,
                    'booking_confirmation',
                    'Your Pickup Bay Booking is Confirmed',
                    $this->getConfirmationMessage($booking)
                );
            }

            // SMS
            if ($this->shouldSendSMS($booking->user, 'booking_confirmation')) {
                $this->sendSMS(
                    $booking,
                    'booking_confirmation',
                    $this->getConfirmationSMS($booking)
                );
            }

            // Push notification
            if ($this->shouldSendPush($booking->user, 'booking_confirmation')) {
                $this->sendPushNotification(
                    $booking,
                    'booking_confirmation',
                    'Pickup Booking Confirmed',
                    $this->getConfirmationPush($booking)
                );
            }
        });
    }

    /**
     * Send pickup reminder.
     */
    public function sendReminder(PickupBooking $booking): void
    {
        // Don't send if already reminded
        if ($booking->reminder_sent_at) {
            return;
        }

        $this->createNotification($booking, 'reminder', function ($notification) use ($booking) {
            // Email
            if ($this->shouldSendEmail($booking->user, 'reminder')) {
                $this->sendEmail(
                    $booking,
                    'reminder',
                    'Pickup Reminder - ' . Carbon::parse($booking->pickup_time)->format('g:i A'),
                    $this->getReminderMessage($booking)
                );
            }

            // SMS
            if ($this->shouldSendSMS($booking->user, 'reminder')) {
                $this->sendSMS(
                    $booking,
                    'reminder',
                    $this->getReminderSMS($booking)
                );
            }

            // Push notification
            if ($this->shouldSendPush($booking->user, 'reminder')) {
                $this->sendPushNotification(
                    $booking,
                    'reminder',
                    'Pickup Reminder',
                    $this->getReminderPush($booking)
                );
            }
        });

        $booking->update(['reminder_sent_at' => now()]);
    }

    /**
     * Send bay change notification.
     */
    public function sendBayChange(PickupBooking $booking, array $oldDetails): void
    {
        $this->createNotification($booking, 'bay_change', function ($notification) use ($booking, $oldDetails) {
            $message = $this->getBayChangeMessage($booking, $oldDetails);

            // Email
            if ($this->shouldSendEmail($booking->user, 'bay_change')) {
                $this->sendEmail(
                    $booking,
                    'bay_change',
                    'Your Pickup Details Have Changed',
                    $message
                );
            }

            // SMS
            if ($this->shouldSendSMS($booking->user, 'bay_change')) {
                $this->sendSMS(
                    $booking,
                    'bay_change',
                    $this->getBayChangeSMS($booking)
                );
            }

            // Push notification
            if ($this->shouldSendPush($booking->user, 'bay_change')) {
                $this->sendPushNotification(
                    $booking,
                    'bay_change',
                    'Pickup Details Changed',
                    $this->getBayChangePush($booking)
                );
            }
        });
    }

    /**
     * Send cancellation notification.
     */
    public function sendCancellation(PickupBooking $booking): void
    {
        $this->createNotification($booking, 'cancellation', function ($notification) use ($booking) {
            // Email
            if ($this->shouldSendEmail($booking->user, 'cancellation')) {
                $this->sendEmail(
                    $booking,
                    'cancellation',
                    'Pickup Booking Cancelled',
                    $this->getCancellationMessage($booking)
                );
            }

            // SMS
            if ($this->shouldSendSMS($booking->user, 'cancellation')) {
                $this->sendSMS(
                    $booking,
                    'cancellation',
                    $this->getCancellationSMS($booking)
                );
            }
        });
    }

    /**
     * Send check-in confirmation.
     */
    public function sendCheckInConfirmation(PickupBooking $booking): void
    {
        $this->createNotification($booking, 'check_in', function ($notification) use ($booking) {
            $message = "You've successfully checked in for pickup at Bay {$booking->bay->bay_number} in Zone {$booking->bay->zone->code}.";

            // In-app notification only
            $this->sendInAppNotification(
                $booking->user,
                'Checked In Successfully',
                $message
            );
        });
    }

    /**
     * Send completion notification.
     */
    public function sendCompletion(PickupBooking $booking): void
    {
        $this->createNotification($booking, 'completed', function ($notification) use ($booking) {
            $message = "Your pickup has been completed. Thank you for using Sydney Markets pickup service!";

            // Email
            if ($this->shouldSendEmail($booking->user, 'completed')) {
                $this->sendEmail(
                    $booking,
                    'completed',
                    'Pickup Completed',
                    $message
                );
            }

            // Request feedback
            $this->requestFeedback($booking);
        });
    }

    /**
     * Create notification record.
     */
    protected function createNotification(PickupBooking $booking, string $type, callable $callback): void
    {
        $notification = PickupNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'type' => $type,
            'channel' => 'multiple',
            'recipient' => $booking->user->email,
            'subject' => $this->getSubject($type),
            'message' => $this->getMessage($booking, $type),
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        try {
            $callback($notification);
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $notification->retry_count + 1,
            ]);
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(PickupBooking $booking, string $type, string $subject, string $message): void
    {
        $emailClass = match($type) {
            'booking_confirmation' => PickupConfirmationEmail::class,
            'reminder' => PickupReminderEmail::class,
            'cancellation' => PickupCancellationEmail::class,
            default => null,
        };

        if ($emailClass) {
            Mail::to($booking->user->email)->send(new $emailClass($booking));
        }

        PickupNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'type' => $type,
            'channel' => 'email',
            'recipient' => $booking->user->email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Send SMS notification.
     */
    protected function sendSMS(PickupBooking $booking, string $type, string $message): void
    {
        if (!$booking->user->phone) {
            return;
        }

        Notification::send($booking->user, new PickupSMSNotification($message));

        PickupNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'type' => $type,
            'channel' => 'sms',
            'recipient' => $booking->user->phone,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Send push notification.
     */
    protected function sendPushNotification(PickupBooking $booking, string $type, string $title, string $message): void
    {
        Notification::send($booking->user, new PickupPushNotification($title, $message, [
            'booking_id' => $booking->id,
            'type' => $type,
        ]));

        PickupNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'type' => $type,
            'channel' => 'push',
            'recipient' => $booking->user->id,
            'subject' => $title,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Send in-app notification.
     */
    protected function sendInAppNotification(User $user, string $title, string $message): void
    {
        $user->notify(new \App\Notifications\InAppNotification($title, $message));
    }

    /**
     * Request feedback after pickup completion.
     */
    protected function requestFeedback(PickupBooking $booking): void
    {
        // Send feedback request email/notification
        $feedbackUrl = route('pickup.feedback', $booking->booking_reference);
        $message = "How was your pickup experience? Please take a moment to rate your experience: {$feedbackUrl}";

        $this->sendEmail(
            $booking,
            'feedback_request',
            'How was your pickup experience?',
            $message
        );
    }

    /**
     * Check if should send email.
     */
    protected function shouldSendEmail(User $user, string $type): bool
    {
        $preferences = $user->notificationPreferences()
            ->where('channel', 'email')
            ->where('type', 'pickup_' . $type)
            ->first();

        return $preferences ? $preferences->enabled : true;
    }

    /**
     * Check if should send SMS.
     */
    protected function shouldSendSMS(User $user, string $type): bool
    {
        if (!$user->phone) {
            return false;
        }

        $preferences = $user->notificationPreferences()
            ->where('channel', 'sms')
            ->where('type', 'pickup_' . $type)
            ->first();

        return $preferences ? $preferences->enabled : false;
    }

    /**
     * Check if should send push notification.
     */
    protected function shouldSendPush(User $user, string $type): bool
    {
        $preferences = $user->notificationPreferences()
            ->where('channel', 'push')
            ->where('type', 'pickup_' . $type)
            ->first();

        return $preferences ? $preferences->enabled : true;
    }

    /**
     * Get notification subject.
     */
    protected function getSubject(string $type): string
    {
        return match($type) {
            'booking_confirmation' => 'Pickup Booking Confirmed',
            'reminder' => 'Pickup Reminder',
            'bay_change' => 'Pickup Details Changed',
            'cancellation' => 'Pickup Cancelled',
            'check_in' => 'Checked In',
            'completed' => 'Pickup Completed',
            default => 'Pickup Notification',
        };
    }

    /**
     * Get notification message.
     */
    protected function getMessage(PickupBooking $booking, string $type): string
    {
        return match($type) {
            'booking_confirmation' => $this->getConfirmationMessage($booking),
            'reminder' => $this->getReminderMessage($booking),
            'cancellation' => $this->getCancellationMessage($booking),
            default => '',
        };
    }

    /**
     * Get confirmation message.
     */
    protected function getConfirmationMessage(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_date . ' ' . $booking->pickup_time);
        
        return "Your pickup booking is confirmed!\n\n" .
               "Reference: {$booking->booking_reference}\n" .
               "Date: {$pickupTime->format('l, F j, Y')}\n" .
               "Time: {$pickupTime->format('g:i A')}\n" .
               "Bay: {$booking->bay->bay_number} (Zone {$booking->bay->zone->code})\n" .
               "Confirmation Code: {$booking->confirmation_code}\n\n" .
               "Please arrive on time. You can check in 30 minutes before your scheduled time.";
    }

    /**
     * Get confirmation SMS.
     */
    protected function getConfirmationSMS(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_time);
        
        return "Pickup confirmed for {$pickupTime->format('g:i A')} on {$booking->pickup_date}. " .
               "Bay {$booking->bay->bay_number}, Zone {$booking->bay->zone->code}. " .
               "Code: {$booking->confirmation_code}";
    }

    /**
     * Get confirmation push message.
     */
    protected function getConfirmationPush(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_time);
        
        return "Pickup booked for {$pickupTime->format('g:i A')} at Bay {$booking->bay->bay_number}";
    }

    /**
     * Get reminder message.
     */
    protected function getReminderMessage(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_date . ' ' . $booking->pickup_time);
        $timeUntil = now()->diffForHumans($pickupTime, ['parts' => 1]);
        
        return "Reminder: You have a pickup scheduled {$timeUntil}.\n\n" .
               "Time: {$pickupTime->format('g:i A')}\n" .
               "Bay: {$booking->bay->bay_number} (Zone {$booking->bay->zone->code})\n" .
               "Confirmation Code: {$booking->confirmation_code}\n\n" .
               "Don't forget to bring your confirmation code!";
    }

    /**
     * Get reminder SMS.
     */
    protected function getReminderSMS(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_time);
        
        return "Reminder: Pickup at {$pickupTime->format('g:i A')} today. " .
               "Bay {$booking->bay->bay_number}, Zone {$booking->bay->zone->code}. " .
               "Code: {$booking->confirmation_code}";
    }

    /**
     * Get reminder push message.
     */
    protected function getReminderPush(PickupBooking $booking): string
    {
        $pickupTime = Carbon::parse($booking->pickup_time);
        
        return "Pickup in 1 hour at {$pickupTime->format('g:i A')}, Bay {$booking->bay->bay_number}";
    }

    /**
     * Get bay change message.
     */
    protected function getBayChangeMessage(PickupBooking $booking, array $oldDetails): string
    {
        return "Your pickup details have been updated:\n\n" .
               "New Bay: {$booking->bay->bay_number} (Zone {$booking->bay->zone->code})\n" .
               "Date: {$booking->pickup_date}\n" .
               "Time: " . Carbon::parse($booking->pickup_time)->format('g:i A') . "\n\n" .
               "Your confirmation code remains: {$booking->confirmation_code}";
    }

    /**
     * Get bay change SMS.
     */
    protected function getBayChangeSMS(PickupBooking $booking): string
    {
        return "Pickup changed to Bay {$booking->bay->bay_number}, Zone {$booking->bay->zone->code} " .
               "at " . Carbon::parse($booking->pickup_time)->format('g:i A') . " on {$booking->pickup_date}";
    }

    /**
     * Get bay change push message.
     */
    protected function getBayChangePush(PickupBooking $booking): string
    {
        return "Pickup moved to Bay {$booking->bay->bay_number} at " . 
               Carbon::parse($booking->pickup_time)->format('g:i A');
    }

    /**
     * Get cancellation message.
     */
    protected function getCancellationMessage(PickupBooking $booking): string
    {
        $message = "Your pickup booking ({$booking->booking_reference}) has been cancelled.\n\n";
        
        if ($booking->cancellation_reason) {
            $message .= "Reason: {$booking->cancellation_reason}\n\n";
        }
        
        if ($booking->is_paid && $booking->booking_fee > 0) {
            $message .= "A refund of $" . number_format($booking->booking_fee, 2) . " will be processed within 3-5 business days.\n\n";
        }
        
        $message .= "To book a new pickup, please visit our website or app.";
        
        return $message;
    }

    /**
     * Get cancellation SMS.
     */
    protected function getCancellationSMS(PickupBooking $booking): string
    {
        return "Your pickup booking {$booking->booking_reference} has been cancelled. " .
               ($booking->is_paid ? "Refund will be processed." : "");
    }
}