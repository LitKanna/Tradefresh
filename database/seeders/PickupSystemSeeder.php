<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PickupZone;
use App\Models\PickupBay;
use App\Models\PickupTimeSlot;
use Carbon\Carbon;

class PickupSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Zones (A-F)
        $zones = [
            [
                'code' => 'A',
                'name' => 'Zone A - Main Entrance',
                'description' => 'Primary pickup zone near main entrance with full facilities',
                'location_description' => 'Main entrance, left side after security gate',
                'total_bays' => 20,
                'truck_bays' => 8,
                'van_bays' => 6,
                'car_spots' => 6,
                'has_forklift' => true,
                'has_trolley_area' => true,
                'is_covered' => true,
                'distance_from_entrance' => 50,
                'equipment' => ['forklift', 'pallet_jack', 'trolley', 'hand_truck'],
                'operating_hours' => [
                    'monday' => ['open' => '04:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '04:00', 'close' => '18:00'],
                    'wednesday' => ['open' => '04:00', 'close' => '18:00'],
                    'thursday' => ['open' => '04:00', 'close' => '18:00'],
                    'friday' => ['open' => '04:00', 'close' => '18:00'],
                    'saturday' => ['open' => '05:00', 'close' => '16:00'],
                    'sunday' => ['open' => '06:00', 'close' => '14:00'],
                ],
                'priority_order' => 1,
            ],
            [
                'code' => 'B',
                'name' => 'Zone B - North Side',
                'description' => 'Secondary pickup zone on the north side',
                'location_description' => 'North entrance, near produce warehouses',
                'total_bays' => 15,
                'truck_bays' => 5,
                'van_bays' => 5,
                'car_spots' => 5,
                'has_forklift' => true,
                'has_trolley_area' => true,
                'is_covered' => false,
                'distance_from_entrance' => 150,
                'equipment' => ['forklift', 'trolley'],
                'priority_order' => 2,
            ],
            [
                'code' => 'C',
                'name' => 'Zone C - South Side',
                'description' => 'Pickup zone for smaller vehicles',
                'location_description' => 'South entrance, near parking area',
                'total_bays' => 12,
                'truck_bays' => 2,
                'van_bays' => 4,
                'car_spots' => 6,
                'has_forklift' => false,
                'has_trolley_area' => true,
                'is_covered' => true,
                'distance_from_entrance' => 200,
                'equipment' => ['trolley', 'hand_truck'],
                'priority_order' => 3,
            ],
            [
                'code' => 'D',
                'name' => 'Zone D - Loading Docks',
                'description' => 'Heavy duty loading zone with dock access',
                'location_description' => 'West side, industrial loading area',
                'total_bays' => 10,
                'truck_bays' => 10,
                'van_bays' => 0,
                'car_spots' => 0,
                'has_forklift' => true,
                'has_trolley_area' => false,
                'is_covered' => true,
                'distance_from_entrance' => 300,
                'equipment' => ['forklift', 'pallet_jack', 'dock_leveler'],
                'priority_order' => 4,
            ],
            [
                'code' => 'E',
                'name' => 'Zone E - Express Pickup',
                'description' => 'Quick pickup zone for pre-arranged orders',
                'location_description' => 'Near main office, express lane',
                'total_bays' => 8,
                'truck_bays' => 0,
                'van_bays' => 3,
                'car_spots' => 5,
                'has_forklift' => false,
                'has_trolley_area' => true,
                'is_covered' => true,
                'distance_from_entrance' => 75,
                'equipment' => ['trolley'],
                'priority_order' => 5,
            ],
            [
                'code' => 'F',
                'name' => 'Zone F - Overflow',
                'description' => 'Additional pickup space during peak times',
                'location_description' => 'Far east side, overflow parking',
                'total_bays' => 18,
                'truck_bays' => 6,
                'van_bays' => 6,
                'car_spots' => 6,
                'has_forklift' => false,
                'has_trolley_area' => false,
                'is_covered' => false,
                'distance_from_entrance' => 400,
                'equipment' => [],
                'priority_order' => 6,
            ],
        ];

        foreach ($zones as $zoneData) {
            $zone = PickupZone::create($zoneData);
            
            // Create bays for each zone
            $this->createBaysForZone($zone);
        }

        // Create Time Slots
        $this->createTimeSlots();
    }

    /**
     * Create bays for a zone.
     */
    private function createBaysForZone(PickupZone $zone): void
    {
        $bayNumber = 1;
        
        // Create truck bays
        for ($i = 0; $i < $zone->truck_bays; $i++) {
            PickupBay::create([
                'zone_id' => $zone->id,
                'bay_number' => $zone->code . sprintf('%02d', $bayNumber++),
                'bay_type' => 'truck_bay',
                'capacity' => 1,
                'width' => 4.5,
                'length' => 15,
                'height_clearance' => 4.5,
                'has_dock_leveler' => $zone->code === 'D',
                'has_forklift_access' => $zone->has_forklift,
                'has_power_outlet' => $zone->is_covered,
                'has_lighting' => true,
                'equipment' => $zone->has_forklift ? ['forklift_available'] : [],
                'status' => 'available',
                'is_premium' => $zone->code === 'A' && $i < 3,
                'premium_rate' => $zone->code === 'A' && $i < 3 ? 5.00 : null,
                'priority_order' => $i + 1,
            ]);
        }
        
        // Create van bays
        for ($i = 0; $i < $zone->van_bays; $i++) {
            PickupBay::create([
                'zone_id' => $zone->id,
                'bay_number' => $zone->code . sprintf('%02d', $bayNumber++),
                'bay_type' => 'van_bay',
                'capacity' => 1,
                'width' => 3.5,
                'length' => 8,
                'height_clearance' => 3.5,
                'has_dock_leveler' => false,
                'has_forklift_access' => $zone->has_forklift,
                'has_power_outlet' => $zone->is_covered,
                'has_lighting' => true,
                'status' => 'available',
                'is_premium' => false,
                'priority_order' => $zone->truck_bays + $i + 1,
            ]);
        }
        
        // Create car spots
        for ($i = 0; $i < $zone->car_spots; $i++) {
            PickupBay::create([
                'zone_id' => $zone->id,
                'bay_number' => $zone->code . sprintf('%02d', $bayNumber++),
                'bay_type' => 'car_spot',
                'capacity' => 1,
                'width' => 2.5,
                'length' => 5,
                'height_clearance' => 2.5,
                'has_dock_leveler' => false,
                'has_forklift_access' => false,
                'has_power_outlet' => false,
                'has_lighting' => $zone->is_covered,
                'status' => 'available',
                'is_premium' => false,
                'priority_order' => $zone->truck_bays + $zone->van_bays + $i + 1,
            ]);
        }
    }

    /**
     * Create time slots.
     */
    private function createTimeSlots(): void
    {
        $slots = [
            // Early Morning Premium (4am-6am)
            [
                'start_time' => '04:00:00',
                'end_time' => '04:30:00',
                'slot_name' => 'Early Morning 4:00 AM',
                'slot_type' => 'premium',
                'duration_minutes' => 30,
                'max_bookings' => 20,
                'price_multiplier' => 1.5,
                'allows_exact_time' => false,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 2,
                'max_advance_days' => 30,
                'priority_order' => 1,
            ],
            [
                'start_time' => '04:30:00',
                'end_time' => '05:00:00',
                'slot_name' => 'Early Morning 4:30 AM',
                'slot_type' => 'premium',
                'duration_minutes' => 30,
                'max_bookings' => 20,
                'price_multiplier' => 1.5,
                'allows_exact_time' => false,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 2,
                'max_advance_days' => 30,
                'priority_order' => 2,
            ],
            [
                'start_time' => '05:00:00',
                'end_time' => '05:30:00',
                'slot_name' => 'Early Morning 5:00 AM',
                'slot_type' => 'premium',
                'duration_minutes' => 30,
                'max_bookings' => 25,
                'price_multiplier' => 1.5,
                'allows_exact_time' => false,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 2,
                'max_advance_days' => 30,
                'priority_order' => 3,
            ],
            [
                'start_time' => '05:30:00',
                'end_time' => '06:00:00',
                'slot_name' => 'Early Morning 5:30 AM',
                'slot_type' => 'premium',
                'duration_minutes' => 30,
                'max_bookings' => 25,
                'price_multiplier' => 1.5,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 2,
                'max_advance_days' => 30,
                'priority_order' => 4,
            ],
            
            // Standard Morning (6am-9am)
            [
                'start_time' => '06:00:00',
                'end_time' => '07:00:00',
                'slot_name' => 'Morning 6:00 AM',
                'slot_type' => 'standard',
                'duration_minutes' => 30,
                'max_bookings' => 30,
                'price_multiplier' => 1.0,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 1,
                'max_advance_days' => 30,
                'priority_order' => 5,
            ],
            [
                'start_time' => '07:00:00',
                'end_time' => '08:00:00',
                'slot_name' => 'Morning 7:00 AM',
                'slot_type' => 'standard',
                'duration_minutes' => 30,
                'max_bookings' => 35,
                'price_multiplier' => 1.0,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 1,
                'max_advance_days' => 30,
                'priority_order' => 6,
            ],
            [
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'slot_name' => 'Morning 8:00 AM',
                'slot_type' => 'standard',
                'duration_minutes' => 30,
                'max_bookings' => 35,
                'price_multiplier' => 1.0,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 1,
                'max_advance_days' => 30,
                'priority_order' => 7,
            ],
            
            // Off-Peak (9am-12pm)
            [
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'slot_name' => 'Late Morning 9:00 AM',
                'slot_type' => 'off_peak',
                'duration_minutes' => 30,
                'max_bookings' => 40,
                'price_multiplier' => 0.8,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 0,
                'max_advance_days' => 30,
                'priority_order' => 8,
            ],
            [
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'slot_name' => 'Late Morning 10:00 AM',
                'slot_type' => 'off_peak',
                'duration_minutes' => 30,
                'max_bookings' => 40,
                'price_multiplier' => 0.8,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 0,
                'max_advance_days' => 30,
                'priority_order' => 9,
            ],
            [
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'slot_name' => 'Late Morning 11:00 AM',
                'slot_type' => 'off_peak',
                'duration_minutes' => 30,
                'max_bookings' => 40,
                'price_multiplier' => 0.8,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 0,
                'max_advance_days' => 30,
                'priority_order' => 10,
            ],
            
            // Afternoon slots
            [
                'start_time' => '12:00:00',
                'end_time' => '14:00:00',
                'slot_name' => 'Afternoon',
                'slot_type' => 'off_peak',
                'duration_minutes' => 30,
                'max_bookings' => 30,
                'price_multiplier' => 0.8,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 0,
                'max_advance_days' => 30,
                'priority_order' => 11,
            ],
            [
                'start_time' => '14:00:00',
                'end_time' => '16:00:00',
                'slot_name' => 'Late Afternoon',
                'slot_type' => 'off_peak',
                'duration_minutes' => 30,
                'max_bookings' => 25,
                'price_multiplier' => 0.8,
                'allows_exact_time' => true,
                'buffer_minutes' => 5,
                'advance_booking_hours' => 0,
                'max_advance_days' => 30,
                'priority_order' => 12,
            ],
            
            // At Stand Pickup (flexible time)
            [
                'start_time' => '06:00:00',
                'end_time' => '16:00:00',
                'slot_name' => 'At Stand Pickup (Flexible)',
                'slot_type' => 'standard',
                'duration_minutes' => 60,
                'max_bookings' => 100,
                'price_multiplier' => 0.5,
                'allows_exact_time' => true,
                'buffer_minutes' => 0,
                'requires_approval' => true,
                'advance_booking_hours' => 0,
                'max_advance_days' => 7,
                'priority_order' => 20,
            ],
        ];
        
        foreach ($slots as $slotData) {
            PickupTimeSlot::create(array_merge($slotData, [
                'is_active' => true,
                'available_days' => null, // Available all days by default
            ]));
        }
    }
}