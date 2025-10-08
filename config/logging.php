<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', \Monolog\Handler\SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [
                \Monolog\Processor\PsrLogMessageProcessor::class,
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [
                \Monolog\Processor\PsrLogMessageProcessor::class,
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // Sydney Markets B2B Specific Channels
        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'auth' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 90, // Keep auth logs longer for security auditing
            'replace_placeholders' => true,
        ],

        'security' => [
            'driver' => 'stack',
            'channels' => ['daily_security', 'slack_security'],
        ],

        'daily_security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 365, // Keep security logs for 1 year
            'replace_placeholders' => true,
        ],

        'slack_security' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Sydney Markets Security',
            'emoji' => ':warning:',
            'level' => 'error',
            'replace_placeholders' => true,
        ],

        'orders' => [
            'driver' => 'daily',
            'path' => storage_path('logs/orders.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 365, // Keep order logs for compliance
            'replace_placeholders' => true,
        ],

        'payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payments.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 2555, // Keep payment logs for 7 years (compliance)
            'replace_placeholders' => true,
        ],

        'invoices' => [
            'driver' => 'daily',
            'path' => storage_path('logs/invoices.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 2555, // Keep invoice logs for 7 years (compliance)
            'replace_placeholders' => true,
        ],

        'whatsapp' => [
            'driver' => 'daily',
            'path' => storage_path('logs/whatsapp.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'delivery' => [
            'driver' => 'daily',
            'path' => storage_path('logs/delivery.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => env('AUDIT_LOG_RETENTION_DAYS', 2555), // Default 7 years for compliance
            'replace_placeholders' => true,
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'mail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // ELK Stack integration
        'elk' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\SocketHandler::class,
            'handler_with' => [
                'connectionString' => 'udp://logstash:5001',
            ],
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'level' => env('LOG_LEVEL', 'debug'),
            'processors' => [
                \Monolog\Processor\PsrLogMessageProcessor::class,
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
                \Monolog\Processor\MemoryPeakUsageProcessor::class,
            ],
        ],

        // Structured logging for monitoring
        'structured' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => storage_path('logs/structured.log'),
            ],
            'level' => env('LOG_LEVEL', 'debug'),
            'processors' => [
                \Monolog\Processor\PsrLogMessageProcessor::class,
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
                \Monolog\Processor\MemoryPeakUsageProcessor::class,
            ],
        ],

        // Critical errors that need immediate attention
        'critical' => [
            'driver' => 'stack',
            'channels' => ['daily', 'slack'],
            'level' => 'critical',
        ],

        // Development specific logging
        'debug' => [
            'driver' => 'single',
            'path' => storage_path('logs/debug.log'),
            'level' => 'debug',
            'replace_placeholders' => true,
        ],

        // Testing channel
        'testing' => [
            'driver' => 'single',
            'path' => storage_path('logs/testing.log'),
            'level' => 'debug',
            'replace_placeholders' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Log Context
    |--------------------------------------------------------------------------
    |
    | Add custom context to all log messages
    |
    */
    'context' => [
        'application' => 'sydney-markets-b2b',
        'environment' => env('APP_ENV', 'production'),
        'version' => env('APP_VERSION', '1.0.0'),
    ],
];