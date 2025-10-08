<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\Buyer;
use App\Models\BusinessContact;
use App\Models\BusinessUser;
use App\Models\BusinessUserRole;
use App\Models\PickupBay;
use App\Models\PickupTimeSlot;
use App\Models\PickupDetail;
use App\Models\PickupBooking;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SydneyMarketsSeeder extends Seeder
{
    /**
     * Run the database seeds for Sydney Markets system.
     */
    public function run(): void
    {
        // Create system roles first
        $this->createRoles();

        // Create pickup infrastructure
        $this->createPickupBays();
        $this->createTimeSlots();

        // Create sample businesses and users
        $this->createBusinessesWithUsers();

        // Create sample pickup bookings
        $this->createSampleBookings();
    }

    /**
     * Create business user roles
     */
    private function createRoles(): void
    {
        $this->command->info('Creating business user roles...');
        
        BusinessUserRole::createSystemRoles();
        
        // Create custom roles
        BusinessUserRole::create([
            'role_name' => 'Warehouse Manager',
            'role_slug' => 'warehouse_manager',
            'description' => 'Manages warehouse operations and pickups',
            'hierarchy_level' => 50,
            'is_system_role' => false,
            'color_badge' => '#059669',
            'can_place_orders' => true,
            'can_view_orders' => true,
            'can_modify_orders' => true,
            'can_manage_vehicles' => true,
            'can_manage_pickup_preferences' => true,
            'can_view_reports' => true,
            'max_order_value' => 10000,
            'display_order' => 7,
        ]);

        $this->command->info('Roles created successfully.');
    }

    /**
     * Create pickup bays
     */
    private function createPickupBays(): void
    {
        $this->command->info('Creating pickup bays...');

        $bays = [
            // North Zone - Loading Docks
            [
                'bay_number' => 'N-01',
                'bay_name' => 'North Loading Dock 1',
                'zone' => 'north',
                'area_type' => 'loading_dock',
                'building' => 'Building A',
                'max_vehicle_height' => 4.5,
                'max_vehicle_length' => 12,
                'vehicle_type_restriction' => 'large_truck',
                'has_forklift' => true,
                'has_loading_equipment' => true,
                'is_covered' => true,
                'is_secure' => true,
                'simultaneous_vehicles' => 2,
                'daily_capacity' => 50,
                'operating_hours' => $this->getStandardOperatingHours(),
                'latitude' => -33.7249,
                'longitude' => 150.9050,
                'access_instructions' => 'Enter via Gate A, follow signs to North Loading Zone',
            ],
            [
                'bay_number' => 'N-02',
                'bay_name' => 'North Loading Dock 2',
                'zone' => 'north',
                'area_type' => 'loading_dock',
                'building' => 'Building A',
                'max_vehicle_height' => 4.5,
                'max_vehicle_length' => 12,
                'has_refrigeration' => true,
                'has_forklift' => true,
                'is_covered' => true,
                'is_secure' => true,
                'simultaneous_vehicles' => 1,
                'daily_capacity' => 30,
                'operating_hours' => $this->getStandardOperatingHours(),
                'latitude' => -33.7250,
                'longitude' => 150.9051,
            ],

            // South Zone - Drive Through
            [
                'bay_number' => 'S-01',
                'bay_name' => 'South Drive Through',
                'zone' => 'south',
                'area_type' => 'drive_through',
                'max_vehicle_height' => 3.5,
                'max_vehicle_length' => 8,
                'vehicle_type_restriction' => 'small_van',
                'is_covered' => true,
                'is_24_7' => true,
                'simultaneous_vehicles' => 3,
                'latitude' => -33.7260,
                'longitude' => 150.9040,
                'access_instructions' => 'Quick pickup zone - max 15 minutes',
            ],

            // East Zone - Parking Bays
            [
                'bay_number' => 'E-01',
                'bay_name' => 'East Parking Bay 1',
                'zone' => 'east',
                'area_type' => 'parking_bay',
                'is_covered' => false,
                'is_secure' => false,
                'simultaneous_vehicles' => 5,
                'operating_hours' => $this->getExtendedOperatingHours(),
                'latitude' => -33.7255,
                'longitude' => 150.9060,
                'special_requirements' => 'Suitable for small vehicles only',
            ],

            // Central Zone - VIP/Express
            [
                'bay_number' => 'C-VIP',
                'bay_name' => 'VIP Express Bay',
                'zone' => 'central',
                'area_type' => 'loading_dock',
                'building' => 'Main Building',
                'has_forklift' => true,
                'has_loading_equipment' => true,
                'has_refrigeration' => true,
                'is_covered' => true,
                'is_secure' => true,
                'requires_booking' => true,
                'simultaneous_vehicles' => 1,
                'daily_capacity' => 20,
                'operating_hours' => $this->getStandardOperatingHours(),
                'latitude' => -33.7252,
                'longitude' => 150.9055,
                'access_instructions' => 'VIP access only - booking required',
                'status' => 'active',
            ],
        ];

        foreach ($bays as $bayData) {
            PickupBay::create($bayData);
        }

        $this->command->info('Pickup bays created successfully.');
    }

    /**
     * Create time slots
     */
    private function createTimeSlots(): void
    {
        $this->command->info('Creating time slots...');

        $slots = [
            // Early morning slots
            [
                'slot_name' => 'Early Bird',
                'start_time' => '04:00',
                'end_time' => '06:00',
                'duration_minutes' => 30,
                'base_price' => 0,
                'priority_level' => 'standard',
                'max_bookings' => 50,
                'max_per_business' => 2,
                'advance_booking_hours' => 4,
                'cancellation_hours' => 2,
                'color_code' => '#10b981',
                'description' => 'Best availability, quietest time',
            ],
            [
                'slot_name' => 'Morning Rush',
                'start_time' => '06:00',
                'end_time' => '09:00',
                'duration_minutes' => 30,
                'base_price' => 10,
                'peak_price' => 15,
                'is_peak' => true,
                'priority_level' => 'standard',
                'max_bookings' => 100,
                'max_per_business' => 3,
                'advance_booking_hours' => 2,
                'cancellation_hours' => 1,
                'color_code' => '#f59e0b',
                'description' => 'Peak morning hours - busiest time',
            ],
            [
                'slot_name' => 'Mid Morning',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'duration_minutes' => 30,
                'base_price' => 5,
                'priority_level' => 'standard',
                'max_bookings' => 60,
                'max_per_business' => 2,
                'advance_booking_hours' => 2,
                'cancellation_hours' => 1,
                'color_code' => '#3b82f6',
            ],
            [
                'slot_name' => 'Late Morning',
                'start_time' => '11:00',
                'end_time' => '13:00',
                'duration_minutes' => 30,
                'base_price' => 0,
                'priority_level' => 'standard',
                'max_bookings' => 40,
                'max_per_business' => 2,
                'advance_booking_hours' => 1,
                'cancellation_hours' => 1,
                'color_code' => '#8b5cf6',
                'description' => 'Quieter period before closing',
            ],
            [
                'slot_name' => 'Express VIP',
                'start_time' => '05:00',
                'end_time' => '12:00',
                'duration_minutes' => 15,
                'base_price' => 25,
                'priority_level' => 'vip',
                'restricted_to_buyer_types' => ['premium', 'wholesale'],
                'max_bookings' => 20,
                'max_per_business' => 1,
                'advance_booking_hours' => 1,
                'cancellation_hours' => 0,
                'color_code' => '#dc2626',
                'description' => 'Priority service for VIP customers',
            ],
        ];

        foreach ($slots as $slotData) {
            // Set day availability (weekdays and Saturday)
            $slotData['monday'] = true;
            $slotData['tuesday'] = true;
            $slotData['wednesday'] = true;
            $slotData['thursday'] = true;
            $slotData['friday'] = true;
            $slotData['saturday'] = true;
            $slotData['sunday'] = false;
            $slotData['is_active'] = true;
            $slotData['display_order'] = 0;

            PickupTimeSlot::create($slotData);
        }

        $this->command->info('Time slots created successfully.');
    }

    /**
     * Create businesses with users
     */
    private function createBusinessesWithUsers(): void
    {
        $this->command->info('Creating businesses and users...');

        $businesses = [
            [
                'abn' => '12345678901',
                'entity_name' => 'Fresh Produce Distributors Pty Ltd',
                'trading_names' => ['Fresh Direct', 'FPD Markets'],
                'business_type' => 'company',
                'abn_status' => 'active',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2000',
                'address_full' => '123 Market Street, Sydney NSW 2000',
                'users' => [
                    [
                        'first_name' => 'John',
                        'last_name' => 'Smith',
                        'email' => 'john@freshproduce.com.au',
                        'role' => 'owner',
                        'is_primary' => true,
                    ],
                    [
                        'first_name' => 'Sarah',
                        'last_name' => 'Johnson',
                        'email' => 'sarah@freshproduce.com.au',
                        'role' => 'manager',
                    ],
                    [
                        'first_name' => 'Mike',
                        'last_name' => 'Wilson',
                        'email' => 'mike@freshproduce.com.au',
                        'role' => 'buyer',
                    ],
                ],
                'contacts' => [
                    ['type' => 'phone_main', 'value' => '0298765432'],
                    ['type' => 'phone_mobile', 'value' => '0412345678'],
                    ['type' => 'email_orders', 'value' => 'orders@freshproduce.com.au'],
                ],
                'vehicles' => [
                    [
                        'rego' => 'ABC123',
                        'type' => 'large_truck',
                        'make' => 'Isuzu',
                        'model' => 'FRR500',
                        'has_refrigeration' => true,
                    ],
                    [
                        'rego' => 'XYZ789',
                        'type' => 'van',
                        'make' => 'Mercedes',
                        'model' => 'Sprinter',
                    ],
                ],
            ],
            [
                'abn' => '98765432109',
                'entity_name' => 'City Greens Restaurant Group',
                'business_type' => 'company',
                'abn_status' => 'active',
                'gst_registered' => true,
                'address_state_code' => 'NSW',
                'address_postcode' => '2010',
                'address_full' => '456 Restaurant Row, Surry Hills NSW 2010',
                'users' => [
                    [
                        'first_name' => 'Maria',
                        'last_name' => 'Garcia',
                        'email' => 'maria@citygreens.com.au',
                        'role' => 'admin',
                        'is_primary' => true,
                    ],
                    [
                        'first_name' => 'Tony',
                        'last_name' => 'Chen',
                        'email' => 'tony@citygreens.com.au',
                        'role' => 'buyer',
                    ],
                ],
                'contacts' => [
                    ['type' => 'phone_main', 'value' => '0291234567'],
                    ['type' => 'email_main', 'value' => 'info@citygreens.com.au'],
                ],
                'vehicles' => [
                    [
                        'rego' => 'CGR001',
                        'type' => 'van',
                        'make' => 'Toyota',
                        'model' => 'HiAce',
                    ],
                ],
            ],
        ];

        $roles = BusinessUserRole::all()->keyBy('role_slug');

        foreach ($businesses as $businessData) {
            // Create business
            $business = Business::create([
                'abn' => $businessData['abn'],
                'entity_name' => $businessData['entity_name'],
                'trading_names' => $businessData['trading_names'] ?? null,
                'business_type' => $businessData['business_type'],
                'abn_status' => $businessData['abn_status'],
                'gst_registered' => $businessData['gst_registered'],
                'address_state_code' => $businessData['address_state_code'],
                'address_postcode' => $businessData['address_postcode'],
                'address_full' => $businessData['address_full'],
                'last_verified_at' => now(),
                'cached_until' => now()->addDays(30),
            ]);

            // Create contacts
            foreach ($businessData['contacts'] as $contact) {
                BusinessContact::create([
                    'business_id' => $business->id,
                    'contact_type' => $contact['type'],
                    'contact_value' => $contact['value'],
                    'is_primary' => $contact['type'] === 'phone_main',
                    'is_verified' => true,
                    'verified_at' => now(),
                ]);
            }

            // Create users
            foreach ($businessData['users'] as $userData) {
                $buyer = Buyer::create([
                    'business_id' => $business->id,
                    'company_name' => $business->entity_name,
                    'contact_name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'email' => $userData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'phone' => '0400000000',
                    'abn' => $business->abn,
                    'business_type' => 'distributor',
                    'buyer_type' => 'regular',
                    'billing_address' => $business->address_full,
                    'billing_suburb' => 'Sydney',
                    'billing_state' => 'NSW',
                    'billing_postcode' => $business->address_postcode,
                    'billing_country' => 'Australia',
                    'status' => 'active',
                    'verification_status' => 'verified',
                    'is_primary_contact' => $userData['is_primary'] ?? false,
                    'can_place_orders' => true,
                    'verified_at' => now(),
                ]);

                // Create business user relationship
                BusinessUser::create([
                    'business_id' => $business->id,
                    'buyer_id' => $buyer->id,
                    'role_id' => $roles[$userData['role']]->id,
                    'status' => 'active',
                    'approved_at' => now(),
                    'access_granted_at' => now(),
                ]);
            }

            // Create vehicles
            $bays = PickupBay::all();
            $timeSlots = PickupTimeSlot::all();

            foreach ($businessData['vehicles'] as $index => $vehicle) {
                PickupDetail::create([
                    'business_id' => $business->id,
                    'vehicle_rego' => $vehicle['rego'],
                    'vehicle_state' => 'NSW',
                    'vehicle_type' => $vehicle['type'],
                    'vehicle_make' => $vehicle['make'],
                    'vehicle_model' => $vehicle['model'],
                    'has_refrigeration' => $vehicle['has_refrigeration'] ?? false,
                    'preferred_bay_id' => $bays->random()->id,
                    'preferred_time_slot_id' => $timeSlots->random()->id,
                    'pickup_method' => 'scheduled_bay',
                    'preferred_pickup_time' => '07:00',
                    'is_primary_vehicle' => $index === 0,
                    'is_active' => true,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'insurance_expiry' => now()->addYear(),
                    'has_site_induction' => true,
                    'induction_date' => now()->subMonth(),
                    'meets_safety_requirements' => true,
                ]);
            }
        }

        $this->command->info('Businesses and users created successfully.');
    }

    /**
     * Create sample bookings
     */
    private function createSampleBookings(): void
    {
        $this->command->info('Creating sample bookings...');

        $businesses = Business::with(['buyers', 'pickupDetails'])->get();
        $bays = PickupBay::active()->get();
        $timeSlots = PickupTimeSlot::active()->get();

        foreach ($businesses as $business) {
            $buyer = $business->buyers()->first();
            $vehicle = $business->pickupDetails()->first();

            if (!$buyer || !$vehicle) {
                continue;
            }

            // Create past bookings
            for ($i = 5; $i >= 1; $i--) {
                $pickupDate = now()->subDays($i);
                $timeSlot = $timeSlots->random();
                $bay = $bays->random();

                PickupBooking::create([
                    'business_id' => $business->id,
                    'buyer_id' => $buyer->id,
                    'pickup_detail_id' => $vehicle->id,
                    'bay_id' => $bay->id,
                    'time_slot_id' => $timeSlot->id,
                    'pickup_date' => $pickupDate,
                    'scheduled_time' => $timeSlot->start_time,
                    'scheduled_end_time' => $timeSlot->end_time,
                    'estimated_duration_minutes' => 30,
                    'vehicle_rego' => $vehicle->vehicle_rego,
                    'driver_name' => $buyer->contact_name,
                    'driver_phone' => $buyer->phone,
                    'total_items' => rand(10, 50),
                    'pallet_count' => rand(1, 5),
                    'status' => 'completed',
                    'confirmed_at' => $pickupDate->copy()->subDay(),
                    'arrival_time' => $pickupDate->copy()->setTimeFromTimeString($timeSlot->start_time),
                    'departure_time' => $pickupDate->copy()->setTimeFromTimeString($timeSlot->start_time)->addMinutes(25),
                    'actual_duration_minutes' => 25,
                    'rating' => rand(4, 5),
                ]);
            }

            // Create upcoming bookings
            for ($i = 1; $i <= 3; $i++) {
                $pickupDate = now()->addDays($i);
                $timeSlot = $timeSlots->random();
                $bay = $bays->random();

                PickupBooking::create([
                    'business_id' => $business->id,
                    'buyer_id' => $buyer->id,
                    'pickup_detail_id' => $vehicle->id,
                    'bay_id' => $bay->id,
                    'time_slot_id' => $timeSlot->id,
                    'pickup_date' => $pickupDate,
                    'scheduled_time' => $timeSlot->start_time,
                    'scheduled_end_time' => $timeSlot->end_time,
                    'estimated_duration_minutes' => 30,
                    'vehicle_rego' => $vehicle->vehicle_rego,
                    'driver_name' => $buyer->contact_name,
                    'driver_phone' => $buyer->phone,
                    'total_items' => rand(10, 50),
                    'pallet_count' => rand(1, 5),
                    'status' => $i === 1 ? 'confirmed' : 'pending',
                    'confirmed_at' => $i === 1 ? now() : null,
                    'special_instructions' => $i === 1 ? 'Please have order ready for quick loading' : null,
                ]);
            }
        }

        $this->command->info('Sample bookings created successfully.');
    }

    /**
     * Get standard operating hours
     */
    private function getStandardOperatingHours(): array
    {
        return [
            'monday' => ['start' => '04:00', 'end' => '14:00'],
            'tuesday' => ['start' => '04:00', 'end' => '14:00'],
            'wednesday' => ['start' => '04:00', 'end' => '14:00'],
            'thursday' => ['start' => '04:00', 'end' => '14:00'],
            'friday' => ['start' => '04:00', 'end' => '14:00'],
            'saturday' => ['start' => '05:00', 'end' => '12:00'],
        ];
    }

    /**
     * Get extended operating hours
     */
    private function getExtendedOperatingHours(): array
    {
        return [
            'monday' => ['start' => '03:00', 'end' => '16:00'],
            'tuesday' => ['start' => '03:00', 'end' => '16:00'],
            'wednesday' => ['start' => '03:00', 'end' => '16:00'],
            'thursday' => ['start' => '03:00', 'end' => '16:00'],
            'friday' => ['start' => '03:00', 'end' => '16:00'],
            'saturday' => ['start' => '04:00', 'end' => '14:00'],
            'sunday' => ['start' => '06:00', 'end' => '10:00'],
        ];
    }
}