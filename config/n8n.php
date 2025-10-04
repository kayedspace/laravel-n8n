<?php

return [
    'api' => [
        'base_url' => env('N8N_API_BASE_URL'),
        'key' => env('N8N_API_KEY'),
        'version' => env('N8N_API_VERSION', 'v1'),
    ],

    'webhook' => [
        'base_url' => env('N8N_WEBHOOK_BASE_URL'),
        'username' => env('N8N_WEBHOOK_USERNAME'),
        'password' => env('N8N_WEBHOOK_PASSWORD'),
        'signature_key' => env('N8N_WEBHOOK_SIGNATURE_KEY'),
    ],

    'timeout' => (int) env('N8N_TIMEOUT', 120),
    'throw' => (bool) env('N8N_THROW', true),
    'retry' => (int) env('N8N_RETRY', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Strategy
    |--------------------------------------------------------------------------
    |
    | Configure how the client retries failed requests.
    | Strategies: 'constant', 'linear', 'exponential'
    |
    */

    'retry_strategy' => [
        'strategy' => env('N8N_RETRY_STRATEGY', 'exponential'),
        'max_delay' => (int) env('N8N_RETRY_MAX_DELAY', 10000), // milliseconds
        'on_status_codes' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Handle n8n rate limits automatically by waiting and retrying.
    |
    */

    'rate_limiting' => [
        'auto_wait' => (bool) env('N8N_RATE_LIMIT_AUTO_WAIT', true),
        'max_wait' => (int) env('N8N_RATE_LIMIT_MAX_WAIT', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of API requests and responses.
    |
    */

    'logging' => [
        'enabled' => (bool) env('N8N_LOGGING_ENABLED', false),
        'channel' => env('N8N_LOGGING_CHANNEL', 'stack'),
        'level' => env('N8N_LOGGING_LEVEL', 'debug'), // debug, info, error
        'include_request_body' => (bool) env('N8N_LOG_REQUEST_BODY', true),
        'include_response_body' => (bool) env('N8N_LOG_RESPONSE_BODY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable Laravel events for API operations.
    |
    */

    'events' => [
        'enabled' => (bool) env('N8N_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache GET requests for improved performance.
    |
    */

    'cache' => [
        'enabled' => (bool) env('N8N_CACHE_ENABLED', false),
        'store' => env('N8N_CACHE_STORE', 'default'),
        'ttl' => (int) env('N8N_CACHE_TTL', 300), // seconds
        'prefix' => env('N8N_CACHE_PREFIX', 'n8n'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Configure async webhook triggering via queue.
    |
    */

    'queue' => [
        'enabled' => (bool) env('N8N_QUEUE_ENABLED', false),
        'connection' => env('N8N_QUEUE_CONNECTION', 'default'),
        'queue' => env('N8N_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable verbose debugging output.
    |
    */

    'debug' => (bool) env('N8N_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Return Type
    |--------------------------------------------------------------------------
    |
    | Choose between 'collection' or 'array' for API responses.
    | Collections are array-accessible via $data['key'] and provide
    | additional helper methods.
    |
    */

    'return_type' => env('N8N_RETURN_TYPE', 'collection'),

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Track API usage metrics.
    |
    */

    'metrics' => [
        'enabled' => (bool) env('N8N_METRICS_ENABLED', false),
        'store' => env('N8N_METRICS_STORE', 'default'),
    ],
];
