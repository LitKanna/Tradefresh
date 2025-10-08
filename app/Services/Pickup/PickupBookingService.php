<?php

namespace App\Services\Pickup;

use App\Models\PickupBooking;
use App\Models\PickupBay;
use App\Models\PickupTimeSlot;
use App\Models\BayAvailability;
use App\Models\RecurringPickupSchedule;
use App\Models\User;
use App\Models\Order;
use App\Jobs\SendPickupNotification;
use App\Services\Pickup\PickupNotificationService;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PickupBookingService
{
    protected $notificationService;
    protected $availabilityService;

    public function __construct(
        PickupNotificationService $notificationService,
        PickupAvailabilityService $availabilityService
    ) {
        $this->notificationService = $notificationService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Create a new pickup booking.
     */
    public function createBooking(array $data): PickupBooking
    {
        return DB::transaction(function () use ($data) {
            // Validate availability
            $availability = $this->availabilityService->checkSlotAvailability(
                $data['bay_id'],
                $data['pickup_date'],
                $data['pickup_time'],
                $data['duration_minutes'] ?? 30
            );

            if (!$availability['available']) {
                throw new \Exception($availability['reason']);
            }

            // Generate unique codes
            $bookingReference = $this->generateBookingReference();
            $confirmationCode = $this->generateConfirmationCode();

            // Calculate end time
            $endTime = Carbon::parse($data['pickup_time'])
                ->addMinutes($data['duration_minutes'] ?? 30)
                ->format('H:i:s');

            // Create booking
            $booking = PickupBooking::create([
                'booking_reference' => $bookingReference,
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'] ?? null,
                'bay_id' => $data['bay_id'],
                'time_slot_id' => $data['time_slot_id'] ?? null,
                'pickup_date' => $data['pickup_date'],
                'pickup_time' => $data['pickup_time'],
                'end_time' => $endTime,
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'booking_type' => $data['booking_type'] ?? 'one_time',
                'status' => $data['auto_confirm'] ?? false ? 'confirmed' : 'pending',
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'vehicle_registration' => $data['vehicle_registration'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_phone' => $data['driver_phone'] ?? null,
                'special_requirements' => $data['special_requirements'] ?? null,
                'items_to_pickup' => $data['items_to_pickup'] ?? null,
                'confirmation_code' => $confirmationCode,
                'booking_fee' => $this->calculateBookingFee($data),
            ]);

            // Generate QR code
            $qrCodePath = $this->generateQRCode($booking);
            $booking->update(['qr_code' => $qrCodePath]);

            // Update bay availability
            $this->updateBayAvailability($booking);

            // Send confirmation notification
            if ($booking->status === 'confirmed') {
                $this->notificationService->sendConfirmation($booking);
            }

            // Schedule reminder
            $this->scheduleReminder($booking);

            return $booking;
        });
    }

    /**
     * Update an existing booking.
     */
    public function updateBooking(PickupBooking $booking, array $data): PickupBooking
    {
        return DB::transaction(function () use ($booking, $data) {
            $originalBayId = $booking->bay_id;
            $originalDate = $booking->pickup_date;
            $originalTime = $booking->pickup_time;

            // Check if time/bay changed
            $timeChanged = isset($data['pickup_date']) && $data['pickup_date'] != $originalDate;
            $timeChanged = $timeChanged || (isset($data['pickup_time']) && $data['pickup_time'] != $originalTime);
            $bayChanged = isset($data['bay_id']) && $data['bay_id'] != $originalBayId;

            if ($timeChanged || $bayChanged) {
                // Validate new availability
                $availability = $this->availabilityService->checkSlotAvailability(
                    $data['bay_id'] ?? $booking->bay_id,
                    $data['pickup_date'] ?? $booking->pickup_date,
                    $data['pickup_time'] ?? $booking->pickup_time,
                    $data['duration_minutes'] ?? $booking->duration_minutes
                );

                if (!$availability['available']) {
                    throw new \Exception($availability['reason']);
                }

                // Release old availability
                BayAvailability::where('booking_id', $booking->id)->delete();
            }

            // Update booking
            $booking->update($data);

            if ($timeChanged || $bayChanged) {
                // Update new availability
                $this->updateBayAvailability($booking);

                // Send change notification
                $this->notificationService->sendBayChange($booking, [
                    'old_bay' => $originalBayId,
                    'old_date' => $originalDate,
                    'old_time' => $originalTime,
                ]);
            }

            return $booking;
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(PickupBooking $booking, string $reason = null, string $cancelledBy = null): bool
    {
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return false;
        }

        DB::transaction(function () use ($booking, $reason, $cancelledBy) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy,
            ]);

            // Release bay availability
            BayAvailability::where('booking_id', $booking->id)->delete();

            // Send cancellation notification
            $this->notificationService->sendCancellation($booking);

            // Process refund if applicable
            if ($booking->is_paid && $booking->booking_fee > 0) {
                $this->processRefund($booking);
            }
        });

        return true;
    }

    /**
     * Check in a booking.
     */
    public function checkIn(PickupBooking $booking, string $confirmationCode = null): array
    {
        // Validate confirmation code if provided
        if ($confirmationCode && $booking->confirmation_code !== $confirmationCode) {
            return [
                'success' => false,
                'message' => 'Invalid confirmation code',
            ];
        }

        // Check if already checked in
        if ($booking->status === 'checked_in') {
            return [
                'success' => false,
                'message' => 'Already checked in',
            ];
        }

        // Check if booking is for today
        if ($booking->pickup_date !== now()->toDateString()) {
            return [
                'success' => false,
                'message' => 'Booking is not for today',
            ];
        }

        // Check if within allowed check-in window (30 minutes before to 30 minutes after)
        $pickupTime = Carbon::parse($booking->pickup_date . ' ' . $booking->pickup_time);
        $now = now();
        $earliestCheckIn = $pickupTime->copy()->subMinutes(30);
        $latestCheckIn = $pickupTime->copy()->addMinutes(30);

        if ($now->lt($earliestCheckIn)) {
            return [
                'success' => false,
                'message' => 'Too early to check in. Please come back closer to your pickup time.',
            ];
        }

        if ($now->gt($latestCheckIn)) {
            $booking->update(['status' => 'no_show']);
            return [
                'success' => false,
                'message' => 'Check-in window has passed. Booking marked as no-show.',
            ];
        }

        // Perform check-in
        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        // Send check-in notification
        $this->notificationService->sendCheckInConfirmation($booking);

        // Notify vendor if order-related
        if ($booking->order_id) {
            $this->notifyVendorOfArrival($booking);
        }

        return [
            'success' => true,
            'message' => 'Successfully checked in',
            'bay' => [
                'number' => $booking->bay->bay_number,
                'zone' => $booking->bay->zone->code,
                'location' => $booking->bay->zone->location_description,
            ],
        ];
    }

    /**
     * Complete a pickup.
     */
    public function completePickup(PickupBooking $booking): bool
    {
        if ($booking->status !== 'checked_in') {
            return false;
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Release bay
        $booking->bay->update(['status' => 'available']);

        // Send completion notification
        $this->notificationService->sendCompletion($booking);

        // Update order status if applicable
        if ($booking->order_id) {
            $booking->order->update(['status' => 'picked_up']);
        }

        return true;
    }

    /**
     * Create recurring booking schedule.
     */
    public function createRecurringSchedule(array $data): RecurringPickupSchedule
    {
        $schedule = RecurringPickupSchedule::create([
            'user_id' => $data['user_id'],
            'bay_id' => $data['bay_id'] ?? null,
            'time_slot_id' => $data['time_slot_id'] ?? null,
            'schedule_name' => $data['schedule_name'],
            'frequency' => $data['frequency'],
            'days_of_week' => $data['days_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'preferred_time' => $data['preferred_time'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_registration' => $data['vehicle_registration'] ?? null,
            'special_requirements' => $data['special_requirements'] ?? null,
            'auto_confirm' => $data['auto_confirm'] ?? false,
            'send_reminders' => $data['send_reminders'] ?? true,
            'reminder_hours' => $data['reminder_hours'] ?? 1,
            'status' => 'active',
            'next_booking_date' => Carbon::parse($data['start_date']),
        ]);

        // Create first booking if requested
        if ($data['create_first_booking'] ?? false) {
            $schedule->createNextBooking();
        }

        return $schedule;
    }

    /**
     * Generate booking reference.
     */
    protected function generateBookingReference(): string
    {
        do {
            $reference = 'PB' . strtoupper(Str::random(8));
        } while (PickupBooking::where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate confirmation code.
     */
    protected function generateConfirmationCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (PickupBooking::where('confirmation_code', $code)->exists());

        return $code;
    }

    /**
     * Generate QR code for booking.
     */
    protected function generateQRCode(PickupBooking $booking): string
    {
        $qrData = [
            'type' => 'pickup_booking',
            'reference' => $booking->booking_reference,
            'code' => $booking->confirmation_code,
            'date' => $booking->pickup_date,
            'time' => $booking->pickup_time,
            'bay' => $booking->bay->bay_number,
            'zone' => $booking->bay->zone->code,
        ];

        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate(json_encode($qrData));

        $filename = 'pickup-qr/' . $booking->booking_reference . '.png';
        Storage::disk('public')->put($filename, $qrCode);

        return $filename;
    }

    /**
     * Update bay availability.
     */
    protected function updateBayAvailability(PickupBooking $booking): void
    {
        BayAvailability::create([
            'bay_id' => $booking->bay_id,
            'date' => $booking->pickup_date,
            'start_time' => $booking->pickup_time,
            'end_time' => $booking->end_time,
            'status' => 'booked',
            'booking_id' => $booking->id,
        ]);
    }

    /**
     * Calculate booking fee.
     */
    protected function calculateBookingFee(array $data): float
    {
        $bay = PickupBay::find($data['bay_id']);
        $timeSlot = isset($data['time_slot_id']) ? PickupTimeSlot::find($data['time_slot_id']) : null;

        $slotType = $timeSlot ? $timeSlot->slot_type : 'standard';
        $duration = $data['duration_minutes'] ?? 30;

        return $bay->calculatePrice($duration, $slotType);
    }

    /**
     * Schedule reminder notification.
     */
    protected function scheduleReminder(PickupBooking $booking): void
    {
        $pickupTime = Carbon::parse($booking->pickup_date . ' ' . $booking->pickup_time);
        $reminderTime = $pickupTime->copy()->subHour();

        if ($reminderTime->gt(now())) {
            SendPickupNotification::dispatch($booking, 'reminder')
                ->delay($reminderTime);
        }
    }

    /**
     * Process refund for cancelled booking.
     */
    protected function processRefund(PickupBooking $booking): void
    {
        // Implementation depends on payment gateway
        // This is a placeholder for refund logic
    }

    /**
     * Notify vendor of buyer arrival.
     */
    protected function notifyVendorOfArrival(PickupBooking $booking): void
    {
        if (!$booking->order) {
            return;
        }

        // Send notification to vendor about buyer arrival
        // Implementation depends on vendor notification preferences
    }

    /**
     * Get booking statistics for a user.
     */
    public function getUserStatistics(User $user): array
    {
        $bookings = PickupBooking::where('user_id', $user->id);

        return [
            'total_bookings' => $bookings->count(),
            'completed_bookings' => $bookings->where('status', 'completed')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'no_shows' => $bookings->where('status', 'no_show')->count(),
            'upcoming_bookings' => $bookings
                ->where('pickup_date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'favorite_bay' => $bookings
                ->select('bay_id', DB::raw('count(*) as count'))
                ->groupBy('bay_id')
                ->orderByDesc('count')
                ->first(),
            'average_duration' => $bookings->avg('duration_minutes'),
            'total_fees_paid' => $bookings->where('is_paid', true)->sum('booking_fee'),
        ];
    }
}