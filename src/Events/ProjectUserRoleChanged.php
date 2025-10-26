<?php

namespace KayedSpace\N8n\Events;

class ProjectUserRoleChanged extends N8nEvent
{
    public function __construct(string $projectId, string $userId, string $role, array $response = [], ?array $context = null)
    {
        parent::__construct('project', 'user_role_changed', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
            'response' => $response,
        ], $context);
    }
}
