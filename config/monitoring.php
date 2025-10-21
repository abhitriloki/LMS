<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for application monitoring, error
    | tracking, and performance monitoring services.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    |
    | Configure error tracking services like Sentry for real-time error
    | monitoring and alerting.
    |
    */

    'error_tracking' => [
        'enabled' => env('ERROR_TRACKING_ENABLED', true),
        
        'sentry' => [
            'enabled' => env('SENTRY_ENABLED', false),
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
            'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
            'send_default_pii' => false,
            'breadcrumbs' => [
                'sql_queries' => true,
                'sql_bindings' => false,
                'logs' => true,
                'cache' => false,
            ],
            'integrations' => [
                'breadcrumbs' => true,
                'transaction' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure APM services for monitoring application performance,
    | database queries, and external API calls.
    |
    */

    'apm' => [
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'app_name' => env('NEW_RELIC_APP_NAME', env('APP_NAME')),
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'transaction_tracer' => [
                'enabled' => true,
                'threshold' => 'apdex_f',
                'detail' => 1,
            ],
        ],

        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
            'host' => env('DATADOG_HOST', 'localhost'),
            'port' => env('DATADOG_PORT', 8125),
            'tags' => [
                'env' => env('APP_ENV', 'production'),
                'service' => env('APP_NAME', 'lms'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configure health check endpoints and monitoring intervals.
    |
    */

    'health_checks' => [
        'enabled' => env('HEALTH_CHECKS_ENABLED', true),
        'endpoint' => env('HEALTH_CHECK_ENDPOINT', '/health'),
        
        'checks' => [
            'database' => true,
            'redis' => true,
            'storage' => true,
            'queue' => true,
            'cache' => true,
        ],
        
        'timeout' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure external uptime monitoring services.
    |
    */

    'uptime' => [
        'pingdom' => [
            'enabled' => env('PINGDOM_ENABLED', false),
            'check_id' => env('PINGDOM_CHECK_ID'),
            'api_key' => env('PINGDOM_API_KEY'),
        ],

        'uptimerobot' => [
            'enabled' => env('UPTIMEROBOT_ENABLED', false),
            'api_key' => env('UPTIMEROBOT_API_KEY'),
            'monitor_id' => env('UPTIMEROBOT_MONITOR_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Aggregation
    |--------------------------------------------------------------------------
    |
    | Configure log aggregation and analysis services.
    |
    */

    'log_aggregation' => [
        'papertrail' => [
            'enabled' => env('PAPERTRAIL_ENABLED', false),
            'host' => env('PAPERTRAIL_HOST'),
            'port' => env('PAPERTRAIL_PORT'),
        ],

        'loggly' => [
            'enabled' => env('LOGGLY_ENABLED', false),
            'token' => env('LOGGLY_TOKEN'),
            'tags' => ['laravel', 'lms'],
        ],

        'cloudwatch' => [
            'enabled' => env('CLOUDWATCH_LOGS_ENABLED', false),
            'group' => env('CLOUDWATCH_LOG_GROUP', '/aws/lms'),
            'stream' => env('CLOUDWATCH_LOG_STREAM', 'application'),
            'retention' => env('CLOUDWATCH_LOG_RETENTION', 14), // days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configure metrics collection for monitoring application performance.
    |
    */

    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        
        'collect' => [
            'requests' => true,
            'database_queries' => true,
            'cache_hits' => true,
            'queue_jobs' => true,
            'memory_usage' => true,
            'response_time' => true,
        ],
        
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 2000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    |
    | Configure alerting thresholds and notification channels.
    |
    */

    'alerting' => [
        'enabled' => env('ALERTING_ENABLED', true),
        
        'channels' => [
            'slack' => [
                'enabled' => env('ALERT_SLACK_ENABLED', false),
                'webhook_url' => env('ALERT_SLACK_WEBHOOK_URL'),
                'channel' => env('ALERT_SLACK_CHANNEL', '#alerts'),
            ],
            
            'email' => [
                'enabled' => env('ALERT_EMAIL_ENABLED', true),
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
            
            'pagerduty' => [
                'enabled' => env('PAGERDUTY_ENABLED', false),
                'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
            ],
        ],
        
        'thresholds' => [
            'error_rate' => env('ALERT_ERROR_RATE_THRESHOLD', 5), // errors per minute
            'response_time' => env('ALERT_RESPONSE_TIME_THRESHOLD', 3000), // milliseconds
            'queue_size' => env('ALERT_QUEUE_SIZE_THRESHOLD', 1000),
            'disk_usage' => env('ALERT_DISK_USAGE_THRESHOLD', 85), // percentage
            'memory_usage' => env('ALERT_MEMORY_USAGE_THRESHOLD', 90), // percentage
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Profiling
    |--------------------------------------------------------------------------
    |
    | Configure performance profiling tools.
    |
    */

    'profiling' => [
        'enabled' => env('PROFILING_ENABLED', false),
        
        'blackfire' => [
            'enabled' => env('BLACKFIRE_ENABLED', false),
            'server_id' => env('BLACKFIRE_SERVER_ID'),
            'server_token' => env('BLACKFIRE_SERVER_TOKEN'),
        ],
        
        'xhprof' => [
            'enabled' => env('XHPROF_ENABLED', false),
            'sample_rate' => env('XHPROF_SAMPLE_RATE', 0.01), // 1% of requests
        ],
    ],

];
