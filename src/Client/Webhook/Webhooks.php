<?php

namespace KayedSpace\N8n\Client\Webhook;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use KayedSpace\N8n\Enums\RequestMethod;

class Webhooks
{
    private ?array $basicAuth;

    public function __construct(
        protected PendingRequest $httpClient,
        protected RequestMethod $method = RequestMethod::Get
    ) {
        $username = Config::string('n8n.webhook.username');
        $password = Config::string('n8n.webhook.password');
        $baseUrl = Config::string('n8n.webhook.base_url');

        if ($username && $password) {
            $this->basicAuth = [
                'username' => $username,
                'password' => $password,
            ];
        }

        $this->httpClient = $httpClient->baseUrl($baseUrl);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function request($path, array $data = []): ?array
    {
        return $this->httpClient
            ->when($this->basicAuth,
                fn ($request) => $request->withBasicAuth($this->basicAuth['username'], $this->basicAuth['password']))
            ->{$this->method->value}($path, $data)
            ->json();
    }

    public function withBasicAuth(string $username, string $password): static
    {
        $this->basicAuth = [
            'username' => $username,
            'password' => $password,
        ];

        return $this;
    }

    public function withoutBasicAuth(): static
    {
        $this->basicAuth = null;

        return $this;
    }
}
