<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use KayedSpace\N8n\Client\BaseClient;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ApiRequestCompleted;
use KayedSpace\N8n\Events\RateLimitEncountered;
use KayedSpace\N8n\Exceptions\AuthenticationException;
use KayedSpace\N8n\Exceptions\N8nException;
use KayedSpace\N8n\Exceptions\RateLimitException;

abstract class AbstractApi extends BaseClient
{
    public function __construct(protected PendingRequest $httpClient)
    {
        parent::__construct($httpClient);

        $baseUrl = Config::get('n8n.api.base_url');
        $key = Config::get('n8n.api.key');
        $this->httpClient = $httpClient->baseUrl($baseUrl)->withHeaders([
            'X-N8N-API-KEY' => $key,
            'Accept' => 'application/json',
        ]);

        $this->setupRetryStrategy();
    }

    /**
     * Proxy HTTP calls through the root client.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function request(RequestMethod $method, string $uri, array $data = []): Collection|array
    {
        // Prepare query data for GET requests
        if (RequestMethod::Get->is($method)) {
            $data = $this->prepareQuery($data);
        }

        try {
            return $this->executeRequest(
                $method,
                $uri,
                $data,
                function ($httpClient, $method, $uri, $data) {
                    $response = $httpClient->{$method->value}($uri, $data);
                    $this->handleRateLimiting($response, $uri);
                    $result = $response->json() ?? [];

                    return [$response, $result];
                },
                fn ($method, $uri, $requestData, $responseData, $status, $duration) => $this->dispatchEvent($method, $uri, $requestData, $responseData, $status, $duration)
            );
        } catch (RequestException $e) {
            $this->handleException($e, $method, $uri, $data, 'API request');
        }
    }

    /**
     * Setup retry strategy based on config.
     */
    protected function setupRetryStrategy(): void
    {
        $retryConfig = Config::get('n8n.retry_strategy', []);
        $strategy = $retryConfig['strategy'] ?? 'exponential';
        $maxDelay = $retryConfig['max_delay'] ?? 10000;

        $this->httpClient = $this->httpClient->retry(
            Config::get('n8n.retry', 3),
            function ($attempt, $exception) use ($strategy, $maxDelay) {
                // Check if we should retry based on status code
                if ($exception instanceof RequestException) {
                    $statusCodes = Config::get('n8n.retry_strategy.on_status_codes', [429, 500, 502, 503, 504]);
                    if (! in_array($exception->response->status(), $statusCodes)) {
                        return 0; // Don't retry
                    }
                }

                return match ($strategy) {
                    'exponential' => min((int) (100 * (2 ** $attempt)), $maxDelay),
                    'linear' => min(1000 * $attempt, $maxDelay),
                    default => 0,
                };
            }
        );
    }

    /**
     * Handle rate limiting.
     */
    protected function handleRateLimiting(Response $response, string $uri): void
    {
        if ($response->status() === 429 && Config::get('n8n.rate_limiting.auto_wait', true)) {
            $retryAfter = (int) ($response->header('Retry-After') ?? 1);
            $maxWait = Config::get('n8n.rate_limiting.max_wait', 60);

            if ($retryAfter <= $maxWait) {
                // Dispatch event
                if (Config::get('n8n.events.enabled', true)) {
                    Event::dispatch(new RateLimitEncountered($retryAfter, $uri));
                }

                sleep($retryAfter);
            }
        }
    }


    /**
     * Dispatch API event.
     */
    protected function dispatchEvent(RequestMethod $method, string $uri, array $requestData, array $responseData, int $status, float $duration): void
    {
        if (! Config::get('n8n.events.enabled', true)) {
            return;
        }

        Event::dispatch(new ApiRequestCompleted(
            $method->value,
            $uri,
            $requestData,
            $responseData,
            $status,
            $duration
        ));
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
