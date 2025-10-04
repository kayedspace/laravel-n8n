<?php

namespace KayedSpace\N8n\Events;

class ExecutionFailed extends N8nEvent
{
    public function __construct(array $execution, ?array $context = null)
    {
        parent::__construct('execution', 'failed', $execution, $context);
    }
}
