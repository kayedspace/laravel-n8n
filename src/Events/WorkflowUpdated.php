<?php

namespace KayedSpace\N8n\Events;

class WorkflowUpdated extends N8nEvent
{
    public function __construct(array $workflow, ?array $context = null)
    {
        parent::__construct('workflow', 'updated', $workflow, $context);
    }
}
