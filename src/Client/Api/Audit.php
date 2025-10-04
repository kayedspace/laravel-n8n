<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Support\Collection;
use KayedSpace\N8n\Enums\RequestMethod;

class Audit extends AbstractApi
{
    public function generate(array $additionalOptions = []): Collection|array
    {
        return $this->request(RequestMethod::Post, '/audit', ['additionalOptions' => $additionalOptions]);
    }
}
