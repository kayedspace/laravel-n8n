<?php

namespace KayedSpace\N8n\Events;

class ProjectUserRemoved extends N8nEvent
{
    public function __construct(string $projectId, string $userId, array $response = [], ?array $context = null)
    {
        parent::__construct('project', 'user_removed', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'response' => $response,
        ], $context);
    }
}
