<?php

namespace Database\Seeders;

use App\Models\Buyer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BuyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = [
            // Fine Dining Restaurant
            [
                'company_name' => 'The Sydney Kitchen',
                'contact_name' => 'James Anderson',
                'email' => 'restaurant@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0412345001',
                'abn' => '11122233344',
                'business_type' => 'restaurant',
                'buyer_type' => 'premium',
                'billing_address' => '123 Fine Dining Avenue',
                'billing_suburb' => 'Sydney',
                'billing_state' => 'NSW',
                'billing_postcode' => '2000',
                'billing_country' => 'Australia',
                'shipping_address' => '123 Fine Dining Avenue',
                'shipping_suburb' => 'Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
                'website' => 'www.thesydneykitchen.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 25000.00,
                'credit_used' => 8500.00,
                'payment_terms' => 'net_30',
                'preferred_payment_method' => 'credit_card',
                'tax_exempt' => false,
                'tax_id' => 'TAX001122',
                'notes' => 'High-end restaurant, orders premium ingredients daily',
                'email_verified_at' => now(),
                'joined_at' => now()->subYears(2),
                'approved_at' => now()->subYears(2),
                'preferences' => json_encode([
                    'preferred_delivery_time' => '06:00-08:00',
                    'special_instructions' => 'Use loading dock entrance',
                    'quality_requirements' => 'Premium grade only'
                ])
            ],
            // Coffee Shop Chain
            [
                'company_name' => 'Sunshine Cafe Group',
                'contact_name' => 'Lisa Martinez',
                'email' => 'cafe@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0423456002',
                'abn' => '22233344455',
                'business_type' => 'cafe',
                'buyer_type' => 'regular',
                'billing_address' => '456 Coffee Street',
                'billing_suburb' => 'Surry Hills',
                'billing_state' => 'NSW',
                'billing_postcode' => '2010',
                'billing_country' => 'Australia',
                'shipping_address' => '456 Coffee Street',
                'shipping_suburb' => 'Surry Hills',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2010',
                'shipping_country' => 'Australia',
                'website' => 'www.sunshinecafes.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 15000.00,
                'credit_used' => 3200.00,
                'payment_terms' => 'net_14',
                'preferred_payment_method' => 'bank_transfer',
                'tax_exempt' => false,
                'tax_id' => 'TAX223344',
                'notes' => '3 locations, weekly bulk orders',
                'email_verified_at' => now(),
                'joined_at' => now()->subMonths(18),
                'approved_at' => now()->subMonths(18),
                'preferences' => json_encode([
                    'preferred_delivery_time' => '05:00-07:00',
                    'consolidate_orders' => true,
                    'eco_packaging_preferred' => true
                ])
            ],
            // Luxury Hotel
            [
                'company_name' => 'Grand Pacific Hotel',
                'contact_name' => 'William Thompson',
                'email' => 'hotel@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0434567003',
                'abn' => '33344455566',
                'business_type' => 'restaurant',
                'buyer_type' => 'wholesale',
                'billing_address' => '1 Harbour View Plaza',
                'billing_suburb' => 'Circular Quay',
                'billing_state' => 'NSW',
                'billing_postcode' => '2000',
                'billing_country' => 'Australia',
                'shipping_address' => '1 Harbour View Plaza - Service Entrance',
                'shipping_suburb' => 'Circular Quay',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
                'website' => 'www.grandpacifichotel.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 75000.00,
                'credit_used' => 25000.00,
                'payment_terms' => 'net_30',
                'preferred_payment_method' => 'bank_transfer',
                'tax_exempt' => false,
                'tax_id' => 'TAX334455',
                'notes' => '5-star hotel, multiple restaurants and room service',
                'email_verified_at' => now(),
                'joined_at' => now()->subYears(3),
                'approved_at' => now()->subYears(3),
                'preferences' => json_encode([
                    'preferred_delivery_time' => '04:00-06:00',
                    'quality_inspection_required' => true,
                    'dedicated_account_manager' => true
                ])
            ],
            // Catering Company
            [
                'company_name' => 'Elite Events Catering',
                'contact_name' => 'Sophie Chen',
                'email' => 'catering@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0445678004',
                'abn' => '44455566677',
                'business_type' => 'distributor',
                'buyer_type' => 'regular',
                'billing_address' => '789 Event Plaza',
                'billing_suburb' => 'Alexandria',
                'billing_state' => 'NSW',
                'billing_postcode' => '2015',
                'billing_country' => 'Australia',
                'shipping_address' => '789 Event Plaza - Kitchen Entry',
                'shipping_suburb' => 'Alexandria',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2015',
                'shipping_country' => 'Australia',
                'website' => 'www.eliteevents.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 35000.00,
                'credit_used' => 12000.00,
                'payment_terms' => 'net_14',
                'preferred_payment_method' => 'credit_card',
                'tax_exempt' => false,
                'tax_id' => 'TAX445566',
                'notes' => 'Large event catering, variable order sizes',
                'email_verified_at' => now(),
                'joined_at' => now()->subYears(1)->subMonths(8),
                'approved_at' => now()->subYears(1)->subMonths(8),
                'preferences' => json_encode([
                    'flexible_delivery_times' => true,
                    'last_minute_orders_ok' => true,
                    'premium_service_level' => true
                ])
            ],
            // School Canteen
            [
                'company_name' => 'Healthy School Meals Co',
                'contact_name' => 'David Kumar',
                'email' => 'schoolmeals@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0456789005',
                'abn' => '55566677788',
                'business_type' => 'other',
                'buyer_type' => 'regular',
                'billing_address' => '321 Education Drive',
                'billing_suburb' => 'Parramatta',
                'billing_state' => 'NSW',
                'billing_postcode' => '2150',
                'billing_country' => 'Australia',
                'shipping_address' => '321 Education Drive',
                'shipping_suburb' => 'Parramatta',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2150',
                'shipping_country' => 'Australia',
                'website' => 'www.healthyschoolmeals.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 20000.00,
                'credit_used' => 4500.00,
                'payment_terms' => 'net_30',
                'preferred_payment_method' => 'bank_transfer',
                'tax_exempt' => true,
                'tax_id' => 'TAX556677',
                'notes' => 'Supplies 8 school canteens, budget conscious',
                'email_verified_at' => now(),
                'joined_at' => now()->subMonths(15),
                'approved_at' => now()->subMonths(15),
                'preferences' => json_encode([
                    'healthy_options_priority' => true,
                    'cost_effective_solutions' => true,
                    'allergen_free_options' => true
                ])
            ],
            // Independent Grocery Store
            [
                'company_name' => 'Corner Store Groceries',
                'contact_name' => 'Maria Rossi',
                'email' => 'grocery@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0467890006',
                'abn' => '66677788899',
                'business_type' => 'grocery',
                'buyer_type' => 'regular',
                'billing_address' => '654 Neighbourhood Road',
                'billing_suburb' => 'Leichhardt',
                'billing_state' => 'NSW',
                'billing_postcode' => '2040',
                'billing_country' => 'Australia',
                'shipping_address' => '654 Neighbourhood Road',
                'shipping_suburb' => 'Leichhardt',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2040',
                'shipping_country' => 'Australia',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 10000.00,
                'credit_used' => 1800.00,
                'payment_terms' => 'net_7',
                'preferred_payment_method' => 'credit_card',
                'tax_exempt' => false,
                'tax_id' => 'TAX667788',
                'notes' => 'Family-owned grocery, 2 generations',
                'email_verified_at' => now(),
                'joined_at' => now()->subMonths(10),
                'approved_at' => now()->subMonths(10),
                'preferences' => json_encode([
                    'small_quantity_orders' => true,
                    'local_produce_preferred' => true,
                    'flexible_payment_terms' => true
                ])
            ],
            // Food Truck Operations
            [
                'company_name' => 'Gourmet Food Trucks Sydney',
                'contact_name' => 'Tony Nguyen',
                'email' => 'foodtrucks@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0478901007',
                'abn' => '77788899900',
                'business_type' => 'restaurant',
                'buyer_type' => 'regular',
                'billing_address' => '987 Mobile Kitchen Way',
                'billing_suburb' => 'Chippendale',
                'billing_state' => 'NSW',
                'billing_postcode' => '2008',
                'billing_country' => 'Australia',
                'shipping_address' => '987 Mobile Kitchen Way - Depot',
                'shipping_suburb' => 'Chippendale',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2008',
                'shipping_country' => 'Australia',
                'website' => 'www.gourmetfoodtrucks.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 8000.00,
                'credit_used' => 2100.00,
                'payment_terms' => 'prepaid',
                'preferred_payment_method' => 'credit_card',
                'tax_exempt' => false,
                'tax_id' => 'TAX778899',
                'notes' => 'Fleet of 4 food trucks, high turnover items',
                'email_verified_at' => now(),
                'joined_at' => now()->subMonths(12),
                'approved_at' => now()->subMonths(12),
                'preferences' => json_encode([
                    'quick_delivery_essential' => true,
                    'compact_packaging_preferred' => true,
                    'fresh_ingredients_priority' => true
                ])
            ],
            // Corporate Catering
            [
                'company_name' => 'Executive Dining Solutions',
                'contact_name' => 'Rachel White',
                'email' => 'corporate@buyer.com',
                'password' => Hash::make('buyer123'),
                'phone' => '0489012008',
                'abn' => '88899900011',
                'business_type' => 'distributor',
                'buyer_type' => 'premium',
                'billing_address' => '111 Corporate Tower',
                'billing_suburb' => 'North Sydney',
                'billing_state' => 'NSW',
                'billing_postcode' => '2060',
                'billing_country' => 'Australia',
                'shipping_address' => '111 Corporate Tower - Level B2',
                'shipping_suburb' => 'North Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2060',
                'shipping_country' => 'Australia',
                'website' => 'www.executivedining.com.au',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 45000.00,
                'credit_used' => 18000.00,
                'payment_terms' => 'net_30',
                'preferred_payment_method' => 'bank_transfer',
                'tax_exempt' => false,
                'tax_id' => 'TAX889900',
                'notes' => 'Corporate catering for CBD offices',
                'email_verified_at' => now(),
                'joined_at' => now()->subMonths(22),
                'approved_at' => now()->subMonths(22),
                'preferences' => json_encode([
                    'presentation_quality_important' => true,
                    'dietary_restrictions_accommodated' => true,
                    'consistent_suppliers_preferred' => true
                ])
            ]
        ];

        foreach ($buyers as $buyer) {
            Buyer::updateOrCreate(
                ['email' => $buyer['email']],
                array_merge($buyer, [
                    'metadata' => json_encode([
                        'average_order_value' => rand(200, 2000),
                        'order_frequency' => ['weekly', 'bi-weekly', 'daily'][rand(0, 2)],
                        'peak_season' => ['summer', 'winter', 'all-year'][rand(0, 2)],
                        'preferred_categories' => [
                            'fresh-produce', 
                            'meat-poultry', 
                            'dairy-eggs'
                        ]
                    ])
                ])
            );
        }

        $this->command->info('Buyers seeded successfully.');
    }
}