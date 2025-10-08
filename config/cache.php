<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        // High-performance cache for product catalog
        'products' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'products:',
            'serializer' => 'igbinary', // More efficient serialization
        ],

        // Session-based cache for user-specific data
        'sessions' => [
            'driver' => 'redis',
            'connection' => 'sessions',
            'prefix' => 'session:',
        ],

        // Fast cache for API responses
        'api' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'api:',
            'compress' => true, // Enable compression for large responses
        ],

        // Long-term cache for computed data
        'computed' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'computed:',
            'default_ttl' => 86400, // 24 hours default
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    */

    'tags' => [
        'products' => 'products',
        'orders' => 'orders',
        'users' => 'users',
        'catalog' => 'catalog',
        'pricing' => 'pricing',
        'inventory' => 'inventory',
        'analytics' => 'analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration for Different Data Types
    |--------------------------------------------------------------------------
    */

    'ttl' => [
        'products_list' => 300,        // 5 minutes
        'product_details' => 600,      // 10 minutes
        'product_search' => 600,       // 10 minutes
        'categories' => 3600,          // 1 hour
        'vendors' => 1800,             // 30 minutes
        'price_ranges' => 1800,        // 30 minutes
        'filter_options' => 3600,      // 1 hour
        'user_preferences' => 86400,   // 24 hours
        'analytics_daily' => 86400,    // 24 hours
        'analytics_hourly' => 3600,    // 1 hour
        'api_responses' => 300,        // 5 minutes
        'expensive_queries' => 1800,   // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Configuration
    |--------------------------------------------------------------------------
    */

    'warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        'schedule' => [
            'products' => '*/5 * * * *',      // Every 5 minutes
            'categories' => '0 */2 * * *',    // Every 2 hours
            'analytics' => '0 1 * * *',       // Daily at 1 AM
        ],
    ],

];