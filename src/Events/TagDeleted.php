<?php

namespace KayedSpace\N8n\Events;

class TagDeleted extends N8nEvent
{
    public function __construct(string $id, ?array $context = null)
    {
        parent::__construct('tag', 'deleted', ['id' => $id], $context);
    }
}
