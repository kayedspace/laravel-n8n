<?php

namespace KayedSpace\N8n\Events;

class WorkflowActivated extends N8nEvent
{
    public function __construct(string $workflowId, array $response = [], ?array $context = null)
    {
        parent::__construct('workflow', 'activated', ['id' => $workflowId, 'response' => $response], $context);
    }
}
