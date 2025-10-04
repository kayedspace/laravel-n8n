<?php

namespace KayedSpace\N8n\Events;

class ExecutionDeleted extends N8nEvent
{
    public function __construct(int $executionId, ?array $context = null)
    {
        parent::__construct('execution', 'deleted', ['id' => $executionId], $context);
    }
}
