<?php

namespace KayedSpace\N8n\Events;

class VariableUpdated extends N8nEvent
{
    public function __construct(string $id, ?array $context = null)
    {
        parent::__construct('variable', 'updated', ['id' => $id], $context);
    }
}
