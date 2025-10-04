<?php

namespace KayedSpace\N8n\Events;

class WorkflowDeleted extends N8nEvent
{
    public function __construct(string $workflowId, ?array $context = null)
    {
        parent::__construct('workflow', 'deleted', ['id' => $workflowId], $context);
    }
}
