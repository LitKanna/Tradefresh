<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        // High-performance Redis queue configuration
        'redis' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // High-priority queue for time-sensitive operations
        'high-priority' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'high',
            'retry_after' => 60,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // Notifications queue
        'notifications' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'notifications',
            'retry_after' => 120,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // Email queue
        'emails' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'emails',
            'retry_after' => 300,
            'block_for' => 5,
            'after_commit' => false,
        ],

        // Image processing queue
        'images' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'images',
            'retry_after' => 600, // 10 minutes for image processing
            'block_for' => 10,
            'after_commit' => false,
        ],

        // Analytics and reporting queue
        'analytics' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'analytics',
            'retry_after' => 1800, // 30 minutes for heavy analytics
            'block_for' => 15,
            'after_commit' => false,
        ],

        // Batch processing queue
        'batch' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'batch',
            'retry_after' => 3600, // 1 hour for batch operations
            'block_for' => 30,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    */

    'workers' => [
        'high-priority' => [
            'processes' => 4,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 256,
            'sleep' => 1,
        ],
        'default' => [
            'processes' => 8,
            'tries' => 3,
            'timeout' => 120,
            'memory' => 512,
            'sleep' => 3,
        ],
        'notifications' => [
            'processes' => 6,
            'tries' => 5,
            'timeout' => 90,
            'memory' => 256,
            'sleep' => 1,
        ],
        'emails' => [
            'processes' => 4,
            'tries' => 5,
            'timeout' => 300,
            'memory' => 256,
            'sleep' => 5,
        ],
        'images' => [
            'processes' => 2,
            'tries' => 3,
            'timeout' => 600,
            'memory' => 1024, // Higher memory for image processing
            'sleep' => 10,
        ],
        'analytics' => [
            'processes' => 2,
            'tries' => 2,
            'timeout' => 1800,
            'memory' => 1024,
            'sleep' => 30,
        ],
        'batch' => [
            'processes' => 1,
            'tries' => 1,
            'timeout' => 3600,
            'memory' => 2048,
            'sleep' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Priorities
    |--------------------------------------------------------------------------
    */

    'priorities' => [
        'critical' => 100,
        'high' => 50,
        'normal' => 10,
        'low' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'emails' => [
            'max_jobs' => 60,
            'duration' => 60, // per minute
        ],
        'notifications' => [
            'max_jobs' => 120,
            'duration' => 60,
        ],
        'api_calls' => [
            'max_jobs' => 30,
            'duration' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('QUEUE_MONITORING_ENABLED', true),
        'slow_job_threshold' => 30, // seconds
        'failed_job_threshold' => 5, // failures per hour
        'queue_size_threshold' => 1000, // max queue size before alert
    ],

];