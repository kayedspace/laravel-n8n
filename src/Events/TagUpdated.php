<?php

namespace KayedSpace\N8n\Events;

class TagUpdated extends N8nEvent
{
    public function __construct(array $tag, ?array $context = null)
    {
        parent::__construct('tag', 'updated', $tag, $context);
    }
}
