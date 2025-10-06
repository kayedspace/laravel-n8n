<?php

namespace KayedSpace\N8n\Events;

class UserCreated extends N8nEvent
{
    public function __construct(array $users, ?array $context = null)
    {
        parent::__construct('user', 'created', $users, $context);
    }
}
