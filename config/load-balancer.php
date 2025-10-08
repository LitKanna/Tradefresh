<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Load Balancer Configuration for Sydney Markets B2B
    |--------------------------------------------------------------------------
    |
    | This configuration is used for horizontal scaling and load distribution
    | across multiple application servers.
    |
    */

    'enabled' => env('LOAD_BALANCER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Server Pool Configuration
    |--------------------------------------------------------------------------
    |
    | Define your application servers that will handle requests
    |
    */
    'servers' => [
        'web' => [
            [
                'host' => env('APP_SERVER_1', '192.168.1.10'),
                'port' => 80,
                'weight' => 1,
                'max_fails' => 3,
                'fail_timeout' => 30,
            ],
            [
                'host' => env('APP_SERVER_2', '192.168.1.11'),
                'port' => 80,
                'weight' => 1,
                'max_fails' => 3,
                'fail_timeout' => 30,
            ],
            [
                'host' => env('APP_SERVER_3', '192.168.1.12'),
                'port' => 80,
                'weight' => 1,
                'max_fails' => 3,
                'fail_timeout' => 30,
            ],
        ],
        'api' => [
            [
                'host' => env('API_SERVER_1', '192.168.1.20'),
                'port' => 80,
                'weight' => 1,
            ],
            [
                'host' => env('API_SERVER_2', '192.168.1.21'),
                'port' => 80,
                'weight' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Load Balancing Algorithm
    |--------------------------------------------------------------------------
    |
    | Choose the algorithm for distributing requests:
    | - round_robin: Requests are distributed evenly
    | - least_conn: Request goes to server with least connections
    | - ip_hash: Client IP determines which server to use
    | - random: Random server selection
    | - weighted: Based on server weights
    |
    */
    'algorithm' => env('LB_ALGORITHM', 'round_robin'),

    /*
    |--------------------------------------------------------------------------
    | Session Affinity (Sticky Sessions)
    |--------------------------------------------------------------------------
    |
    | Enable this to ensure users stay on the same server
    |
    */
    'sticky_sessions' => [
        'enabled' => env('LB_STICKY_SESSIONS', true),
        'cookie_name' => 'server_id',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configure health check endpoints and parameters
    |
    */
    'health_check' => [
        'enabled' => true,
        'endpoint' => '/api/health',
        'interval' => 30, // seconds
        'timeout' => 5, // seconds
        'healthy_threshold' => 2,
        'unhealthy_threshold' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL/TLS Configuration
    |--------------------------------------------------------------------------
    |
    | SSL termination at load balancer level
    |
    */
    'ssl' => [
        'enabled' => env('LB_SSL_ENABLED', true),
        'redirect_http' => true,
        'certificate' => env('SSL_CERT_PATH', '/etc/ssl/certs/sydneymarkets.crt'),
        'private_key' => env('SSL_KEY_PATH', '/etc/ssl/private/sydneymarkets.key'),
        'protocols' => ['TLSv1.2', 'TLSv1.3'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Implement rate limiting at load balancer level
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'burst' => 10,
        'by' => 'ip', // ip, user, api_key
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching at Load Balancer
    |--------------------------------------------------------------------------
    |
    | Cache static content at load balancer level
    |
    */
    'cache' => [
        'enabled' => true,
        'static_files' => [
            'extensions' => ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'],
            'ttl' => 86400, // 1 day
        ],
        'api_responses' => [
            'enabled' => false,
            'ttl' => 60, // 1 minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request/Response Headers
    |--------------------------------------------------------------------------
    |
    | Custom headers to add or remove
    |
    */
    'headers' => [
        'add' => [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ],
        'remove' => [
            'Server',
            'X-Powered-By',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log configuration for load balancer
    |
    */
    'logging' => [
        'access_log' => '/var/log/lb/access.log',
        'error_log' => '/var/log/lb/error.log',
        'level' => 'info',
    ],
];
