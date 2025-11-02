<?php

namespace KayedSpace\N8n\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class N8nEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $resource,
        public readonly string $action,
        public readonly array $data = [],
        public readonly ?array $context = null
    ) {}
}
