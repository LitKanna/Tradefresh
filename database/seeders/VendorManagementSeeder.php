<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorProduct;
use App\Models\VendorCertification;
use App\Models\VendorPerformanceMetric;
use App\Models\VendorPricingTerm;
use App\Models\VendorReview;
use App\Models\User;
use Faker\Factory as Faker;
use Carbon\Carbon;

class VendorManagementSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        // Create vendor categories
        $categories = $this->createCategories();
        
        // Create sample vendors
        $vendors = $this->createVendors($faker, $categories);
        
        // Create products for each vendor
        foreach ($vendors as $vendor) {
            $this->createProductsForVendor($vendor, $faker);
            $this->createCertificationsForVendor($vendor, $faker);
            $this->createPerformanceMetricsForVendor($vendor, $faker);
            $this->createPricingTermsForVendor($vendor, $faker);
            $this->createReviewsForVendor($vendor, $faker);
        }
    }

    private function createCategories()
    {
        $categories = [
            [
                'name' => 'Fresh Produce',
                'slug' => 'fresh-produce',
                'description' => 'Fresh fruits and vegetables',
                'icon' => 'fa-carrot',
                'children' => [
                    ['name' => 'Fruits', 'slug' => 'fruits'],
                    ['name' => 'Vegetables', 'slug' => 'vegetables'],
                    ['name' => 'Herbs', 'slug' => 'herbs'],
                ]
            ],
            [
                'name' => 'Meat & Seafood',
                'slug' => 'meat-seafood',
                'description' => 'Fresh and frozen meat and seafood',
                'icon' => 'fa-fish',
                'children' => [
                    ['name' => 'Beef', 'slug' => 'beef'],
                    ['name' => 'Poultry', 'slug' => 'poultry'],
                    ['name' => 'Seafood', 'slug' => 'seafood'],
                ]
            ],
            [
                'name' => 'Dairy & Eggs',
                'slug' => 'dairy-eggs',
                'description' => 'Dairy products and eggs',
                'icon' => 'fa-cheese',
                'children' => [
                    ['name' => 'Milk', 'slug' => 'milk'],
                    ['name' => 'Cheese', 'slug' => 'cheese'],
                    ['name' => 'Eggs', 'slug' => 'eggs'],
                ]
            ],
            [
                'name' => 'Bakery',
                'slug' => 'bakery',
                'description' => 'Fresh baked goods',
                'icon' => 'fa-bread-slice'
            ],
            [
                'name' => 'Beverages',
                'slug' => 'beverages',
                'description' => 'Non-alcoholic beverages',
                'icon' => 'fa-wine-bottle'
            ]
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            $parent = VendorCategory::create($categoryData);
            $createdCategories[] = $parent;
            
            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                $childData['description'] = $childData['description'] ?? null;
                $childData['icon'] = $childData['icon'] ?? null;
                $child = VendorCategory::create($childData);
                $createdCategories[] = $child;
            }
        }
        
        return $createdCategories;
    }

    private function createVendors($faker, $categories)
    {
        $vendorData = [
            [
                'business_name' => 'Fresh Harvest Farms',
                'contact_name' => 'John Smith',
                'email' => 'contact@freshharvestfarms.com',
                'phone' => '02 9999 0001',
                'business_type' => 'producer',
                'vendor_type' => 'premium',
                'abn' => '12345678901',
                'address' => '123 Farm Road',
                'suburb' => 'Flemington',
                'state' => 'NSW',
                'postcode' => '2140',
                'country' => 'Australia',
                'website' => 'https://freshharvestfarms.com.au',
                'description' => 'Premium quality fresh produce direct from our farms',
                'status' => 'active',
                'verification_status' => 'verified',
                'rating' => 4.7,
                'total_reviews' => 45,
                'total_sales' => 150000,
                'joined_at' => Carbon::now()->subYears(3),
                'approved_at' => Carbon::now()->subYears(3),
                'minimum_order_value' => 500,
                'delivery_fee' => 50,
                'free_delivery_threshold' => 2000,
            ],
            [
                'business_name' => 'Sydney Seafood Co',
                'contact_name' => 'Sarah Johnson',
                'email' => 'orders@sydneyseafood.com.au',
                'phone' => '02 9999 0002',
                'business_type' => 'distributor',
                'vendor_type' => 'standard',
                'abn' => '23456789012',
                'address' => '456 Wharf Street',
                'suburb' => 'Pyrmont',
                'state' => 'NSW',
                'postcode' => '2009',
                'country' => 'Australia',
                'website' => 'https://sydneyseafoodco.com.au',
                'description' => 'Fresh seafood daily from Sydney Fish Markets',
                'status' => 'active',
                'verification_status' => 'verified',
                'rating' => 4.9,
                'total_reviews' => 78,
                'total_sales' => 280000,
                'joined_at' => Carbon::now()->subYears(5),
                'approved_at' => Carbon::now()->subYears(5),
                'minimum_order_value' => 300,
                'delivery_fee' => 40,
                'free_delivery_threshold' => 1500,
            ],
            [
                'business_name' => 'Artisan Bakery Supply',
                'contact_name' => 'Michael Brown',
                'email' => 'info@artisanbakery.com.au',
                'phone' => '02 9999 0003',
                'business_type' => 'manufacturer',
                'vendor_type' => 'premium',
                'abn' => '34567890123',
                'address' => '789 Bakery Lane',
                'suburb' => 'Marrickville',
                'state' => 'NSW',
                'postcode' => '2204',
                'country' => 'Australia',
                'website' => 'https://artisanbakerysupply.com.au',
                'description' => 'Handcrafted artisan breads and pastries',
                'status' => 'active',
                'verification_status' => 'verified',
                'rating' => 4.8,
                'total_reviews' => 62,
                'total_sales' => 195000,
                'joined_at' => Carbon::now()->subYears(2),
                'approved_at' => Carbon::now()->subYears(2),
                'minimum_order_value' => 200,
                'delivery_fee' => 30,
                'free_delivery_threshold' => 1000,
            ],
            [
                'business_name' => 'Premium Meats Direct',
                'contact_name' => 'David Wilson',
                'email' => 'sales@premiummeatsdirect.com.au',
                'phone' => '02 9999 0004',
                'business_type' => 'wholesaler',
                'vendor_type' => 'premium',
                'abn' => '45678901234',
                'address' => '321 Butcher Street',
                'suburb' => 'Homebush',
                'state' => 'NSW',
                'postcode' => '2140',
                'country' => 'Australia',
                'website' => 'https://premiummeatsdirect.com.au',
                'description' => 'Premium grass-fed beef and free-range poultry',
                'status' => 'active',
                'verification_status' => 'verified',
                'rating' => 4.6,
                'total_reviews' => 89,
                'total_sales' => 420000,
                'joined_at' => Carbon::now()->subYears(4),
                'approved_at' => Carbon::now()->subYears(4),
                'minimum_order_value' => 400,
                'delivery_fee' => 60,
                'free_delivery_threshold' => 2500,
            ],
            [
                'business_name' => 'Valley Dairy Products',
                'contact_name' => 'Emma Thompson',
                'email' => 'orders@valleydairy.com.au',
                'phone' => '02 9999 0005',
                'business_type' => 'producer',
                'vendor_type' => 'standard',
                'abn' => '56789012345',
                'address' => '654 Dairy Road',
                'suburb' => 'Camden',
                'state' => 'NSW',
                'postcode' => '2570',
                'country' => 'Australia',
                'website' => 'https://valleydairyproducts.com.au',
                'description' => 'Fresh local dairy products from our family farm',
                'status' => 'active',
                'verification_status' => 'verified',
                'rating' => 4.5,
                'total_reviews' => 34,
                'total_sales' => 120000,
                'joined_at' => Carbon::now()->subYears(1),
                'approved_at' => Carbon::now()->subYears(1),
                'minimum_order_value' => 150,
                'delivery_fee' => 25,
                'free_delivery_threshold' => 800,
            ]
        ];

        $vendors = [];
        foreach ($vendorData as $data) {
            $data['password'] = bcrypt('password');
            $vendor = Vendor::create($data);
            
            // Attach random categories
            $categoryCount = rand(1, 3);
            $selectedCategories = $faker->randomElements($categories, $categoryCount);
            foreach ($selectedCategories as $index => $category) {
                $vendor->categories()->attach($category->id, ['is_primary' => $index === 0]);
            }
            
            $vendors[] = $vendor;
        }
        
        return $vendors;
    }

    private function createProductsForVendor($vendor, $faker)
    {
        $productTemplates = [
            'Fresh Harvest Farms' => [
                ['name' => 'Organic Tomatoes', 'price' => 8.99, 'unit' => 'kg'],
                ['name' => 'Fresh Lettuce', 'price' => 4.50, 'unit' => 'head'],
                ['name' => 'Carrots', 'price' => 3.99, 'unit' => 'kg'],
                ['name' => 'Apples - Pink Lady', 'price' => 6.99, 'unit' => 'kg'],
                ['name' => 'Strawberries', 'price' => 12.99, 'unit' => 'punnet'],
            ],
            'Sydney Seafood Co' => [
                ['name' => 'Fresh Salmon Fillets', 'price' => 42.99, 'unit' => 'kg'],
                ['name' => 'Tiger Prawns', 'price' => 38.99, 'unit' => 'kg'],
                ['name' => 'Sydney Rock Oysters', 'price' => 24.99, 'unit' => 'dozen'],
                ['name' => 'Barramundi Fillets', 'price' => 32.99, 'unit' => 'kg'],
                ['name' => 'Blue Swimmer Crab', 'price' => 28.99, 'unit' => 'kg'],
            ],
            'Artisan Bakery Supply' => [
                ['name' => 'Sourdough Loaf', 'price' => 7.50, 'unit' => 'loaf'],
                ['name' => 'Croissants', 'price' => 4.50, 'unit' => 'piece'],
                ['name' => 'Baguette', 'price' => 5.00, 'unit' => 'piece'],
                ['name' => 'Danish Pastries', 'price' => 5.50, 'unit' => 'piece'],
                ['name' => 'Ciabatta Rolls', 'price' => 2.50, 'unit' => 'piece'],
            ],
            'Premium Meats Direct' => [
                ['name' => 'Wagyu Beef Ribeye', 'price' => 89.99, 'unit' => 'kg'],
                ['name' => 'Free Range Chicken', 'price' => 18.99, 'unit' => 'kg'],
                ['name' => 'Lamb Cutlets', 'price' => 45.99, 'unit' => 'kg'],
                ['name' => 'Pork Belly', 'price' => 22.99, 'unit' => 'kg'],
                ['name' => 'Beef Mince Premium', 'price' => 16.99, 'unit' => 'kg'],
            ],
            'Valley Dairy Products' => [
                ['name' => 'Full Cream Milk', 'price' => 4.50, 'unit' => '2L'],
                ['name' => 'Greek Yoghurt', 'price' => 8.99, 'unit' => '1kg'],
                ['name' => 'Cheddar Cheese', 'price' => 12.99, 'unit' => 'kg'],
                ['name' => 'Free Range Eggs', 'price' => 7.99, 'unit' => 'dozen'],
                ['name' => 'Butter Unsalted', 'price' => 6.99, 'unit' => '500g'],
            ]
        ];

        $products = $productTemplates[$vendor->business_name] ?? [];
        
        foreach ($products as $productData) {
            VendorProduct::create([
                'vendor_id' => $vendor->id,
                'name' => $productData['name'],
                'sku' => strtoupper($faker->bothify('???-#####')),
                'description' => $faker->paragraph(2),
                'price' => $productData['price'],
                'compare_price' => $productData['price'] * 1.2,
                'currency' => 'AUD',
                'unit' => $productData['unit'],
                'min_order_quantity' => rand(1, 10),
                'stock_quantity' => rand(100, 1000),
                'track_inventory' => true,
                'lead_time' => rand(1, 3) . ' days',
                'status' => 'active',
                'rating' => $faker->randomFloat(1, 3.5, 5),
                'review_count' => rand(5, 50),
                'order_count' => rand(20, 200),
                'bulk_pricing' => [
                    ['min_quantity' => 10, 'discount_percentage' => 5],
                    ['min_quantity' => 25, 'discount_percentage' => 10],
                    ['min_quantity' => 50, 'discount_percentage' => 15]
                ]
            ]);
        }
    }

    private function createCertificationsForVendor($vendor, $faker)
    {
        $certifications = [
            ['name' => 'HACCP Certification', 'type' => 'food_safety', 'issuing_authority' => 'SAI Global'],
            ['name' => 'Organic Certification', 'type' => 'organic', 'issuing_authority' => 'Australian Certified Organic'],
            ['name' => 'ISO 9001:2015', 'type' => 'quality', 'issuing_authority' => 'Standards Australia'],
            ['name' => 'SQF Certification', 'type' => 'food_safety', 'issuing_authority' => 'Safe Quality Food Institute'],
            ['name' => 'Halal Certification', 'type' => 'religious', 'issuing_authority' => 'Australian Halal Authority'],
        ];

        $selectedCerts = $faker->randomElements($certifications, rand(1, 3));
        
        foreach ($selectedCerts as $cert) {
            VendorCertification::create([
                'vendor_id' => $vendor->id,
                'name' => $cert['name'],
                'type' => $cert['type'],
                'issuing_authority' => $cert['issuing_authority'],
                'certificate_number' => strtoupper($faker->bothify('CERT-########')),
                'issue_date' => Carbon::now()->subMonths(rand(6, 24)),
                'expiry_date' => Carbon::now()->addMonths(rand(6, 24)),
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => Carbon::now()->subMonths(rand(1, 6)),
                'description' => $faker->sentence()
            ]);
        }
    }

    private function createPerformanceMetricsForVendor($vendor, $faker)
    {
        for ($i = 0; $i < 6; $i++) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            
            VendorPerformanceMetric::create([
                'vendor_id' => $vendor->id,
                'metric_date' => $date,
                'total_orders' => rand(50, 200),
                'completed_orders' => rand(45, 190),
                'cancelled_orders' => rand(0, 5),
                'returned_orders' => rand(0, 3),
                'total_revenue' => $faker->randomFloat(2, 10000, 100000),
                'average_order_value' => $faker->randomFloat(2, 200, 2000),
                'on_time_delivery_rate' => $faker->randomFloat(2, 85, 99),
                'defect_rate' => $faker->randomFloat(2, 0, 5),
                'customer_satisfaction_score' => $faker->randomFloat(1, 3.5, 5),
                'response_time_hours' => $faker->randomFloat(1, 0.5, 12),
                'disputes_raised' => rand(0, 5),
                'disputes_resolved' => rand(0, 5),
                'return_rate' => $faker->randomFloat(2, 0, 5),
                'new_customers' => rand(5, 30),
                'repeat_customers' => rand(20, 100)
            ]);
        }
    }

    private function createPricingTermsForVendor($vendor, $faker)
    {
        // Create general pricing term
        VendorPricingTerm::create([
            'vendor_id' => $vendor->id,
            'user_id' => null, // Available to all users
            'term_type' => 'volume_pricing',
            'name' => 'Volume Discount',
            'description' => 'Discounts for bulk orders',
            'volume_tiers' => [
                ['min_quantity' => 100, 'max_quantity' => 499, 'discount_percentage' => 5],
                ['min_quantity' => 500, 'max_quantity' => 999, 'discount_percentage' => 10],
                ['min_quantity' => 1000, 'max_quantity' => null, 'discount_percentage' => 15]
            ],
            'valid_from' => Carbon::now()->subMonth(),
            'valid_until' => Carbon::now()->addYear(),
            'is_active' => true,
            'status' => 'active'
        ]);

        // Create user-specific pricing term
        $users = User::limit(2)->get();
        foreach ($users as $user) {
            if (rand(0, 1)) {
                VendorPricingTerm::create([
                    'vendor_id' => $vendor->id,
                    'user_id' => $user->id,
                    'term_type' => 'discount',
                    'name' => 'Preferred Customer Discount',
                    'description' => 'Special pricing for valued customers',
                    'discount_percentage' => rand(5, 15),
                    'minimum_order_value' => rand(500, 2000),
                    'payment_terms_days' => rand(15, 45),
                    'valid_from' => Carbon::now()->subMonths(3),
                    'valid_until' => Carbon::now()->addMonths(9),
                    'is_active' => true,
                    'status' => 'active'
                ]);
            }
        }
    }

    private function createReviewsForVendor($vendor, $faker)
    {
        $users = User::limit(10)->get();
        
        foreach ($users as $user) {
            if (rand(0, 1)) {
                VendorReview::create([
                    'vendor_id' => $vendor->id,
                    'user_id' => $user->id,
                    'rating' => $faker->randomFloat(1, 3, 5),
                    'quality_rating' => $faker->randomFloat(1, 3, 5),
                    'delivery_rating' => $faker->randomFloat(1, 3, 5),
                    'service_rating' => $faker->randomFloat(1, 3, 5),
                    'price_rating' => $faker->randomFloat(1, 3, 5),
                    'title' => $faker->sentence(4),
                    'comment' => $faker->paragraph(3),
                    'verified_purchase' => rand(0, 1),
                    'is_anonymous' => rand(0, 1) ? false : true,
                    'helpful_count' => rand(0, 20),
                    'status' => 'approved',
                    'created_at' => Carbon::now()->subDays(rand(1, 90))
                ]);
            }
        }
    }
}