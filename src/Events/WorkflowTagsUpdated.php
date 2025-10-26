<?php

namespace KayedSpace\N8n\Events;

class WorkflowTagsUpdated extends N8nEvent
{
    public function __construct(string $workflowId, array $tagIds, array $response = [], ?array $context = null)
    {
        parent::__construct('workflow', 'tags_updated', [
            'id' => $workflowId,
            'tags' => $tagIds,
            'response' => $response,
        ], $context);
    }
}
