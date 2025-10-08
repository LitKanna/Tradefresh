<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class SydneyMarketsCompleteSeeder extends Seeder
{
    protected $faker;
    
    // Realistic Sydney Markets vendor data with valid ABNs
    protected $sydneyMarketsVendors = [
        [
            'abn' => '51824753556', // Fresh Produce Group
            'business_name' => 'Sydney Fresh Produce Group Pty Ltd',
            'trading_name' => 'Fresh & Best Produce',
            'categories' => ['Fruits', 'Vegetables'],
            'specialties' => ['Organic produce', 'Local farms', 'Seasonal fruits'],
        ],
        [
            'abn' => '89003783373', // Quality Meats
            'business_name' => 'Premium Meats Australia Pty Ltd',
            'trading_name' => 'Sydney Prime Meats',
            'categories' => ['Meat', 'Poultry'],
            'specialties' => ['Wagyu beef', 'Free-range poultry', 'Specialty cuts'],
        ],
        [
            'abn' => '75000024733', // Seafood Specialists
            'business_name' => 'Ocean Fresh Seafood Pty Ltd',
            'trading_name' => 'Sydney Seafood Market',
            'categories' => ['Seafood'],
            'specialties' => ['Fresh daily catch', 'Live seafood', 'Sashimi grade'],
        ],
        [
            'abn' => '64006580883', // Flower Power
            'business_name' => 'Blooms Wholesale Australia Pty Ltd',
            'trading_name' => 'Sydney Flower Markets',
            'categories' => ['Flowers', 'Plants'],
            'specialties' => ['Native flowers', 'Imported roses', 'Event arrangements'],
        ],
        [
            'abn' => '86082541465', // Asian Groceries
            'business_name' => 'Asian Food Importers Pty Ltd',
            'trading_name' => 'Asia Best Wholesale',
            'categories' => ['Asian Groceries', 'Specialty Foods'],
            'specialties' => ['Japanese ingredients', 'Korean products', 'Chinese herbs'],
        ],
        [
            'abn' => '31004085616', // Dairy Products
            'business_name' => 'Australian Dairy Excellence Pty Ltd',
            'trading_name' => 'Farm Fresh Dairy',
            'categories' => ['Dairy', 'Cheese'],
            'specialties' => ['Artisan cheese', 'Organic milk', 'Yogurt varieties'],
        ],
        [
            'abn' => '45002117818', // Bakery Supplies
            'business_name' => 'Master Bakers Supply Co Pty Ltd',
            'trading_name' => 'Bakery Wholesale Hub',
            'categories' => ['Bakery', 'Pastries'],
            'specialties' => ['Artisan breads', 'French pastries', 'Gluten-free options'],
        ],
        [
            'abn' => '93083380995', // Organic Specialists
            'business_name' => 'Certified Organic Traders Pty Ltd',
            'trading_name' => 'Pure Organic Markets',
            'categories' => ['Organic', 'Health Foods'],
            'specialties' => ['Certified organic', 'Biodynamic produce', 'Superfoods'],
        ],
    ];
    
    // Realistic buyer businesses
    protected $buyerBusinesses = [
        [
            'abn' => '48068213834', // Restaurant Chain
            'business_name' => 'Sydney Restaurant Group Pty Ltd',
            'trading_name' => 'Harbor View Restaurants',
            'type' => 'Restaurant Chain',
        ],
        [
            'abn' => '53004085497', // Supermarket
            'business_name' => 'Local Fresh Markets Pty Ltd',
            'trading_name' => 'FreshMart Supermarkets',
            'type' => 'Supermarket',
        ],
        [
            'abn' => '66000004466', // Hotel Group
            'business_name' => 'Sydney Hospitality Group Pty Ltd',
            'trading_name' => 'Harbour Hotels',
            'type' => 'Hotel Chain',
        ],
        [
            'abn' => '41001678779', // Catering Company
            'business_name' => 'Elite Catering Services Pty Ltd',
            'trading_name' => 'Sydney Elite Catering',
            'type' => 'Catering',
        ],
        [
            'abn' => '84002939050', // Cafe Chain
            'business_name' => 'Coffee Culture Australia Pty Ltd',
            'trading_name' => 'Bean Supreme Cafes',
            'type' => 'Cafe Chain',
        ],
    ];
    
    // Sydney Markets actual zones and bays layout
    protected $pickupZones = [
        'A' => ['name' => 'Building A - Fresh Produce', 'bays' => 30],
        'B' => ['name' => 'Building B - Flowers & Plants', 'bays' => 20],
        'C' => ['name' => 'Building C - Meat & Poultry', 'bays' => 25],
        'D' => ['name' => 'Building D - Seafood', 'bays' => 15],
        'E' => ['name' => 'Building E - Dairy & Deli', 'bays' => 20],
        'W' => ['name' => 'Warehouse - Dry Goods', 'bays' => 40],
    ];
    
    public function __construct()
    {
        $this->faker = Faker::create('en_AU');
    }
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedBusinessesAndUsers();
            $this->seedCategories();
            $this->seedProducts();
            $this->seedPickupSystem();
            $this->seedDeliveryZones();
            $this->seedOrders();
            $this->seedPaymentData();
            $this->seedCommunications();
            $this->seedAnalytics();
        });
        
        $this->command->info('Sydney Markets complete seeding finished successfully!');
    }
    
    /**
     * Seed businesses and users
     */
    protected function seedBusinessesAndUsers(): void
    {
        $this->command->info('Seeding businesses and users...');
        
        // Create vendor businesses
        foreach ($this->sydneyMarketsVendors as $vendorData) {
            $business = DB::table('businesses')->insertGetId([
                'abn' => $vendorData['abn'],
                'business_name' => $vendorData['business_name'],
                'trading_name' => $vendorData['trading_name'],
                'business_type' => 'vendor',
                'entity_type' => 'company',
                'abn_registration_date' => Carbon::now()->subYears(rand(5, 20)),
                'gst_registered' => true,
                'primary_email' => strtolower(str_replace(' ', '.', $vendorData['trading_name'])) . '@sydneymarkets.com.au',
                'phone' => '02 9' . rand(100, 999) . ' ' . rand(1000, 9999),
                'mobile' => '04' . rand(10000000, 99999999),
                'address_line1' => 'Sydney Markets',
                'address_line2' => 'Building ' . substr($vendorData['trading_name'], 0, 1),
                'suburb' => 'Flemington',
                'state' => 'NSW',
                'postcode' => '2140',
                'latitude' => -33.8688 + (rand(-100, 100) / 10000),
                'longitude' => 151.2093 + (rand(-100, 100) / 10000),
                'business_description' => 'Specializing in ' . implode(', ', $vendorData['specialties']),
                'business_hours' => json_encode([
                    'monday' => ['open' => '03:00', 'close' => '12:00'],
                    'tuesday' => ['open' => '03:00', 'close' => '12:00'],
                    'wednesday' => ['open' => '03:00', 'close' => '12:00'],
                    'thursday' => ['open' => '03:00', 'close' => '12:00'],
                    'friday' => ['open' => '03:00', 'close' => '12:00'],
                    'saturday' => ['open' => '05:00', 'close' => '11:00'],
                    'sunday' => 'closed',
                ]),
                'credit_limit' => rand(50000, 500000),
                'payment_terms_days' => 30,
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => Carbon::now()->subDays(rand(30, 365)),
                'subscription_tier' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
                'average_rating' => rand(40, 50) / 10,
                'total_reviews' => rand(50, 500),
                'total_orders' => rand(1000, 10000),
                'total_revenue' => rand(500000, 5000000),
                'on_time_delivery_rate' => rand(85, 100),
                'haccp_certified' => $this->faker->boolean(70),
                'organic_certified' => strpos($vendorData['business_name'], 'Organic') !== false,
                'created_at' => Carbon::now()->subDays(rand(365, 1825)),
                'updated_at' => Carbon::now(),
            ]);
            
            // Create vendor admin user
            DB::table('users')->insert([
                'business_id' => $business,
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'email' => 'admin@' . strtolower(str_replace(' ', '', $vendorData['trading_name'])) . '.com.au',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'phone' => '02 9' . rand(100, 999) . ' ' . rand(1000, 9999),
                'mobile' => '04' . rand(10000000, 99999999),
                'user_type' => 'vendor',
                'role' => 'admin',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            // Create additional vendor staff
            for ($i = 0; $i < rand(2, 5); $i++) {
                DB::table('users')->insert([
                    'business_id' => $business,
                    'first_name' => $this->faker->firstName,
                    'last_name' => $this->faker->lastName,
                    'email' => $this->faker->unique()->safeEmail,
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('password123'),
                    'phone' => '02 9' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'user_type' => 'vendor',
                    'role' => $this->faker->randomElement(['manager', 'sales', 'warehouse']),
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
        
        // Create buyer businesses
        foreach ($this->buyerBusinesses as $buyerData) {
            $business = DB::table('businesses')->insertGetId([
                'abn' => $buyerData['abn'],
                'business_name' => $buyerData['business_name'],
                'trading_name' => $buyerData['trading_name'],
                'business_type' => 'buyer',
                'entity_type' => 'company',
                'abn_registration_date' => Carbon::now()->subYears(rand(2, 15)),
                'gst_registered' => true,
                'primary_email' => strtolower(str_replace(' ', '.', $buyerData['trading_name'])) . '@business.com.au',
                'phone' => '02 ' . rand(8000, 9999) . ' ' . rand(1000, 9999),
                'mobile' => '04' . rand(10000000, 99999999),
                'address_line1' => $this->faker->streetAddress,
                'suburb' => $this->faker->randomElement(['Sydney', 'Parramatta', 'Chatswood', 'Bondi', 'Manly']),
                'state' => 'NSW',
                'postcode' => (string)rand(2000, 2200),
                'latitude' => -33.8688 + (rand(-500, 500) / 10000),
                'longitude' => 151.2093 + (rand(-500, 500) / 10000),
                'business_description' => $buyerData['type'] . ' - Serving Sydney since ' . Carbon::now()->subYears(rand(5, 20))->year,
                'credit_limit' => rand(10000, 100000),
                'payment_terms_days' => $this->faker->randomElement([7, 14, 30, 60]),
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => Carbon::now()->subDays(rand(30, 365)),
                'subscription_tier' => $this->faker->randomElement(['free', 'basic', 'premium']),
                'total_orders' => rand(100, 2000),
                'created_at' => Carbon::now()->subDays(rand(180, 730)),
                'updated_at' => Carbon::now(),
            ]);
            
            // Create buyer users
            for ($i = 0; $i < rand(1, 3); $i++) {
                DB::table('users')->insert([
                    'business_id' => $business,
                    'first_name' => $this->faker->firstName,
                    'last_name' => $this->faker->lastName,
                    'email' => $this->faker->unique()->companyEmail,
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('password123'),
                    'phone' => '02 ' . rand(8000, 9999) . ' ' . rand(1000, 9999),
                    'user_type' => 'buyer',
                    'role' => $i === 0 ? 'purchaser' : 'assistant',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
        
        // Create system admin
        DB::table('users')->insert([
            'business_id' => null,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@sydneymarkets.com.au',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('Admin@2025!'),
            'phone' => '02 9325 6200',
            'user_type' => 'admin',
            'role' => 'super_admin',
            'is_active' => true,
            'two_factor_enabled' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
    
    /**
     * Seed categories
     */
    protected function seedCategories(): void
    {
        $this->command->info('Seeding categories...');
        
        $categories = [
            'Fresh Produce' => [
                'Fruits' => ['Citrus', 'Stone Fruits', 'Berries', 'Tropical', 'Apples & Pears'],
                'Vegetables' => ['Leafy Greens', 'Root Vegetables', 'Herbs', 'Asian Vegetables', 'Organic'],
            ],
            'Meat & Poultry' => [
                'Beef' => ['Premium Cuts', 'Wagyu', 'Grass-fed', 'Aged Beef'],
                'Lamb' => ['Spring Lamb', 'Mutton', 'Specialty Cuts'],
                'Pork' => ['Free-range', 'Specialty Cuts'],
                'Poultry' => ['Chicken', 'Duck', 'Turkey', 'Game Birds'],
            ],
            'Seafood' => [
                'Fish' => ['Ocean Fish', 'River Fish', 'Sashimi Grade'],
                'Shellfish' => ['Prawns', 'Oysters', 'Lobster', 'Crab'],
                'Processed' => ['Smoked', 'Cured', 'Prepared'],
            ],
            'Dairy & Deli' => [
                'Milk Products' => ['Fresh Milk', 'Cream', 'Butter'],
                'Cheese' => ['Local', 'Imported', 'Artisan'],
                'Deli Meats' => ['Ham', 'Salami', 'Prosciutto'],
            ],
            'Flowers & Plants' => [
                'Cut Flowers' => ['Roses', 'Native', 'Seasonal', 'Imported'],
                'Plants' => ['Indoor', 'Outdoor', 'Herbs', 'Succulents'],
                'Supplies' => ['Vases', 'Wrapping', 'Accessories'],
            ],
        ];
        
        foreach ($categories as $mainName => $subcategories) {
            $mainId = DB::table('categories')->insertGetId([
                'name' => $mainName,
                'slug' => str_replace(' ', '-', strtolower($mainName)),
                'description' => 'High quality ' . strtolower($mainName) . ' from Sydney Markets',
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            foreach ($subcategories as $subName => $items) {
                $subId = DB::table('categories')->insertGetId([
                    'name' => $subName,
                    'slug' => str_replace(' ', '-', strtolower($mainName . '-' . $subName)),
                    'parent_id' => $mainId,
                    'description' => 'Fresh ' . strtolower($subName),
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                
                foreach ($items as $index => $itemName) {
                    DB::table('categories')->insert([
                        'name' => $itemName,
                        'slug' => str_replace(' ', '-', strtolower($mainName . '-' . $subName . '-' . $itemName)),
                        'parent_id' => $subId,
                        'description' => 'Premium ' . strtolower($itemName),
                        'sort_order' => $index + 2,
                        'is_active' => true,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }
    }
    
    /**
     * Seed products
     */
    protected function seedProducts(): void
    {
        $this->command->info('Seeding products...');
        
        $vendors = DB::table('businesses')->where('business_type', 'vendor')->get();
        $categories = DB::table('categories')->whereNotNull('parent_id')->get();
        
        $productTemplates = [
            'Fruits' => [
                ['name' => 'Granny Smith Apples', 'unit' => 'kg', 'price_range' => [3, 6]],
                ['name' => 'Cavendish Bananas', 'unit' => 'kg', 'price_range' => [2, 5]],
                ['name' => 'Valencia Oranges', 'unit' => 'kg', 'price_range' => [2, 4]],
                ['name' => 'Strawberries', 'unit' => 'punnet', 'price_range' => [3, 7]],
                ['name' => 'Hass Avocados', 'unit' => 'each', 'price_range' => [2, 4]],
            ],
            'Vegetables' => [
                ['name' => 'Iceberg Lettuce', 'unit' => 'each', 'price_range' => [2, 4]],
                ['name' => 'Roma Tomatoes', 'unit' => 'kg', 'price_range' => [3, 6]],
                ['name' => 'Carrots', 'unit' => 'kg', 'price_range' => [2, 4]],
                ['name' => 'Broccoli', 'unit' => 'kg', 'price_range' => [4, 8]],
                ['name' => 'Red Capsicum', 'unit' => 'kg', 'price_range' => [6, 12]],
            ],
            'Beef' => [
                ['name' => 'Ribeye Steak', 'unit' => 'kg', 'price_range' => [35, 55]],
                ['name' => 'Beef Mince', 'unit' => 'kg', 'price_range' => [12, 18]],
                ['name' => 'T-Bone Steak', 'unit' => 'kg', 'price_range' => [28, 42]],
                ['name' => 'Beef Tenderloin', 'unit' => 'kg', 'price_range' => [45, 80]],
                ['name' => 'Chuck Roast', 'unit' => 'kg', 'price_range' => [15, 25]],
            ],
            'Fish' => [
                ['name' => 'Atlantic Salmon', 'unit' => 'kg', 'price_range' => [28, 38]],
                ['name' => 'Barramundi Fillets', 'unit' => 'kg', 'price_range' => [22, 32]],
                ['name' => 'King Prawns', 'unit' => 'kg', 'price_range' => [25, 45]],
                ['name' => 'Sydney Rock Oysters', 'unit' => 'dozen', 'price_range' => [18, 30]],
                ['name' => 'Blue Swimmer Crab', 'unit' => 'kg', 'price_range' => [35, 55]],
            ],
        ];
        
        foreach ($vendors as $vendor) {
            // Select random categories for this vendor
            $vendorCategories = $categories->random(rand(3, 8));
            
            foreach ($vendorCategories as $category) {
                // Get product templates for this category type
                $categoryType = explode('-', $category->slug)[1] ?? 'Fruits';
                $templates = $productTemplates[$categoryType] ?? $productTemplates['Fruits'];
                
                foreach ($templates as $template) {
                    $basePrice = rand($template['price_range'][0] * 100, $template['price_range'][1] * 100) / 100;
                    $sku = strtoupper(substr($vendor->trading_name, 0, 3)) . '-' . rand(10000, 99999);
                    
                    $productId = DB::table('products')->insertGetId([
                        'business_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $template['name'] . ' - ' . $vendor->trading_name,
                        'slug' => str_replace(' ', '-', strtolower($template['name'] . '-' . $vendor->id . '-' . rand(1000, 9999))),
                        'sku' => $sku,
                        'barcode' => '936' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT),
                        'description' => 'Premium quality ' . $template['name'] . ' sourced from the best suppliers',
                        'short_description' => 'Fresh ' . $template['name'],
                        'base_price' => $basePrice,
                        'compare_price' => $basePrice * 1.2,
                        'cost_price' => $basePrice * 0.7,
                        'price_unit' => $template['unit'],
                        'is_negotiable' => $this->faker->boolean(30),
                        'stock_quantity' => rand(100, 5000),
                        'min_order_quantity' => $template['unit'] === 'kg' ? 5 : 1,
                        'max_order_quantity' => $template['unit'] === 'kg' ? 500 : 100,
                        'step_quantity' => $template['unit'] === 'kg' ? 5 : 1,
                        'track_inventory' => true,
                        'allow_backorder' => $this->faker->boolean(40),
                        'low_stock_threshold' => rand(10, 50),
                        'unit_of_measure' => $template['unit'],
                        'weight' => $template['unit'] === 'kg' ? 1 : rand(100, 500) / 1000,
                        'weight_unit' => 'kg',
                        'country_of_origin' => $this->faker->randomElement(['Australia', 'New Zealand', 'USA', 'China', 'Vietnam']),
                        'brand' => $vendor->trading_name,
                        'status' => 'active',
                        'is_featured' => $this->faker->boolean(20),
                        'is_organic' => $vendor->organic_certified && $this->faker->boolean(40),
                        'average_rating' => rand(35, 50) / 10,
                        'review_count' => rand(5, 200),
                        'order_count' => rand(50, 1000),
                        'created_at' => Carbon::now()->subDays(rand(30, 365)),
                        'updated_at' => Carbon::now(),
                    ]);
                    
                    // Add bulk pricing tiers
                    $tierQuantities = [10, 50, 100, 500];
                    foreach ($tierQuantities as $index => $qty) {
                        if ($template['unit'] === 'kg') {
                            $qty *= 5;
                        }
                        
                        DB::table('product_price_tiers')->insert([
                            'product_id' => $productId,
                            'min_quantity' => $qty,
                            'max_quantity' => $index < count($tierQuantities) - 1 ? $tierQuantities[$index + 1] * ($template['unit'] === 'kg' ? 5 : 1) - 1 : null,
                            'price' => $basePrice * (1 - (0.05 * ($index + 1))),
                            'discount_percentage' => 5 * ($index + 1),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Seed pickup system
     */
    protected function seedPickupSystem(): void
    {
        $this->command->info('Seeding pickup system...');
        
        // Create zones
        foreach ($this->pickupZones as $code => $zoneData) {
            $zoneId = DB::table('pickup_zones')->insertGetId([
                'name' => $zoneData['name'],
                'code' => $code,
                'description' => 'Loading zone for ' . $zoneData['name'],
                'location_description' => 'Located at ' . $zoneData['name'] . ', Sydney Markets Flemington',
                'total_bays' => $zoneData['bays'],
                'is_active' => true,
                'operating_hours' => json_encode([
                    'weekdays' => ['open' => '03:00', 'close' => '12:00'],
                    'saturday' => ['open' => '05:00', 'close' => '11:00'],
                    'sunday' => 'closed',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            // Create bays for each zone
            for ($i = 1; $i <= $zoneData['bays']; $i++) {
                $bayType = 'standard';
                if (strpos($zoneData['name'], 'Meat') !== false || strpos($zoneData['name'], 'Seafood') !== false) {
                    $bayType = 'refrigerated';
                } elseif (strpos($zoneData['name'], 'Dairy') !== false) {
                    $bayType = $this->faker->randomElement(['refrigerated', 'frozen']);
                }
                
                DB::table('pickup_bays')->insert([
                    'zone_id' => $zoneId,
                    'bay_number' => str_pad($i, 2, '0', STR_PAD_LEFT),
                    'bay_code' => $code . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'size' => $this->faker->randomElement(['small', 'medium', 'large', 'xlarge']),
                    'type' => $bayType,
                    'is_covered' => true,
                    'has_loading_dock' => $i <= 5, // First 5 bays have loading docks
                    'max_weight_kg' => rand(1000, 5000),
                    'max_height_m' => rand(3, 5),
                    'is_active' => true,
                    'status' => $this->faker->randomElement(['available', 'available', 'available', 'occupied']),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
        
        // Create time slots
        $slots = [
            ['03:00', '04:00'], ['04:00', '05:00'], ['05:00', '06:00'],
            ['06:00', '07:00'], ['07:00', '08:00'], ['08:00', '09:00'],
            ['09:00', '10:00'], ['10:00', '11:00'], ['11:00', '12:00'],
        ];
        
        foreach ($slots as $slot) {
            DB::table('pickup_time_slots')->insert([
                'start_time' => $slot[0],
                'end_time' => $slot[1],
                'duration_minutes' => 60,
                'max_bookings' => rand(15, 30),
                'available_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                'is_peak' => in_array($slot[0], ['06:00', '07:00', '08:00']),
                'peak_surcharge' => in_array($slot[0], ['06:00', '07:00', '08:00']) ? 10 : 0,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
    
    /**
     * Seed delivery zones
     */
    protected function seedDeliveryZones(): void
    {
        $this->command->info('Seeding delivery zones...');
        
        $zones = [
            [
                'name' => 'Sydney CBD',
                'code' => 'CBD',
                'postcodes' => ['2000', '2001', '2007', '2008', '2009', '2010'],
                'suburbs' => ['Sydney', 'The Rocks', 'Barangaroo', 'Darling Harbour'],
                'base_fee' => 25,
                'per_km' => 2.5,
                'minutes' => 30,
            ],
            [
                'name' => 'Eastern Suburbs',
                'code' => 'EAST',
                'postcodes' => ['2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030'],
                'suburbs' => ['Paddington', 'Bondi', 'Coogee', 'Randwick', 'Maroubra', 'Vaucluse', 'Dover Heights'],
                'base_fee' => 30,
                'per_km' => 2.8,
                'minutes' => 45,
            ],
            [
                'name' => 'Inner West',
                'code' => 'IWEST',
                'postcodes' => ['2037', '2038', '2039', '2040', '2041', '2042', '2043', '2044', '2045', '2046'],
                'suburbs' => ['Glebe', 'Annandale', 'Balmain', 'Leichhardt', 'Haberfield', 'Newtown', 'Enmore'],
                'base_fee' => 20,
                'per_km' => 2.0,
                'minutes' => 25,
            ],
            [
                'name' => 'North Shore',
                'code' => 'NORTH',
                'postcodes' => ['2060', '2061', '2062', '2063', '2064', '2065', '2066', '2067', '2068', '2069'],
                'suburbs' => ['North Sydney', 'Milsons Point', 'Cammeray', 'Northbridge', 'Artarmon', 'Crows Nest'],
                'base_fee' => 35,
                'per_km' => 3.0,
                'minutes' => 40,
            ],
            [
                'name' => 'Western Sydney',
                'code' => 'WEST',
                'postcodes' => ['2140', '2141', '2142', '2143', '2144', '2145', '2146', '2147', '2148', '2150'],
                'suburbs' => ['Homebush', 'Silverwater', 'Granville', 'Parramatta', 'Westmead', 'Wentworthville'],
                'base_fee' => 15,
                'per_km' => 1.8,
                'minutes' => 20,
            ],
        ];
        
        foreach ($zones as $zone) {
            DB::table('delivery_zones')->insert([
                'name' => $zone['name'],
                'code' => $zone['code'],
                'postcodes' => json_encode($zone['postcodes']),
                'suburbs' => json_encode($zone['suburbs']),
                'base_delivery_fee' => $zone['base_fee'],
                'per_km_rate' => $zone['per_km'],
                'estimated_minutes' => $zone['minutes'],
                'express_available' => true,
                'express_surcharge' => 15,
                'is_active' => true,
                'delivery_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'cutoff_times' => json_encode([
                    'standard' => '14:00',
                    'express' => '10:00',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        // Create delivery drivers
        $driverUsers = DB::table('users')
            ->where('user_type', 'buyer')
            ->limit(5)
            ->get();
            
        foreach ($driverUsers as $index => $user) {
            DB::table('delivery_drivers')->insert([
                'user_id' => $user->id,
                'driver_code' => 'DRV' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'license_number' => 'NSW' . rand(100000, 999999),
                'license_expiry' => Carbon::now()->addYears(rand(1, 5)),
                'vehicle_type' => $this->faker->randomElement(['van', 'truck', 'refrigerated_truck']),
                'vehicle_registration' => strtoupper($this->faker->bothify('???###')),
                'vehicle_make' => $this->faker->randomElement(['Toyota', 'Ford', 'Mercedes', 'Isuzu']),
                'vehicle_model' => $this->faker->randomElement(['HiAce', 'Transit', 'Sprinter', 'NPR']),
                'vehicle_year' => rand(2018, 2024),
                'max_load_kg' => rand(1000, 3000),
                'has_refrigeration' => $this->faker->boolean(60),
                'has_freezer' => $this->faker->boolean(30),
                'status' => 'available',
                'current_latitude' => -33.8688 + (rand(-100, 100) / 1000),
                'current_longitude' => 151.2093 + (rand(-100, 100) / 1000),
                'last_location_update' => Carbon::now(),
                'total_deliveries' => rand(100, 1000),
                'on_time_rate' => rand(85, 100),
                'average_rating' => rand(40, 50) / 10,
                'total_ratings' => rand(50, 500),
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'shift_start' => '06:00',
                'shift_end' => '18:00',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
    
    /**
     * Seed orders
     */
    protected function seedOrders(): void
    {
        $this->command->info('Seeding orders...');
        
        $vendors = DB::table('businesses')->where('business_type', 'vendor')->get();
        $buyers = DB::table('businesses')->where('business_type', 'buyer')->get();
        $products = DB::table('products')->where('status', 'active')->get();
        $bays = DB::table('pickup_bays')->where('status', 'available')->get();
        $slots = DB::table('pickup_time_slots')->get();
        
        // Create historical orders
        for ($i = 0; $i < 500; $i++) {
            $vendor = $vendors->random();
            $buyer = $buyers->random();
            $orderDate = Carbon::now()->subDays(rand(1, 180));
            $deliveryMethod = $this->faker->randomElement(['pickup', 'delivery']);
            
            $buyerUser = DB::table('users')
                ->where('business_id', $buyer->id)
                ->where('user_type', 'buyer')
                ->first();
            
            if (!$buyerUser) {
                continue;
            }
            
            $subtotal = 0;
            $status = $this->faker->randomElement([
                'completed', 'completed', 'completed', 'delivered', 
                'processing', 'confirmed', 'cancelled'
            ]);
            
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => 'ORD-' . date('Ymd', $orderDate->timestamp) . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'buyer_business_id' => $buyer->id,
                'vendor_business_id' => $vendor->id,
                'placed_by_user_id' => $buyerUser->id,
                'order_type' => $this->faker->randomElement(['standard', 'express', 'scheduled']),
                'status' => $status,
                'order_date' => $orderDate,
                'requested_delivery_date' => $orderDate->copy()->addDays(rand(1, 3)),
                'confirmed_at' => in_array($status, ['confirmed', 'processing', 'delivered', 'completed']) ? $orderDate->copy()->addHours(rand(1, 4)) : null,
                'delivered_at' => in_array($status, ['delivered', 'completed']) ? $orderDate->copy()->addDays(rand(1, 3)) : null,
                'completed_at' => $status === 'completed' ? $orderDate->copy()->addDays(rand(2, 5)) : null,
                'delivery_method' => $deliveryMethod,
                'pickup_bay_id' => $deliveryMethod === 'pickup' ? $bays->random()->id : null,
                'pickup_slot_id' => $deliveryMethod === 'pickup' ? $slots->random()->id : null,
                'payment_status' => in_array($status, ['completed', 'delivered']) ? 'paid' : 'pending',
                'payment_method' => $this->faker->randomElement(['credit', 'card', 'bank_transfer']),
                'is_urgent' => $this->faker->boolean(10),
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);
            
            // Add order items
            $vendorProducts = $products->where('business_id', $vendor->id)->take(rand(2, 8));
            
            foreach ($vendorProducts as $product) {
                $quantity = rand(5, 100);
                $unitPrice = $product->base_price;
                $totalPrice = $quantity * $unitPrice;
                $subtotal += $totalPrice;
                
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_of_measure' => $product->unit_of_measure,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'status' => in_array($status, ['delivered', 'completed']) ? 'delivered' : 'pending',
                    'quantity_delivered' => in_array($status, ['delivered', 'completed']) ? $quantity : 0,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
            }
            
            // Update order totals
            $taxAmount = $subtotal * 0.1; // 10% GST
            $deliveryFee = $deliveryMethod === 'delivery' ? rand(15, 50) : 0;
            $totalAmount = $subtotal + $taxAmount + $deliveryFee;
            
            DB::table('orders')
                ->where('id', $orderId)
                ->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $totalAmount,
                    'amount_paid' => in_array($status, ['completed', 'delivered']) ? $totalAmount : 0,
                    'paid_at' => in_array($status, ['completed', 'delivered']) ? $orderDate->copy()->addDays(rand(7, 30)) : null,
                ]);
            
            // Create pickup booking if pickup method
            if ($deliveryMethod === 'pickup' && !in_array($status, ['cancelled'])) {
                DB::table('pickup_bookings')->insert([
                    'booking_reference' => 'PB-' . strtoupper($this->faker->bothify('????####')),
                    'order_id' => $orderId,
                    'bay_id' => $bays->random()->id,
                    'slot_id' => $slots->random()->id,
                    'booked_by_user_id' => $buyerUser->id,
                    'pickup_date' => $orderDate->copy()->addDays(1)->format('Y-m-d'),
                    'scheduled_start_time' => $slots->random()->start_time,
                    'scheduled_end_time' => $slots->random()->end_time,
                    'vehicle_type' => $this->faker->randomElement(['van', 'truck', 'ute']),
                    'vehicle_registration' => strtoupper($this->faker->bothify('???###')),
                    'driver_name' => $this->faker->name,
                    'driver_phone' => '04' . rand(10000000, 99999999),
                    'status' => in_array($status, ['delivered', 'completed']) ? 'completed' : 'confirmed',
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
            }
        }
    }
    
    /**
     * Seed payment data
     */
    protected function seedPaymentData(): void
    {
        $this->command->info('Seeding payment data...');
        
        $paidOrders = DB::table('orders')->where('payment_status', 'paid')->get();
        
        foreach ($paidOrders as $order) {
            // Create invoice
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_number' => 'INV-' . date('Ym') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'business_id' => $order->buyer_business_id,
                'order_id' => $order->id,
                'invoice_date' => Carbon::parse($order->order_date)->addDays(1),
                'due_date' => Carbon::parse($order->order_date)->addDays(30),
                'status' => 'paid',
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'amount_paid' => $order->total_amount,
                'balance_due' => 0,
                'paid_at' => $order->paid_at,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);
            
            // Create payment transaction
            DB::table('payment_transactions')->insert([
                'transaction_id' => 'TXN-' . uniqid(),
                'payable_type' => 'App\\Models\\Order',
                'payable_id' => $order->id,
                'business_id' => $order->buyer_business_id,
                'type' => 'payment',
                'status' => 'completed',
                'amount' => $order->total_amount,
                'payment_method' => $order->payment_method === 'card' ? 'credit_card' : $order->payment_method,
                'gateway' => $order->payment_method === 'card' ? 'stripe' : null,
                'gateway_transaction_id' => $order->payment_method === 'card' ? 'stripe_' . uniqid() : null,
                'reference' => $order->order_number,
                'description' => 'Payment for order ' . $order->order_number,
                'created_at' => $order->paid_at,
                'updated_at' => $order->paid_at,
            ]);
        }
        
        // Create credit accounts for buyers
        $buyers = DB::table('businesses')->where('business_type', 'buyer')->get();
        
        foreach ($buyers as $buyer) {
            DB::table('credit_accounts')->insert([
                'business_id' => $buyer->id,
                'credit_limit' => $buyer->credit_limit,
                'available_credit' => $buyer->credit_limit * 0.7,
                'used_credit' => $buyer->credit_limit * 0.3,
                'payment_terms_days' => $buyer->payment_terms_days,
                'next_review_date' => Carbon::now()->addMonths(6),
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
    
    /**
     * Seed communications
     */
    protected function seedCommunications(): void
    {
        $this->command->info('Seeding communications...');
        
        $users = DB::table('users')->where('is_active', true)->get();
        $orders = DB::table('orders')->limit(50)->get();
        
        // Create notifications for recent orders
        foreach ($orders as $order) {
            $buyer = DB::table('users')->where('business_id', $order->buyer_business_id)->first();
            $vendor = DB::table('users')->where('business_id', $order->vendor_business_id)->first();
            
            if ($buyer) {
                DB::table('notifications')->insert([
                    'id_hash' => md5(uniqid()),
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $buyer->id,
                    'type' => 'OrderConfirmation',
                    'channel' => 'database',
                    'title' => 'Order Confirmed',
                    'message' => 'Your order ' . $order->order_number . ' has been confirmed',
                    'data' => json_encode(['order_id' => $order->id]),
                    'priority' => 'normal',
                    'sent_at' => $order->confirmed_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
            
            if ($vendor) {
                DB::table('notifications')->insert([
                    'id_hash' => md5(uniqid()),
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $vendor->id,
                    'type' => 'NewOrder',
                    'channel' => 'database',
                    'title' => 'New Order Received',
                    'message' => 'You have received a new order ' . $order->order_number,
                    'data' => json_encode(['order_id' => $order->id]),
                    'priority' => 'high',
                    'sent_at' => $order->created_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }
        
        // Create sample messages between users
        for ($i = 0; $i < 100; $i++) {
            $sender = $users->random();
            $recipient = $users->where('id', '!=', $sender->id)->random();
            
            DB::table('messages')->insert([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'subject' => $this->faker->sentence,
                'body' => $this->faker->paragraphs(rand(1, 3), true),
                'is_read' => $this->faker->boolean(70),
                'read_at' => $this->faker->boolean(70) ? Carbon::now()->subDays(rand(1, 30)) : null,
                'is_important' => $this->faker->boolean(20),
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
    
    /**
     * Seed analytics
     */
    protected function seedAnalytics(): void
    {
        $this->command->info('Seeding analytics data...');
        
        $users = DB::table('users')->get();
        $products = DB::table('products')->limit(100)->get();
        
        // Product view events
        for ($i = 0; $i < 1000; $i++) {
            $product = $products->random();
            $user = $this->faker->boolean(80) ? $users->random() : null;
            
            DB::table('analytics_events')->insert([
                'event_type' => 'product_view',
                'event_category' => 'engagement',
                'trackable_type' => 'App\\Models\\Product',
                'trackable_id' => $product->id,
                'user_id' => $user ? $user->id : null,
                'session_id' => $this->faker->uuid,
                'properties' => json_encode([
                    'source' => $this->faker->randomElement(['search', 'category', 'direct', 'recommendation']),
                    'device' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
                ]),
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'occurred_at' => Carbon::now()->subDays(rand(1, 30)),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        // Activity logs
        $activities = ['login', 'logout', 'order_placed', 'product_updated', 'profile_updated'];
        
        for ($i = 0; $i < 500; $i++) {
            $user = $users->random();
            
            DB::table('activity_logs')->insert([
                'log_name' => 'user_activity',
                'description' => $this->faker->randomElement($activities),
                'subject_type' => 'App\\Models\\User',
                'subject_id' => $user->id,
                'causer_type' => 'App\\Models\\User',
                'causer_id' => $user->id,
                'properties' => json_encode([
                    'ip' => $this->faker->ipv4,
                    'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                ]),
                'event' => $this->faker->randomElement($activities),
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}