<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketplace Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for the B2B wholesale
    | marketplace platform.
    |
    */

    'name' => env('APP_NAME', 'Sydney Markets B2B'),
    
    'currency' => env('MARKETPLACE_CURRENCY', 'AUD'),
    
    'timezone' => env('MARKETPLACE_TIMEZONE', 'Australia/Sydney'),
    
    'default_language' => env('MARKETPLACE_DEFAULT_LANGUAGE', 'en'),
    
    'supported_languages' => ['en', 'zh', 'ar', 'vi', 'ko'],

    /*
    |--------------------------------------------------------------------------
    | Commission & Fees
    |--------------------------------------------------------------------------
    */
    
    'commission' => [
        'rate' => env('MARKETPLACE_COMMISSION_RATE', 2.5), // Percentage
        'min_amount' => 5.00,
        'max_amount' => 1000.00,
    ],
    
    'transaction_fees' => [
        'stripe' => [
            'percentage' => 2.9,
            'fixed' => 0.30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RFQ (Request for Quote) Settings
    |--------------------------------------------------------------------------
    */
    
    'rfq' => [
        'auto_expire_days' => 7,
        'max_quotes_per_rfq' => 10,
        'min_quantity' => 1,
        'max_attachments' => 5,
        'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'doc', 'docx'],
        'max_file_size' => 10240, // KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Quote Settings
    |--------------------------------------------------------------------------
    */
    
    'quotes' => [
        'validity_days' => 30,
        'auto_reminder_days' => [7, 3, 1],
        'require_deposit' => false,
        'deposit_percentage' => 20,
        'allow_partial_acceptance' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Settings
    |--------------------------------------------------------------------------
    */
    
    'orders' => [
        'auto_cancel_unpaid_hours' => 48,
        'min_order_value' => 100.00,
        'max_order_value' => 1000000.00,
        'require_delivery_confirmation' => true,
        'allow_partial_delivery' => true,
        'invoice_prefix' => 'SM',
        'order_number_prefix' => 'ORD',
        'order_number_padding' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    
    'payments' => [
        'methods' => ['stripe', 'bank_transfer', 'cash_on_delivery'],
        'default_method' => 'stripe',
        'hold_period_days' => 7, // Days to hold payment before releasing to vendor
        'allow_split_payments' => true,
        'require_upfront_payment' => false,
        'payment_terms' => [
            'net_7' => 7,
            'net_14' => 14,
            'net_30' => 30,
            'net_60' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Roles & Permissions
    |--------------------------------------------------------------------------
    */
    
    'roles' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'vendor' => 'Vendor',
        'buyer' => 'Buyer',
        'delivery_partner' => 'Delivery Partner',
    ],
    
    'vendor' => [
        'auto_approve' => true, // Set to true for testing/demo purposes
        'require_verification' => true,
        'verification_documents' => ['abn', 'business_license', 'insurance'],
        'subscription_plans' => [
            'basic' => [
                'name' => 'Basic',
                'monthly_fee' => 0,
                'commission_rate' => 3.5,
                'max_products' => 100,
                'max_rfq_responses' => 50,
            ],
            'professional' => [
                'name' => 'Professional',
                'monthly_fee' => 99,
                'commission_rate' => 2.5,
                'max_products' => 500,
                'max_rfq_responses' => 200,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'monthly_fee' => 299,
                'commission_rate' => 1.5,
                'max_products' => null,
                'max_rfq_responses' => null,
            ],
        ],
    ],
    
    'buyer' => [
        'auto_approve' => true,
        'require_verification' => false,
        'credit_limit_enabled' => true,
        'default_credit_limit' => 5000.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    
    'notifications' => [
        'channels' => ['mail', 'database', 'sms', 'whatsapp', 'wechat'],
        'default_channels' => ['mail', 'database'],
        'sms_enabled' => env('TWILIO_SID', false) ? true : false,
        'whatsapp_enabled' => env('TWILIO_WHATSAPP_FROM', false) ? true : false,
        'wechat_enabled' => env('WECHAT_APP_ID', false) ? true : false,
        'real_time_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search & Filter Settings
    |--------------------------------------------------------------------------
    */
    
    'search' => [
        'engine' => 'database', // Options: 'database', 'elasticsearch', 'algolia'
        'min_query_length' => 2,
        'results_per_page' => 20,
        'enable_suggestions' => true,
        'enable_fuzzy_search' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media & Upload Settings
    |--------------------------------------------------------------------------
    */
    
    'media' => [
        'disk' => env('FILESYSTEM_DISK', 'local'),
        'max_upload_size' => 10240, // KB
        'image_sizes' => [
            'thumbnail' => [150, 150],
            'small' => [300, 300],
            'medium' => [600, 600],
            'large' => [1200, 1200],
        ],
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'optimize_images' => true,
        'generate_thumbnails' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'two_factor_auth' => true,
        'password_expires_days' => 90,
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // minutes
        'session_timeout' => 120, // minutes
        'require_email_verification' => true,
        'api_rate_limit' => 60, // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting & Analytics
    |--------------------------------------------------------------------------
    */
    
    'reports' => [
        'enable_analytics' => true,
        'track_user_activity' => true,
        'retention_days' => 365,
        'export_formats' => ['pdf', 'excel', 'csv'],
        'scheduled_reports' => [
            'daily_sales' => true,
            'weekly_summary' => true,
            'monthly_analytics' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    
    'integrations' => [
        'accounting' => [
            'enabled' => false,
            'provider' => null, // 'xero', 'myob', 'quickbooks'
        ],
        'inventory' => [
            'enabled' => false,
            'provider' => null,
        ],
        'shipping' => [
            'enabled' => true,
            'providers' => ['auspost', 'startrack', 'toll'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    */
    
    'maintenance' => [
        'enabled' => false,
        'message' => 'The marketplace is currently under maintenance. Please check back soon.',
        'allowed_ips' => [],
        'secret' => env('MAINTENANCE_SECRET'),
    ],
];