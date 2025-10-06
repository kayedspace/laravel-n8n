<?php

namespace KayedSpace\N8n\Events;

class UserRoleChanged extends N8nEvent
{
    public function __construct(string $id, string $newRole, ?array $context = null)
    {
        parent::__construct('user', 'role_changed', ['id' => $id, 'new_role' => $newRole], $context);
    }
}
