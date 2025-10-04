<?php

namespace KayedSpace\N8n\Events;

class RateLimitEncountered extends N8nEvent
{
    public function __construct(int $retryAfter, string $uri, ?array $context = null)
    {
        parent::__construct('api', 'rate_limit_encountered', [
            'retry_after' => $retryAfter,
            'uri' => $uri,
        ], $context);
    }
}
