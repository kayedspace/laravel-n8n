<?php

namespace KayedSpace\N8n\Client\Webhook;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\WebhookTriggered;
use KayedSpace\N8n\Jobs\TriggerN8nWebhook;

class Webhooks
{
    private ?array $basicAuth;

    private bool $async = false;

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
     * Enable async queue mode.
     */
    public function async(): static
    {
        $this->async = true;

        return $this;
    }

    /**
     * Disable async queue mode.
     */
    public function sync(): static
    {
        $this->async = false;

        return $this;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function request($path, array $data = []): Collection|array|null
    {
        // If async and queue enabled, dispatch to queue
        if ($this->async && Config::get('n8n.queue.enabled')) {
            Queue::connection(Config::get('n8n.queue.connection', 'default'))
                ->pushOn(
                    Config::get('n8n.queue.queue', 'default'),
                    new TriggerN8nWebhook($path, $data, $this->method, $this->basicAuth)
                );

            return collect(['queued' => true, 'path' => $path]);
        }

        // Synchronous request
        $response = $this->httpClient
            ->when($this->basicAuth,
                fn ($request) => $request->withBasicAuth($this->basicAuth['username'], $this->basicAuth['password']))
            ->{$this->method->value}($path, $data)
            ->json();

        // Dispatch event
        if (Config::get('n8n.events.enabled', true)) {
            Event::dispatch(new WebhookTriggered($path, $data, $response ?? []));
        }

        return $this->formatResponse($response);
    }

    /**
     * Verify webhook signature.
     */
    public static function verifySignature(Request $request, ?string $secret = null): bool
    {
        $secret = $secret ?? Config::get('n8n.webhook.signature_key');

        if (! $secret) {
            return false;
        }

        $signature = $request->header('X-N8n-Signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate and parse webhook request.
     */
    public static function validateWebhookRequest(Request $request): array
    {
        $data = $request->all();

        return [
            'valid' => true,
            'data' => $data,
            'headers' => $request->headers->all(),
            'method' => $request->method(),
        ];
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

    /**
     * Format response based on config.
     */
    protected function formatResponse(mixed $data): Collection|array|null
    {
        if ($data === null) {
            return null;
        }

        if (Config::get('n8n.return_type') === 'collection') {
            return collect($data);
        }

        return is_array($data) ? $data : [];
    }
}
