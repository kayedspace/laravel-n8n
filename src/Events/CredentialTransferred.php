<?php

namespace KayedSpace\N8n\Events;

class CredentialTransferred extends N8nEvent
{
    public function __construct(string $credentialId, string $destinationProjectId, array $response = [], ?array $context = null)
    {
        parent::__construct('credential', 'transferred', [
            'id' => $credentialId,
            'destination_project_id' => $destinationProjectId,
            'response' => $response,
        ], $context);
    }
}
