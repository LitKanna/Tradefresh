<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Sydney CBD',
                'description' => 'Central Business District and surrounding areas',
                'postcodes' => json_encode(['2000', '2001', '2008', '2009', '2010', '2011']),
                'suburbs' => json_encode([
                    'Sydney', 'Haymarket', 'The Rocks', 'Circular Quay', 
                    'Chippendale', 'Pyrmont', 'Surry Hills', 'Darlinghurst'
                ]),
                'delivery_fee' => 15.00,
                'free_delivery_threshold' => 200.00,
                'delivery_time_standard' => '2-4 hours',
                'delivery_time_express' => '1-2 hours',
                'is_active' => true,
                'priority' => 1,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Small to medium trucks',
                    'access_times' => '6AM-8PM weekdays, 7AM-6PM weekends',
                    'special_notes' => 'Parking restrictions apply in CBD'
                ])
            ],
            [
                'name' => 'Inner West',
                'description' => 'Newtown, Glebe, Leichhardt and surrounding suburbs',
                'postcodes' => json_encode(['2040', '2041', '2042', '2043', '2044', '2050']),
                'suburbs' => json_encode([
                    'Leichhardt', 'Glebe', 'Newtown', 'Enmore', 'Annandale', 
                    'Camperdown', 'Petersham', 'Marrickville'
                ]),
                'delivery_fee' => 12.00,
                'free_delivery_threshold' => 250.00,
                'delivery_time_standard' => '3-5 hours',
                'delivery_time_express' => '1-3 hours',
                'is_active' => true,
                'priority' => 2,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Medium trucks acceptable',
                    'access_times' => '5AM-9PM daily',
                    'special_notes' => 'Narrow streets in some areas'
                ])
            ],
            [
                'name' => 'Eastern Suburbs',
                'description' => 'Bondi, Paddington, Double Bay and eastern areas',
                'postcodes' => json_encode(['2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030', '2031']),
                'suburbs' => json_encode([
                    'Paddington', 'Woollahra', 'Double Bay', 'Bondi Junction', 
                    'Bondi', 'Bronte', 'Coogee', 'Randwick', 'Kingsford', 'Maroubra'
                ]),
                'delivery_fee' => 18.00,
                'free_delivery_threshold' => 300.00,
                'delivery_time_standard' => '3-6 hours',
                'delivery_time_express' => '2-4 hours',
                'is_active' => true,
                'priority' => 3,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Small to medium trucks',
                    'access_times' => '6AM-8PM weekdays, 7AM-7PM weekends',
                    'special_notes' => 'Premium area, careful parking required'
                ])
            ],
            [
                'name' => 'Northern Beaches',
                'description' => 'Manly, Dee Why, Mona Vale and northern coastal areas',
                'postcodes' => json_encode(['2095', '2096', '2097', '2099', '2100', '2101', '2102', '2103', '2104', '2105', '2106', '2107', '2108']),
                'suburbs' => json_encode([
                    'Manly', 'Brookvale', 'Dee Why', 'Collaroy', 'Narrabeen', 
                    'Mona Vale', 'Avalon', 'Palm Beach', 'Frenchs Forest', 'Belrose'
                ]),
                'delivery_fee' => 25.00,
                'free_delivery_threshold' => 400.00,
                'delivery_time_standard' => '4-8 hours',
                'delivery_time_express' => '2-4 hours',
                'is_active' => true,
                'priority' => 4,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Small trucks preferred',
                    'access_times' => '6AM-7PM daily',
                    'special_notes' => 'Bridge crossing, allow extra travel time'
                ])
            ],
            [
                'name' => 'Lower North Shore',
                'description' => 'North Sydney, Chatswood, Crows Nest and surrounding areas',
                'postcodes' => json_encode(['2060', '2061', '2062', '2063', '2064', '2065', '2066', '2067', '2068', '2069', '2070']),
                'suburbs' => json_encode([
                    'North Sydney', 'Neutral Bay', 'Cremorne', 'Mosman', 
                    'Crows Nest', 'Wollstonecraft', 'Lane Cove', 'Chatswood', 'Willoughby'
                ]),
                'delivery_fee' => 16.00,
                'free_delivery_threshold' => 280.00,
                'delivery_time_standard' => '3-5 hours',
                'delivery_time_express' => '1-3 hours',
                'is_active' => true,
                'priority' => 2,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Medium trucks acceptable',
                    'access_times' => '5AM-9PM daily',
                    'special_notes' => 'Bridge access via Harbour Bridge'
                ])
            ],
            [
                'name' => 'South West',
                'description' => 'Canterbury, Bankstown, Liverpool and south western suburbs',
                'postcodes' => json_encode(['2195', '2196', '2197', '2198', '2200', '2203', '2204', '2205', '2206', '2207', '2208', '2209', '2210']),
                'suburbs' => json_encode([
                    'Canterbury', 'Campsie', 'Lakemba', 'Bankstown', 'Yagoona', 
                    'Birrong', 'Marrickville', 'Dulwich Hill', 'Hurstville', 'Beverly Hills'
                ]),
                'delivery_fee' => 20.00,
                'free_delivery_threshold' => 350.00,
                'delivery_time_standard' => '4-6 hours',
                'delivery_time_express' => '2-4 hours',
                'is_active' => true,
                'priority' => 3,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Large trucks acceptable',
                    'access_times' => '5AM-9PM daily',
                    'special_notes' => 'Industrial areas, good truck access'
                ])
            ],
            [
                'name' => 'West Sydney',
                'description' => 'Parramatta, Auburn, Lidcombe and western suburbs',
                'postcodes' => json_encode(['2140', '2141', '2142', '2143', '2144', '2145', '2146', '2147', '2148', '2150', '2151', '2152']),
                'suburbs' => json_encode([
                    'Flemington', 'Homebush', 'Lidcombe', 'Auburn', 'Granville', 
                    'Parramatta', 'Harris Park', 'Westmead', 'Blacktown', 'Seven Hills'
                ]),
                'delivery_fee' => 22.00,
                'free_delivery_threshold' => 400.00,
                'delivery_time_standard' => '4-7 hours',
                'delivery_time_express' => '2-5 hours',
                'is_active' => true,
                'priority' => 4,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Large trucks acceptable',
                    'access_times' => '5AM-10PM daily',
                    'special_notes' => 'Major transport hub, good access roads'
                ])
            ],
            [
                'name' => 'Outer Metropolitan',
                'description' => 'Penrith, Richmond, Camden and outer Sydney areas',
                'postcodes' => json_encode(['2747', '2748', '2749', '2750', '2751', '2752', '2753', '2754', '2755', '2756', '2757', '2570', '2571', '2572']),
                'suburbs' => json_encode([
                    'Penrith', 'Richmond', 'Windsor', 'Blacktown', 'Mount Druitt', 
                    'Camden', 'Narellan', 'Campbelltown', 'Liverpool', 'Fairfield'
                ]),
                'delivery_fee' => 35.00,
                'free_delivery_threshold' => 600.00,
                'delivery_time_standard' => '6-8 hours',
                'delivery_time_express' => '4-6 hours',
                'is_active' => true,
                'priority' => 5,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Any size acceptable',
                    'access_times' => '5AM-10PM daily',
                    'special_notes' => 'Longer travel times, schedule accordingly'
                ])
            ],
            [
                'name' => 'Sutherland Shire',
                'description' => 'Cronulla, Miranda, Caringbah and Shire areas',
                'postcodes' => json_encode(['2220', '2221', '2222', '2223', '2224', '2225', '2226', '2227', '2228', '2229', '2230', '2231', '2232']),
                'suburbs' => json_encode([
                    'Hurstville', 'Kogarah', 'Brighton-Le-Sands', 'Rockdale', 'Miranda', 
                    'Caringbah', 'Cronulla', 'Sutherland', 'Engadine', 'Menai'
                ]),
                'delivery_fee' => 28.00,
                'free_delivery_threshold' => 450.00,
                'delivery_time_standard' => '5-7 hours',
                'delivery_time_express' => '3-5 hours',
                'is_active' => true,
                'priority' => 4,
                'restrictions' => json_encode([
                    'vehicle_size_limit' => 'Medium trucks preferred',
                    'access_times' => '6AM-8PM daily',
                    'special_notes' => 'Bridge crossing required from city'
                ])
            ]
        ];

        foreach ($zones as $zone) {
            DeliveryZone::updateOrCreate(
                ['name' => $zone['name']],
                array_merge($zone, [
                    'metadata' => json_encode([
                        'population_density' => ['high', 'medium', 'low'][rand(0, 2)],
                        'business_concentration' => ['high', 'medium', 'low'][rand(0, 2)],
                        'traffic_conditions' => ['heavy', 'moderate', 'light'][rand(0, 2)],
                        'parking_availability' => ['limited', 'moderate', 'good'][rand(0, 2)]
                    ])
                ])
            );
        }

        $this->command->info('Delivery zones seeded successfully.');
    }
}