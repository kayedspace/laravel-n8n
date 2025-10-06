<?php

namespace KayedSpace\N8n\Events;

class ProjectCreated extends N8nEvent
{
    public function __construct(array $project, ?array $context = null)
    {
        parent::__construct('project', 'created', $project, $context);
    }
}
