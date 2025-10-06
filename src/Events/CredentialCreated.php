<?php

namespace KayedSpace\N8n\Events;

class CredentialCreated extends N8nEvent
{
    public function __construct(array $credential, ?array $context = null)
    {
        parent::__construct('credential', 'created', $credential, $context);
    }
}
