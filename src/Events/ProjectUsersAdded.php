<?php

namespace KayedSpace\N8n\Events;

class ProjectUsersAdded extends N8nEvent
{
    public function __construct(string $projectId, array $relations, array $response = [], ?array $context = null)
    {
        parent::__construct('project', 'users_added', [
            'project_id' => $projectId,
            'relations' => $relations,
            'response' => $response,
        ], $context);
    }
}
