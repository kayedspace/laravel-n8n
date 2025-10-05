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
use KayedSpace\N8n\Client\BaseClient;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\WebhookTriggered;
use KayedSpace\N8n\Jobs\TriggerN8nWebhook;

class Webhooks extends BaseClient
{
    private ?array $basicAuth;

    private bool $async = false;

    public function __construct(
        protected PendingRequest $httpClient,
        protected RequestMethod $method = RequestMethod::Get
    ) {
        parent::__construct($httpClient);

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
                    new TriggerN8nWebhook($path, $data, $this->method)
                );

            return collect(['queued' => true, 'path' => $path]);
        }

        // Execute synchronous request with full observability
        return $this->executeRequest(
            $this->method,
            $path,
            $data,
            function ($httpClient, $method, $uri, $data) {
                $response = $httpClient
                    ->when($this->basicAuth,
                        fn ($request) => $request->withBasicAuth($this->basicAuth['username'], $this->basicAuth['password']))
                    ->{$method->value}($uri, $data);

                $result = $response->json();

                return [$response, $result];
            },
            function ($method, $uri, $requestData, $responseData, $status, $duration) {
                // Dispatch webhook event
                if (Config::get('n8n.events.enabled', true)) {
                    Event::dispatch(new WebhookTriggered($uri, $requestData, $responseData ?? []));
                }
            }
        );
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
}
