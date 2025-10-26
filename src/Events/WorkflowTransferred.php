<?php

namespace KayedSpace\N8n\Events;

class WorkflowTransferred extends N8nEvent
{
    public function __construct(string $workflowId, string $destinationProjectId, array $response = [], ?array $context = null)
    {
        parent::__construct('workflow', 'transferred', [
            'id' => $workflowId,
            'destination_project_id' => $destinationProjectId,
            'response' => $response,
        ], $context);
    }
}
