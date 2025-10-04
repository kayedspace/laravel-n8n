<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Enums\RequestMethod;

class SourceControl extends AbstractApi
{
    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function pull(array $payload): Collection|array
    {
        return $this->request(RequestMethod::Post, '/source-control/pull', $payload);
    }
}
