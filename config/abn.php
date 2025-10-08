<?php

return [
    'api' => [
        'guid' => env('ABN_API_GUID', '00805b10-ccd8-4eea-8ff5-88376e6161fe'),
        'base_url' => env('ABN_API_BASE_URL', 'https://abr.business.gov.au'),
        'timeout' => env('ABN_API_TIMEOUT', 30),
        'retry_times' => env('ABN_API_RETRY_TIMES', 3),
        'retry_delay' => env('ABN_API_RETRY_DELAY', 1000),
    ],
    
    'logging' => [
        'enabled' => env('ABN_LOGGING_ENABLED', true),
        'channel' => env('ABN_LOG_CHANNEL', 'stack'),
    ],
    
    'cache' => [
        'enabled' => env('ABN_CACHE_ENABLED', true),
        'ttl' => env('ABN_CACHE_TTL', 86400), // 24 hours
        'prefix' => env('ABN_CACHE_PREFIX', 'abn_'),
    ],
    
    'validation' => [
        'checksum' => env('ABN_VALIDATE_CHECKSUM', true),
        'format' => env('ABN_VALIDATE_FORMAT', true),
    ],
];