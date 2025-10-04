<?php

namespace KayedSpace\N8n\Events;

class ExecutionCompleted extends N8nEvent
{
    public function __construct(array $execution, ?array $context = null)
    {
        parent::__construct('execution', 'completed', $execution, $context);
    }
}
