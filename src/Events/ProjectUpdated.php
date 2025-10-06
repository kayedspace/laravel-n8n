<?php

namespace KayedSpace\N8n\Events;

class ProjectUpdated extends N8nEvent
{
    public function __construct(string $id, ?array $context = null)
    {
        parent::__construct('project', 'updated', ['id' => $id], $context);
    }
}
