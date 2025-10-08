<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Buyer;

class SuppliersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Fresh Produce Co',
                'business_name' => 'Fresh Produce Company Pty Ltd',
                'email' => 'orders@freshproduce.com.au',
                'phone' => '+61 2 1234 5678',
                'address' => '123 Market Street, Sydney Markets',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2015',
                'country' => 'Australia',
                'abn' => '12 345 678 901',
                'website' => 'https://freshproduce.com.au',
                'description' => 'Premium fresh fruits and vegetables supplier',
                'categories' => json_encode(['Fruits', 'Vegetables', 'Organic']),
                'rating' => 4.8,
                'total_orders' => 150,
                'total_revenue' => 45000.00,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ],
            [
                'name' => 'Metro Meats',
                'business_name' => 'Metropolitan Meat Suppliers',
                'email' => 'sales@metromeats.com.au',
                'phone' => '+61 2 2345 6789',
                'address' => '456 Butcher Lane, Sydney Markets',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2015',
                'country' => 'Australia',
                'abn' => '23 456 789 012',
                'website' => 'https://metromeats.com.au',
                'description' => 'Quality meat and poultry wholesale supplier',
                'categories' => json_encode(['Beef', 'Chicken', 'Pork', 'Lamb']),
                'rating' => 4.6,
                'total_orders' => 120,
                'total_revenue' => 68000.00,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ],
            [
                'name' => 'Ocean Fresh Seafood',
                'business_name' => 'Ocean Fresh Seafood Pty Ltd',
                'email' => 'info@oceanfresh.com.au',
                'phone' => '+61 2 3456 7890',
                'address' => '789 Wharf Road, Sydney Markets',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2015',
                'country' => 'Australia',
                'abn' => '34 567 890 123',
                'website' => 'https://oceanfresh.com.au',
                'description' => 'Fresh seafood and fish supplier',
                'categories' => json_encode(['Fish', 'Shellfish', 'Frozen Seafood']),
                'rating' => 4.7,
                'total_orders' => 89,
                'total_revenue' => 32000.00,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ],
            [
                'name' => 'Dairy Direct',
                'business_name' => 'Dairy Direct Suppliers',
                'email' => 'orders@dairydirect.com.au',
                'phone' => '+61 2 4567 8901',
                'address' => '321 Milk Street, Sydney Markets',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2015',
                'country' => 'Australia',
                'abn' => '45 678 901 234',
                'website' => 'https://dairydirect.com.au',
                'description' => 'Premium dairy products and cheese supplier',
                'categories' => json_encode(['Milk', 'Cheese', 'Yogurt', 'Butter']),
                'rating' => 4.9,
                'total_orders' => 95,
                'total_revenue' => 28000.00,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ],
            [
                'name' => 'Grain & More',
                'business_name' => 'Grain & More Wholesale',
                'email' => 'sales@grainandmore.com.au',
                'phone' => '+61 2 5678 9012',
                'address' => '654 Flour Street, Sydney Markets',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2015',
                'country' => 'Australia',
                'abn' => '56 789 012 345',
                'website' => 'https://grainandmore.com.au',
                'description' => 'Bulk grains, flour, and baking supplies',
                'categories' => json_encode(['Grains', 'Flour', 'Baking Supplies']),
                'rating' => 4.5,
                'total_orders' => 78,
                'total_revenue' => 22000.00,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }

        // Connect suppliers to existing buyers
        $buyers = Buyer::all();
        $supplierIds = Supplier::pluck('id')->toArray();

        foreach ($buyers as $buyer) {
            // Randomly connect 2-4 suppliers to each buyer
            $randomSuppliers = array_slice($supplierIds, 0, rand(2, 4));
            
            foreach ($randomSuppliers as $supplierId) {
                $buyer->suppliers()->attach($supplierId, [
                    'total_spent' => rand(5000, 25000),
                    'total_orders' => rand(10, 50),
                    'average_rating' => round(4.0 + (rand(0, 10) / 10), 1),
                    'is_preferred' => rand(0, 1),
                    'first_order_at' => now()->subDays(rand(30, 365)),
                    'last_order_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
