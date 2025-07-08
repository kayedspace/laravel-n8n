<?php

return [
    'api' => [
        'base_url' => env('N8N_API_BASE_URL'),
        'key' => env('N8N_API_KEY'),
    ],
    'webhook' => [
        'base_url' => env('N8N_WEBHOOK_BASE_URL'),
        'username' => env('N8N_WEBHOOK_USERNAME'),
        'password' => env('N8N_WEBHOOK_PASSWORD'),
    ],
    'timeout' => (int) env('N8N_TIMEOUT', 120),
    'throw' => (bool) env('N8N_THROW', true),
    'retry' => (int) env('N8N_RETRY', 3),
];
