<?php

namespace KayedSpace\N8n\Events;

class WorkflowDeactivated extends N8nEvent
{
    public function __construct(string $workflowId, array $response = [], ?array $context = null)
    {
        parent::__construct('workflow', 'deactivated', ['id' => $workflowId, 'response' => $response], $context);
    }
}
