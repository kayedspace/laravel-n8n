<?php

namespace KayedSpace\N8n\Events;

class ApiRequestCompleted extends N8nEvent
{
    public function __construct(
        string $method,
        string $uri,
        array $requestData,
        array $responseData,
        int $statusCode,
        float $duration,
        ?array $context = null
    ) {
        parent::__construct('api', 'request_completed', [
            'method' => $method,
            'uri' => $uri,
            'request' => $requestData,
            'response' => $responseData,
            'status_code' => $statusCode,
            'duration' => $duration,
        ], $context);
    }
}
