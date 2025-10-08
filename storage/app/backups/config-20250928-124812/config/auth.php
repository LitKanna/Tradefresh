<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        
        'vendor' => [
            'driver' => 'session',
            'provider' => 'vendors',
        ],
        
        'buyer' => [
            'driver' => 'session',
            'provider' => 'buyers',
        ],
        
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
        
        'admin-api' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        
        'vendor-api' => [
            'driver' => 'sanctum',
            'provider' => 'vendors',
        ],
        
        'buyer-api' => [
            'driver' => 'sanctum',
            'provider' => 'buyers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
        
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        
        'vendors' => [
            'driver' => 'eloquent',
            'model' => App\Models\Vendor::class,
        ],
        
        'buyers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Buyer::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        
        'admins' => [
            'provider' => 'admins',
            'table' => 'admin_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        
        'vendors' => [
            'provider' => 'vendors',
            'table' => 'vendor_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        
        'buyers' => [
            'provider' => 'buyers',
            'table' => 'buyer_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];