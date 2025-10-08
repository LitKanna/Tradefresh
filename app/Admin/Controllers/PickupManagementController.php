<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupBay;
use App\Models\PickupZone;
use App\Models\PickupBooking;
use App\Models\PickupTimeSlot;
use App\Models\BayAvailability;
use App\Services\Pickup\PickupAvailabilityService;
use App\Services\Pickup\PickupBookingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PickupManagementController extends Controller
{
    protected $availabilityService;
    protected $bookingService;

    public function __construct(
        PickupAvailabilityService $availabilityService,
        PickupBookingService $bookingService
    ) {
        $this->availabilityService = $availabilityService;
        $this->bookingService = $bookingService;
    }

    /**
     * Display pickup management dashboard.
     */
    public function index()
    {
        $today = now()->toDateString();
        
        // Get today's statistics
        $todayStats = [
            'total_bookings' => PickupBooking::where('pickup_date', $today)->count(),
            'confirmed' => PickupBooking::where('pickup_date', $today)->where('status', 'confirmed')->count(),
            'checked_in' => PickupBooking::where('pickup_date', $today)->where('status', 'checked_in')->count(),
            'completed' => PickupBooking::where('pickup_date', $today)->where('status', 'completed')->count(),
            'no_shows' => PickupBooking::where('pickup_date', $today)->where('status', 'no_show')->count(),
        ];
        
        // Get zone utilization
        $zoneUtilization = $this->availabilityService->getZoneAvailability($today);
        
        // Get upcoming bookings
        $upcomingBookings = PickupBooking::where('pickup_date', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['user', 'bay.zone', 'order'])
            ->orderBy('pickup_time')
            ->get();
        
        // Get peak hours analysis
        $peakHours = $this->availabilityService->getPeakHoursAnalysis(
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        );
        
        return view('admin.pickup.dashboard', compact(
            'todayStats',
            'zoneUtilization',
            'upcomingBookings',
            'peakHours'
        ));
    }

    /**
     * Display bay management page.
     */
    public function bays(Request $request)
    {
        $query = PickupBay::with('zone');
        
        if ($request->has('zone')) {
            $query->whereHas('zone', function ($q) use ($request) {
                $q->where('code', $request->zone);
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $bays = $query->paginate(20);
        $zones = PickupZone::all();
        
        return view('admin.pickup.bays', compact('bays', 'zones'));
    }

    /**
     * Update bay status.
     */
    public function updateBayStatus(Request $request, PickupBay $bay)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance,closed',
            'reason' => 'nullable|string|max:255',
        ]);
        
        $bay->update(['status' => $validated['status']]);
        
        // If maintenance or closed, block future availability
        if (in_array($validated['status'], ['maintenance', 'closed'])) {
            BayAvailability::create([
                'bay_id' => $bay->id,
                'date' => now()->toDateString(),
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'status' => $validated['status'] === 'maintenance' ? 'maintenance' : 'blocked',
                'blocked_reason' => $validated['reason'] ?? 'Bay ' . $validated['status'],
            ]);
        }
        
        return redirect()->back()->with('success', 'Bay status updated successfully');
    }

    /**
     * Display today's pickups.
     */
    public function todayPickups(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        
        $bookings = PickupBooking::where('pickup_date', $date)
            ->with(['user', 'bay.zone', 'order.vendor'])
            ->orderBy('pickup_time')
            ->get()
            ->groupBy('status');
        
        return view('admin.pickup.today', compact('bookings', 'date'));
    }

    /**
     * Reassign bay for a booking.
     */
    public function reassignBay(Request $request, PickupBooking $booking)
    {
        $validated = $request->validate([
            'new_bay_id' => 'required|exists:pickup_bays,id',
            'notify_user' => 'boolean',
        ]);
        
        $oldBay = $booking->bay_id;
        
        try {
            $booking = $this->bookingService->updateBooking($booking, [
                'bay_id' => $validated['new_bay_id'],
            ]);
            
            if ($validated['notify_user'] ?? true) {
                // Notification will be sent automatically by the service
            }
            
            return redirect()->back()->with('success', 'Bay reassigned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reassign bay: ' . $e->getMessage());
        }
    }

    /**
     * Generate pickup report.
     */
    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:summary,detailed,zone,bay,user',
        ]);
        
        $bookings = PickupBooking::whereBetween('pickup_date', [$validated['start_date'], $validated['end_date']])
            ->with(['user', 'bay.zone', 'order']);
        
        switch ($validated['report_type']) {
            case 'summary':
                $data = $this->generateSummaryReport($bookings);
                break;
            
            case 'detailed':
                $data = $this->generateDetailedReport($bookings);
                break;
            
            case 'zone':
                $data = $this->generateZoneReport($bookings);
                break;
            
            case 'bay':
                $data = $this->generateBayReport($bookings);
                break;
            
            case 'user':
                $data = $this->generateUserReport($bookings);
                break;
        }
        
        return view('admin.pickup.report', [
            'data' => $data,
            'type' => $validated['report_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);
    }

    /**
     * Manage time slots.
     */
    public function timeSlots()
    {
        $timeSlots = PickupTimeSlot::orderBy('start_time')->get();
        
        return view('admin.pickup.time-slots', compact('timeSlots'));
    }

    /**
     * Create or update time slot.
     */
    public function saveTimeSlot(Request $request, PickupTimeSlot $timeSlot = null)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_name' => 'required|string|max:100',
            'slot_type' => 'required|in:premium,standard,off_peak',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_bookings' => 'required|integer|min:1',
            'price_multiplier' => 'required|numeric|min:0.1|max:5',
            'available_days' => 'nullable|array',
            'allows_exact_time' => 'boolean',
            'requires_approval' => 'boolean',
            'advance_booking_hours' => 'required|integer|min:0',
            'max_advance_days' => 'required|integer|min:1|max:365',
            'is_active' => 'boolean',
        ]);
        
        if ($timeSlot) {
            $timeSlot->update($validated);
        } else {
            $timeSlot = PickupTimeSlot::create($validated);
        }
        
        return redirect()->route('admin.pickup.time-slots')
            ->with('success', 'Time slot saved successfully');
    }

    /**
     * Block dates for maintenance.
     */
    public function blockDates(Request $request)
    {
        $validated = $request->validate([
            'zone_id' => 'nullable|exists:pickup_zones,id',
            'bay_ids' => 'nullable|array',
            'bay_ids.*' => 'exists:pickup_bays,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:255',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $startTime = $validated['start_time'] ?? '00:00';
        $endTime = $validated['end_time'] ?? '23:59';
        
        // Get affected bays
        $bays = collect();
        
        if ($validated['zone_id']) {
            $bays = PickupBay::where('zone_id', $validated['zone_id'])->get();
        } elseif (!empty($validated['bay_ids'])) {
            $bays = PickupBay::whereIn('id', $validated['bay_ids'])->get();
        }
        
        // Create availability blocks
        foreach ($bays as $bay) {
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                BayAvailability::create([
                    'bay_id' => $bay->id,
                    'date' => $currentDate->toDateString(),
                    'start_time' => $startTime . ':00',
                    'end_time' => $endTime . ':59',
                    'status' => 'maintenance',
                    'blocked_reason' => $validated['reason'],
                ]);
                
                // Cancel affected bookings
                $affectedBookings = PickupBooking::where('bay_id', $bay->id)
                    ->where('pickup_date', $currentDate->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('pickup_time', [$startTime, $endTime])
                          ->orWhereBetween('end_time', [$startTime, $endTime]);
                    })
                    ->get();
                
                foreach ($affectedBookings as $booking) {
                    $this->bookingService->cancelBooking(
                        $booking,
                        'Bay maintenance: ' . $validated['reason'],
                        'System'
                    );
                }
                
                $currentDate->addDay();
            }
        }
        
        return redirect()->back()->with('success', 'Dates blocked successfully');
    }

    /**
     * Check in a booking manually.
     */
    public function checkIn(PickupBooking $booking)
    {
        $result = $this->bookingService->checkIn($booking);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Complete a pickup manually.
     */
    public function completePickup(PickupBooking $booking)
    {
        if ($this->bookingService->completePickup($booking)) {
            return redirect()->back()->with('success', 'Pickup marked as completed');
        }
        
        return redirect()->back()->with('error', 'Failed to complete pickup');
    }

    /**
     * Generate summary report data.
     */
    private function generateSummaryReport($bookingsQuery)
    {
        $bookings = $bookingsQuery->get();
        
        return [
            'total_bookings' => $bookings->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'no_shows' => $bookings->where('status', 'no_show')->count(),
            'total_revenue' => $bookings->where('is_paid', true)->sum('booking_fee'),
            'average_duration' => $bookings->avg('duration_minutes'),
            'busiest_day' => $bookings->groupBy('pickup_date')->map->count()->sortDesc()->keys()->first(),
            'most_used_bay' => $bookings->groupBy('bay_id')->map->count()->sortDesc()->keys()->first(),
        ];
    }

    /**
     * Generate detailed report data.
     */
    private function generateDetailedReport($bookingsQuery)
    {
        return $bookingsQuery->orderBy('pickup_date')->orderBy('pickup_time')->get();
    }

    /**
     * Generate zone report data.
     */
    private function generateZoneReport($bookingsQuery)
    {
        $bookings = $bookingsQuery->get();
        
        return PickupZone::all()->map(function ($zone) use ($bookings) {
            $zoneBookings = $bookings->filter(function ($booking) use ($zone) {
                return $booking->bay->zone_id === $zone->id;
            });
            
            return [
                'zone' => $zone,
                'total_bookings' => $zoneBookings->count(),
                'completed' => $zoneBookings->where('status', 'completed')->count(),
                'revenue' => $zoneBookings->where('is_paid', true)->sum('booking_fee'),
                'utilization_rate' => $zoneBookings->count() > 0 
                    ? round(($zoneBookings->count() / ($zone->total_bays * 30)) * 100, 2)
                    : 0,
            ];
        });
    }

    /**
     * Generate bay report data.
     */
    private function generateBayReport($bookingsQuery)
    {
        $bookings = $bookingsQuery->get();
        
        return PickupBay::all()->map(function ($bay) use ($bookings) {
            $bayBookings = $bookings->where('bay_id', $bay->id);
            
            return [
                'bay' => $bay,
                'total_bookings' => $bayBookings->count(),
                'completed' => $bayBookings->where('status', 'completed')->count(),
                'revenue' => $bayBookings->where('is_paid', true)->sum('booking_fee'),
                'average_duration' => $bayBookings->avg('duration_minutes'),
            ];
        })->filter(function ($item) {
            return $item['total_bookings'] > 0;
        });
    }

    /**
     * Generate user report data.
     */
    private function generateUserReport($bookingsQuery)
    {
        return $bookingsQuery->get()
            ->groupBy('user_id')
            ->map(function ($userBookings) {
                $user = $userBookings->first()->user;
                
                return [
                    'user' => $user,
                    'total_bookings' => $userBookings->count(),
                    'completed' => $userBookings->where('status', 'completed')->count(),
                    'cancelled' => $userBookings->where('status', 'cancelled')->count(),
                    'no_shows' => $userBookings->where('status', 'no_show')->count(),
                    'total_spent' => $userBookings->where('is_paid', true)->sum('booking_fee'),
                ];
            });
    }
}