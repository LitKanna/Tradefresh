<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment gateway integration including
    | API keys, webhook settings, and marketplace configuration.
    |
    */

    // API Keys
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    
    // Webhook Secret
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
    
    // Stripe Connect Settings (Marketplace)
    'connect' => [
        'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'redirect_uri' => env('STRIPE_CONNECT_REDIRECT_URI', '/stripe/connect/callback'),
    ],
    
    // Platform Configuration
    'platform' => [
        'name' => env('STRIPE_PLATFORM_NAME', 'Sydney Markets'),
        'commission_percentage' => env('STRIPE_COMMISSION_PERCENTAGE', 10), // 10% default commission
        'currency' => env('STRIPE_CURRENCY', 'aud'),
        'country' => env('STRIPE_COUNTRY', 'AU'),
    ],
    
    // Payment Methods
    'payment_methods' => [
        'card' => true,
        'au_becs_debit' => true, // Australian bank transfers
        'afterpay_clearpay' => true, // Buy now, pay later
    ],
    
    // Credit Terms
    'credit_terms' => [
        'enabled' => true,
        'days' => [7, 14, 30, 60],
        'late_fee_percentage' => 2, // 2% late fee
        'grace_period_days' => 3,
    ],
    
    // Payout Settings
    'payouts' => [
        'schedule' => [
            'interval' => 'daily', // daily, weekly, monthly
            'delay_days' => 2, // Days to hold funds before payout
        ],
        'minimum_amount' => 1000, // Minimum payout amount in cents (AUD $10)
    ],
    
    // Fee Structure
    'fees' => [
        'card' => [
            'percentage' => 1.75,
            'fixed' => 30, // in cents
        ],
        'bank_transfer' => [
            'percentage' => 0.8,
            'fixed' => 0,
        ],
        'afterpay' => [
            'percentage' => 4.0,
            'fixed' => 30,
        ],
    ],
    
    // Invoice Settings
    'invoice' => [
        'auto_advance' => true,
        'collection_method' => 'send_invoice',
        'days_until_due' => 7,
        'footer' => 'Thank you for your business with Sydney Markets',
    ],
];