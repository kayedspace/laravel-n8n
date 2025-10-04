<?php

namespace KayedSpace\N8n\Events;

class WebhookTriggered extends N8nEvent
{
    public function __construct(string $path, array $payload, array $response = [], ?array $context = null)
    {
        parent::__construct('webhook', 'triggered', [
            'path' => $path,
            'payload' => $payload,
            'response' => $response,
        ], $context);
    }
}
