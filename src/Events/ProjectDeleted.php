<?php

namespace KayedSpace\N8n\Events;

class ProjectDeleted extends N8nEvent
{
    public function __construct(string $id, ?array $context = null)
    {
        parent::__construct('project', 'deleted', ['id' => $id], $context);
    }
}
