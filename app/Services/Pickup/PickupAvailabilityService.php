<?php

namespace App\Services\Pickup;

use App\Models\PickupBay;
use App\Models\PickupZone;
use App\Models\PickupTimeSlot;
use App\Models\PickupBooking;
use App\Models\BayAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PickupAvailabilityService
{
    /**
     * Get available bays for a specific date and time.
     */
    public function getAvailableBays($date, $startTime, $duration = 30, $vehicleType = null): Collection
    {
        $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');
        
        $query = PickupBay::available()
            ->with(['zone']);
        
        if ($vehicleType) {
            $query->where(function ($q) use ($vehicleType) {
                switch ($vehicleType) {
                    case 'truck':
                        $q->where('bay_type', 'truck_bay');
                        break;
                    case 'van':
                        $q->whereIn('bay_type', ['van_bay', 'truck_bay']);
                        break;
                    case 'car':
                    case 'ute':
                        $q->whereIn('bay_type', ['car_spot', 'van_bay', 'truck_bay']);
                        break;
                }
            });
        }
        
        return $query->get()->filter(function ($bay) use ($date, $startTime, $endTime) {
            return $bay->isAvailableAt($date, $startTime, $endTime);
        });
    }

    /**
     * Get available time slots for a specific date.
     */
    public function getAvailableTimeSlots($date, $bayId = null): Collection
    {
        $slots = PickupTimeSlot::active()
            ->availableOn($date)
            ->byPriority()
            ->get();
        
        return $slots->map(function ($slot) use ($date, $bayId) {
            $bookingCount = $slot->getBookingCount($date);
            $capacity = $slot->max_bookings;
            $remainingCapacity = $slot->getRemainingCapacity($date);
            
            // If specific bay is requested, check its availability
            if ($bayId) {
                $bay = PickupBay::find($bayId);
                $availableInBay = $bay && $bay->isAvailableAt(
                    $date,
                    $slot->start_time,
                    $slot->end_time
                );
            } else {
                $availableInBay = true;
            }
            
            return [
                'id' => $slot->id,
                'name' => $slot->slot_name,
                'display_name' => $slot->getDisplayName(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'type' => $slot->slot_type,
                'type_label' => $slot->getTypeLabel(),
                'duration' => $slot->duration_minutes,
                'price_multiplier' => $slot->price_multiplier,
                'bookings' => $bookingCount,
                'capacity' => $capacity,
                'remaining' => $remainingCapacity,
                'available' => $remainingCapacity > 0 && $availableInBay,
                'allows_exact_time' => $slot->allows_exact_time,
                'time_options' => $slot->allows_exact_time ? $slot->getTimeOptions() : [],
            ];
        });
    }

    /**
     * Get zone availability summary for a date.
     */
    public function getZoneAvailability($date): Collection
    {
        return PickupZone::active()
            ->with(['bays'])
            ->byPriority()
            ->get()
            ->map(function ($zone) use ($date) {
                $totalBays = $zone->bays()->active()->count();
                $availableBays = $zone->bays()
                    ->active()
                    ->get()
                    ->filter(function ($bay) use ($date) {
                        // Check if bay has any availability today
                        return !PickupBooking::where('bay_id', $bay->id)
                            ->where('pickup_date', $date)
                            ->whereIn('status', ['confirmed', 'checked_in'])
                            ->exists();
                    })
                    ->count();
                
                return [
                    'id' => $zone->id,
                    'code' => $zone->code,
                    'name' => $zone->name,
                    'location' => $zone->location_description,
                    'total_bays' => $totalBays,
                    'available_bays' => $availableBays,
                    'occupied_bays' => $totalBays - $availableBays,
                    'utilization_rate' => $totalBays > 0 ? round((($totalBays - $availableBays) / $totalBays) * 100, 2) : 0,
                    'has_forklift' => $zone->has_forklift,
                    'has_trolley' => $zone->has_trolley_area,
                    'is_covered' => $zone->is_covered,
                    'equipment' => $zone->getAvailableEquipment(),
                    'truck_bays' => $zone->truck_bays,
                    'van_bays' => $zone->van_bays,
                    'car_spots' => $zone->car_spots,
                ];
            });
    }

    /**
     * Find next available slot.
     */
    public function findNextAvailableSlot($vehicleType = null, $duration = 30, $preferredZone = null): ?array
    {
        $maxDays = 30;
        $currentDate = now();
        
        for ($i = 0; $i < $maxDays; $i++) {
            $checkDate = $currentDate->copy()->addDays($i);
            $dateString = $checkDate->toDateString();
            
            // Get available time slots for this date
            $timeSlots = $this->getAvailableTimeSlots($dateString);
            
            foreach ($timeSlots as $slot) {
                if (!$slot['available']) {
                    continue;
                }
                
                // Find available bay for this slot
                $availableBays = $this->getAvailableBays(
                    $dateString,
                    $slot['start_time'],
                    $duration,
                    $vehicleType
                );
                
                if ($preferredZone) {
                    $availableBays = $availableBays->filter(function ($bay) use ($preferredZone) {
                        return $bay->zone->code === $preferredZone;
                    });
                }
                
                if ($availableBays->isNotEmpty()) {
                    $bay = $availableBays->first();
                    
                    return [
                        'date' => $dateString,
                        'time' => $slot['start_time'],
                        'end_time' => Carbon::parse($slot['start_time'])->addMinutes($duration)->format('H:i'),
                        'bay' => [
                            'id' => $bay->id,
                            'number' => $bay->bay_number,
                            'type' => $bay->bay_type,
                            'zone' => $bay->zone->code,
                        ],
                        'slot' => [
                            'id' => $slot['id'],
                            'name' => $slot['name'],
                            'type' => $slot['type'],
                        ],
                    ];
                }
            }
        }
        
        return null;
    }

    /**
     * Check if a specific slot is available.
     */
    public function checkSlotAvailability($bayId, $date, $startTime, $duration = 30): array
    {
        $bay = PickupBay::find($bayId);
        
        if (!$bay) {
            return [
                'available' => false,
                'reason' => 'Bay not found',
            ];
        }
        
        if (!$bay->is_active) {
            return [
                'available' => false,
                'reason' => 'Bay is not active',
            ];
        }
        
        if ($bay->status !== 'available') {
            return [
                'available' => false,
                'reason' => 'Bay is ' . $bay->status,
            ];
        }
        
        $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');
        
        if (!$bay->isAvailableAt($date, $startTime, $endTime)) {
            // Check for conflicts
            $conflicts = PickupBooking::where('bay_id', $bayId)
                ->where('pickup_date', $date)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('pickup_time', [$startTime, $endTime])
                        ->orWhereBetween('end_time', [$startTime, $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('pickup_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                        });
                })
                ->get();
            
            if ($conflicts->isNotEmpty()) {
                return [
                    'available' => false,
                    'reason' => 'Time slot conflicts with existing booking',
                    'conflicts' => $conflicts->map(function ($booking) {
                        return [
                            'reference' => $booking->booking_reference,
                            'time' => Carbon::parse($booking->pickup_time)->format('g:i A') . ' - ' . 
                                     Carbon::parse($booking->end_time)->format('g:i A'),
                        ];
                    }),
                ];
            }
            
            // Check for maintenance blocks
            $blocked = BayAvailability::where('bay_id', $bayId)
                ->where('date', $date)
                ->whereIn('status', ['blocked', 'maintenance'])
                ->first();
            
            if ($blocked) {
                return [
                    'available' => false,
                    'reason' => $blocked->blocked_reason ?? 'Bay is blocked for maintenance',
                ];
            }
        }
        
        return [
            'available' => true,
            'bay' => [
                'id' => $bay->id,
                'number' => $bay->bay_number,
                'type' => $bay->bay_type,
                'zone' => $bay->zone->name,
                'features' => $bay->getFeatures(),
            ],
        ];
    }

    /**
     * Get alternative bays when preferred is not available.
     */
    public function getAlternativeBays($preferredBayId, $date, $startTime, $duration = 30): Collection
    {
        $preferredBay = PickupBay::find($preferredBayId);
        
        if (!$preferredBay) {
            return collect();
        }
        
        $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');
        
        // Find similar bays
        return PickupBay::available()
            ->where('id', '!=', $preferredBayId)
            ->where('bay_type', $preferredBay->bay_type)
            ->with(['zone'])
            ->get()
            ->filter(function ($bay) use ($date, $startTime, $endTime) {
                return $bay->isAvailableAt($date, $startTime, $endTime);
            })
            ->sortBy(function ($bay) use ($preferredBay) {
                // Sort by same zone first, then by distance
                if ($bay->zone_id === $preferredBay->zone_id) {
                    return 0;
                }
                return $bay->zone->distance_from_entrance ?? 999;
            })
            ->take(5);
    }

    /**
     * Get peak hours analysis for planning.
     */
    public function getPeakHoursAnalysis($startDate, $endDate): array
    {
        $bookings = PickupBooking::whereBetween('pickup_date', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
            ->get();
        
        $hourlyData = [];
        
        foreach ($bookings as $booking) {
            $hour = Carbon::parse($booking->pickup_time)->format('H:00');
            
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = 0;
            }
            
            $hourlyData[$hour]++;
        }
        
        ksort($hourlyData);
        
        $peakHour = array_search(max($hourlyData), $hourlyData);
        $averagePerHour = count($bookings) > 0 ? round(count($bookings) / count($hourlyData), 2) : 0;
        
        return [
            'hourly_distribution' => $hourlyData,
            'peak_hour' => $peakHour,
            'peak_count' => $hourlyData[$peakHour] ?? 0,
            'average_per_hour' => $averagePerHour,
            'total_bookings' => count($bookings),
        ];
    }
}