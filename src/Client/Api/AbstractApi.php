<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use KayedSpace\N8n\Enums\RequestMethod;

abstract class AbstractApi
{
    public function __construct(protected PendingRequest $httpClient)
    {
        $baseUrl = Config::get('n8n.api.base_url');
        $key = Config::get('n8n.api.key');
        $this->httpClient = $httpClient->baseUrl($baseUrl)->withHeaders([
            'X-N8N-API-KEY' => $key,
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Proxy HTTP calls through the root client.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function request(RequestMethod $method, string $uri, array $data = []): array
    {
        if (RequestMethod::Get->is($method)) {
            $data = $this->prepareQuery($data);
        }

        return $this->httpClient
            ->{$method->value}($uri, $data)
            ->json();
    }

    private function prepareQuery(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->prepareQuery($value);
            } elseif (is_null($value)) {
                unset($data[$key]);
            } elseif (is_bool($value)) {
                $data[$key] = $value ? 'true' : 'false';
            }
        }

        return $data;
    }
}
