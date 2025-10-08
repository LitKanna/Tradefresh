<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sydney Markets Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Sydney Markets operations, locations,
    | and business rules.
    |
    */

    'locations' => [
        'main' => [
            'name' => 'Sydney Markets Flemington',
            'address' => '250-318 Parramatta Road, Flemington NSW 2140',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'phone' => '+61 2 9325 6200',
            'email' => 'info@sydneymarkets.com.au',
        ],
        'warehouses' => [
            'flemington' => [
                'name' => 'Flemington Warehouse',
                'code' => 'FLM',
                'zones' => ['A', 'B', 'C', 'D', 'E', 'F'],
            ],
            'homebush' => [
                'name' => 'Homebush Distribution',
                'code' => 'HMB',
                'zones' => ['North', 'South', 'East', 'West'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Market Operating Hours
    |--------------------------------------------------------------------------
    */
    
    'operating_hours' => [
        'growers_market' => [
            'monday' => ['start' => '03:00', 'end' => '09:00'],
            'tuesday' => ['start' => '03:00', 'end' => '09:00'],
            'wednesday' => ['start' => '03:00', 'end' => '09:00'],
            'thursday' => ['start' => '03:00', 'end' => '09:00'],
            'friday' => ['start' => '03:00', 'end' => '09:00'],
            'saturday' => ['start' => '03:00', 'end' => '09:00'],
            'sunday' => null, // Closed
        ],
        'flower_market' => [
            'monday' => ['start' => '04:00', 'end' => '09:00'],
            'tuesday' => ['start' => '04:00', 'end' => '09:00'],
            'wednesday' => ['start' => '04:00', 'end' => '09:00'],
            'thursday' => ['start' => '04:00', 'end' => '09:00'],
            'friday' => ['start' => '04:00', 'end' => '09:00'],
            'saturday' => ['start' => '05:00', 'end' => '10:00'],
            'sunday' => null, // Closed
        ],
        'wholesale_market' => [
            'monday' => ['start' => '02:00', 'end' => '10:00'],
            'tuesday' => ['start' => '02:00', 'end' => '10:00'],
            'wednesday' => ['start' => '02:00', 'end' => '10:00'],
            'thursday' => ['start' => '02:00', 'end' => '10:00'],
            'friday' => ['start' => '02:00', 'end' => '10:00'],
            'saturday' => ['start' => '03:00', 'end' => '11:00'],
            'sunday' => null, // Closed
        ],
        'office_hours' => [
            'monday' => ['start' => '08:00', 'end' => '17:00'],
            'tuesday' => ['start' => '08:00', 'end' => '17:00'],
            'wednesday' => ['start' => '08:00', 'end' => '17:00'],
            'thursday' => ['start' => '08:00', 'end' => '17:00'],
            'friday' => ['start' => '08:00', 'end' => '17:00'],
            'saturday' => null, // Closed
            'sunday' => null, // Closed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Categories
    |--------------------------------------------------------------------------
    */
    
    'categories' => [
        'fresh_produce' => [
            'name' => 'Fresh Produce',
            'subcategories' => [
                'fruits' => ['Citrus', 'Stone Fruit', 'Tropical', 'Berries', 'Melons', 'Apples & Pears'],
                'vegetables' => ['Leafy Greens', 'Root Vegetables', 'Tomatoes', 'Onions & Garlic', 'Asian Vegetables', 'Herbs'],
                'exotic' => ['Exotic Fruits', 'Exotic Vegetables', 'Microgreens'],
            ],
        ],
        'flowers' => [
            'name' => 'Flowers & Plants',
            'subcategories' => [
                'cut_flowers' => ['Roses', 'Lilies', 'Natives', 'Seasonal', 'Imported'],
                'plants' => ['Indoor Plants', 'Outdoor Plants', 'Succulents', 'Orchids'],
                'supplies' => ['Vases', 'Wrapping', 'Floral Foam', 'Ribbons'],
            ],
        ],
        'meat_seafood' => [
            'name' => 'Meat & Seafood',
            'subcategories' => [
                'meat' => ['Beef', 'Lamb', 'Pork', 'Poultry', 'Game'],
                'seafood' => ['Fish', 'Shellfish', 'Crustaceans', 'Processed Seafood'],
            ],
        ],
        'dairy_eggs' => [
            'name' => 'Dairy & Eggs',
            'subcategories' => [
                'dairy' => ['Milk', 'Cheese', 'Yogurt', 'Butter', 'Cream'],
                'eggs' => ['Chicken Eggs', 'Duck Eggs', 'Quail Eggs', 'Egg Products'],
            ],
        ],
        'dry_goods' => [
            'name' => 'Dry Goods',
            'subcategories' => [
                'grains' => ['Rice', 'Wheat', 'Pulses', 'Cereals'],
                'nuts_dried' => ['Nuts', 'Dried Fruits', 'Seeds'],
                'packaging' => ['Boxes', 'Bags', 'Containers', 'Labels'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Zones
    |--------------------------------------------------------------------------
    */
    
    'delivery_zones' => [
        'inner_sydney' => [
            'name' => 'Inner Sydney',
            'postcodes' => range(2000, 2050),
            'base_rate' => 15.00,
            'per_km_rate' => 2.00,
            'express_multiplier' => 1.5,
        ],
        'eastern_suburbs' => [
            'name' => 'Eastern Suburbs',
            'postcodes' => range(2021, 2036),
            'base_rate' => 20.00,
            'per_km_rate' => 2.50,
            'express_multiplier' => 1.5,
        ],
        'western_sydney' => [
            'name' => 'Western Sydney',
            'postcodes' => range(2140, 2200),
            'base_rate' => 25.00,
            'per_km_rate' => 2.00,
            'express_multiplier' => 1.5,
        ],
        'northern_beaches' => [
            'name' => 'Northern Beaches',
            'postcodes' => range(2084, 2108),
            'base_rate' => 30.00,
            'per_km_rate' => 3.00,
            'express_multiplier' => 1.75,
        ],
        'southern_sydney' => [
            'name' => 'Southern Sydney',
            'postcodes' => range(2216, 2234),
            'base_rate' => 25.00,
            'per_km_rate' => 2.50,
            'express_multiplier' => 1.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Settings
    |--------------------------------------------------------------------------
    */
    
    'delivery' => [
        'radius_km' => env('DELIVERY_RADIUS_KM', 50),
        'base_rate' => env('DELIVERY_BASE_RATE', 15.00),
        'per_km_rate' => env('DELIVERY_PER_KM_RATE', 2.50),
        'time_slots' => [
            'early_morning' => ['start' => '04:00', 'end' => '07:00', 'surcharge' => 10.00],
            'morning' => ['start' => '07:00', 'end' => '12:00', 'surcharge' => 0.00],
            'afternoon' => ['start' => '12:00', 'end' => '17:00', 'surcharge' => 0.00],
            'evening' => ['start' => '17:00', 'end' => '21:00', 'surcharge' => 5.00],
        ],
        'vehicle_types' => [
            'van' => ['capacity_kg' => 1000, 'rate_multiplier' => 1.0],
            'truck_small' => ['capacity_kg' => 3000, 'rate_multiplier' => 1.5],
            'truck_medium' => ['capacity_kg' => 8000, 'rate_multiplier' => 2.0],
            'truck_large' => ['capacity_kg' => 15000, 'rate_multiplier' => 3.0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vendor Types
    |--------------------------------------------------------------------------
    */
    
    'vendor_types' => [
        'grower' => [
            'name' => 'Grower/Producer',
            'requirements' => ['ABN', 'Food Safety Certificate'],
            'commission_rate' => 2.0,
        ],
        'wholesaler' => [
            'name' => 'Wholesaler',
            'requirements' => ['ABN', 'Business License', 'Insurance'],
            'commission_rate' => 2.5,
        ],
        'importer' => [
            'name' => 'Importer',
            'requirements' => ['ABN', 'Import License', 'Quarantine Clearance'],
            'commission_rate' => 3.0,
        ],
        'processor' => [
            'name' => 'Processor/Packager',
            'requirements' => ['ABN', 'Food Safety Certificate', 'HACCP Certification'],
            'commission_rate' => 2.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance & Regulations
    |--------------------------------------------------------------------------
    */
    
    'compliance' => [
        'food_safety_standards' => [
            'HACCP' => 'Hazard Analysis Critical Control Points',
            'SQF' => 'Safe Quality Food',
            'BRC' => 'British Retail Consortium',
            'ISO22000' => 'Food Safety Management',
        ],
        'required_licenses' => [
            'ABN' => 'Australian Business Number',
            'GST' => 'Goods and Services Tax Registration',
            'Food_License' => 'Food Business License',
        ],
        'insurance_requirements' => [
            'public_liability' => 20000000, // $20M
            'product_liability' => 10000000, // $10M
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Units of Measurement
    |--------------------------------------------------------------------------
    */
    
    'units' => [
        'weight' => [
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'tonne' => 'Tonnes',
            'lb' => 'Pounds',
        ],
        'quantity' => [
            'each' => 'Each',
            'dozen' => 'Dozen',
            'box' => 'Box',
            'carton' => 'Carton',
            'pallet' => 'Pallet',
            'punnet' => 'Punnet',
            'bunch' => 'Bunch',
            'tray' => 'Tray',
            'bag' => 'Bag',
            'crate' => 'Crate',
        ],
        'volume' => [
            'l' => 'Litres',
            'ml' => 'Millilitres',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Grades
    |--------------------------------------------------------------------------
    */
    
    'quality_grades' => [
        'premium' => [
            'name' => 'Premium/Export Quality',
            'code' => 'A+',
            'description' => 'Highest quality, suitable for export',
        ],
        'grade_a' => [
            'name' => 'Grade A',
            'code' => 'A',
            'description' => 'High quality, minimal defects',
        ],
        'grade_b' => [
            'name' => 'Grade B',
            'code' => 'B',
            'description' => 'Good quality, minor defects',
        ],
        'grade_c' => [
            'name' => 'Grade C',
            'code' => 'C',
            'description' => 'Standard quality, suitable for processing',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peak Seasons
    |--------------------------------------------------------------------------
    */
    
    'peak_seasons' => [
        'summer' => ['months' => [12, 1, 2], 'surcharge_percentage' => 0],
        'autumn' => ['months' => [3, 4, 5], 'surcharge_percentage' => 0],
        'winter' => ['months' => [6, 7, 8], 'surcharge_percentage' => 5],
        'spring' => ['months' => [9, 10, 11], 'surcharge_percentage' => 0],
        'christmas' => ['dates' => ['12-15', '12-24'], 'surcharge_percentage' => 15],
        'easter' => ['dynamic' => true, 'surcharge_percentage' => 10],
        'chinese_new_year' => ['dynamic' => true, 'surcharge_percentage' => 10],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Integration
    |--------------------------------------------------------------------------
    */
    
    'api' => [
        'key' => env('SYDNEY_MARKETS_API_KEY'),
        'secret' => env('SYDNEY_MARKETS_API_SECRET'),
        'base_url' => 'https://api.sydneymarkets.com.au/v1',
        'endpoints' => [
            'prices' => '/market-prices',
            'availability' => '/product-availability',
            'vendors' => '/vendors',
            'news' => '/market-news',
        ],
    ],
];