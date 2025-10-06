<?php

namespace KayedSpace\N8n\Events;

class VariableCreated extends N8nEvent
{
    public function __construct(array $variable, ?array $context = null)
    {
        parent::__construct('variable', 'created', $variable, $context);
    }
}
