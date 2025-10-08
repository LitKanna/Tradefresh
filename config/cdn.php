<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration for Sydney Markets B2B
    |--------------------------------------------------------------------------
    |
    | Configure your CDN settings here. When enabled, all assets will be
    | served from the CDN instead of your local server.
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'url' => env('CDN_URL', 'https://cdn.sydneymarkets.com'),

    /*
    |--------------------------------------------------------------------------
    | CDN Providers
    |--------------------------------------------------------------------------
    |
    | Popular CDN options for production deployment
    |
    */
    'providers' => [
        'cloudflare' => [
            'url' => 'https://cdn.cloudflare.com',
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
        ],
        'bunnycdn' => [
            'url' => env('BUNNY_CDN_URL', 'https://sydneymarkets.b-cdn.net'),
            'storage_zone' => env('BUNNY_STORAGE_ZONE'),
            'api_key' => env('BUNNY_API_KEY'),
        ],
        'aws_cloudfront' => [
            'distribution_id' => env('AWS_CLOUDFRONT_DISTRIBUTION_ID'),
            'url' => env('AWS_CLOUDFRONT_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for automatic asset optimization
    |
    */
    'optimization' => [
        'minify_css' => true,
        'minify_js' => true,
        'compress_images' => true,
        'convert_to_webp' => true,
        'lazy_load_images' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Control Headers
    |--------------------------------------------------------------------------
    |
    | Configure cache durations for different asset types (in seconds)
    |
    */
    'cache_control' => [
        'css' => 31536000, // 1 year
        'js' => 31536000, // 1 year
        'images' => 2592000, // 30 days
        'fonts' => 31536000, // 1 year
        'documents' => 86400, // 1 day
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Paths that should not be served from CDN
    |
    */
    'exclude' => [
        'api/*',
        'admin/*',
        'livewire/*',
        '*.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preload Assets
    |--------------------------------------------------------------------------
    |
    | Critical assets to preload for better performance
    |
    */
    'preload' => [
        'css' => [
            'build/assets/css/app.css',
            'assets/css/global/global-professional.css',
        ],
        'js' => [
            'build/assets/js/app.js',
        ],
        'fonts' => [
            // Add font files here
        ],
    ],
];
