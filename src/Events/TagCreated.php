<?php

namespace KayedSpace\N8n\Events;

class TagCreated extends N8nEvent
{
    public function __construct(array $tag, ?array $context = null)
    {
        parent::__construct('tag', 'created', $tag, $context);
    }
}
