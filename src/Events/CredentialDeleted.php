<?php

namespace KayedSpace\N8n\Events;

class CredentialDeleted extends N8nEvent
{
    public function __construct(string $id, ?array $context = null)
    {
        parent::__construct('credential', 'deleted', ['id' => $id], $context);
    }
}
