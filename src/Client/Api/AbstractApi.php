<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ApiRequestCompleted;
use KayedSpace\N8n\Events\RateLimitEncountered;
use KayedSpace\N8n\Exceptions\AuthenticationException;
use KayedSpace\N8n\Exceptions\N8nException;
use KayedSpace\N8n\Exceptions\RateLimitException;

abstract class AbstractApi
{
    use Macroable;

    protected bool $cachingEnabled = false;

    protected array $clientModifiers = [];

    protected array $metrics = [];

    public function __construct(protected PendingRequest $httpClient)
    {
        $baseUrl = Config::get('n8n.api.base_url');
        $key = Config::get('n8n.api.key');
        $this->httpClient = $httpClient->baseUrl($baseUrl)->withHeaders([
            'X-N8N-API-KEY' => $key,
            'Accept' => 'application/json',
        ]);

        $this->setupRetryStrategy();
    }

    /**
     * Enable caching for the next request.
     */
    public function cached(): static
    {
        $this->cachingEnabled = true;

        return $this;
    }

    /**
     * Disable caching for the next request.
     */
    public function fresh(): static
    {
        $this->cachingEnabled = false;

        return $this;
    }

    /**
     * Add a client modifier to customize the HTTP client.
     */
    public function withClientModifier(callable $modifier): static
    {
        $this->clientModifiers[] = $modifier;

        return $this;
    }

    /**
     * Proxy HTTP calls through the root client.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function request(RequestMethod $method, string $uri, array $data = []): Collection|array
    {
        $startTime = microtime(true);

        // Apply client modifiers
        foreach ($this->clientModifiers as $modifier) {
            $this->httpClient = $modifier($this->httpClient);
        }

        if (RequestMethod::Get->is($method)) {
            $data = $this->prepareQuery($data);
        }

        // Check cache for GET requests
        if (RequestMethod::Get->is($method) && $this->shouldUseCache()) {
            $cached = $this->getFromCache($method, $uri, $data);
            if ($cached !== null) {
                $this->logRequest($method, $uri, $data, $cached, 200, microtime(true) - $startTime, true);

                return $this->formatResponse($cached);
            }
        }

        // Debug mode
        if (Config::get('n8n.debug')) {
            Log::debug('N8N Request', [
                'method' => $method->value,
                'uri' => $uri,
                'data' => $data,
            ]);
        }

        try {
            $response = $this->httpClient->{$method->value}($uri, $data);

            $this->handleRateLimiting($response, $uri);

            $result = $response->json() ?? [];
            $duration = microtime(true) - $startTime;

            // Cache GET responses
            if (RequestMethod::Get->is($method) && $this->shouldUseCache()) {
                $this->putInCache($method, $uri, $data, $result);
            }

            // Logging
            $this->logRequest($method, $uri, $data, $result, $response->status(), $duration);

            // Events
            $this->dispatchEvent($method, $uri, $data, $result, $response->status(), $duration);

            // Metrics
            $this->trackMetrics($method, $uri, $duration, $response->status());

            // Debug mode
            if (Config::get('n8n.debug')) {
                Log::debug('N8N Response', [
                    'status' => $response->status(),
                    'duration' => $duration.'s',
                    'data' => $result,
                ]);
            }

            // Invalidate cache on mutations
            if (! RequestMethod::Get->is($method)) {
                $this->invalidateCache($uri);
            }

            return $this->formatResponse($result);
        } catch (RequestException $e) {
            $this->handleException($e, $method, $uri, $data);
        }
    }

    /**
     * Format response based on config (collection or array).
     */
    protected function formatResponse(mixed $data): Collection|array
    {
        if (Config::get('n8n.return_type') === 'collection') {
            return collect($data);
        }

        return is_array($data) ? $data : [];
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
     * Handle exceptions and convert to domain exceptions.
     */
    protected function handleException(RequestException $exception, RequestMethod $method, string $uri, array $data): never
    {
        $response = $exception->response;

        // Log the error
        if (Config::get('n8n.logging.enabled')) {
            $channel = Config::get('n8n.logging.channel', 'stack');
            Log::channel($channel)->error('N8n API request failed', [
                'method' => $method->value,
                'uri' => $uri,
                'status' => $response?->status(),
                'response' => $response?->json(),
            ]);
        }

        // Convert to domain exception
        $domainException = match ($response?->status()) {
            401, 403 => AuthenticationException::fromResponse($response, 'Authentication failed'),
            429 => RateLimitException::fromResponse($response),
            default => N8nException::fromResponse($response, '', ['method' => $method->value, 'uri' => $uri, 'data' => $data]),
        };

        throw $domainException;
    }

    /**
     * Log the request.
     */
    protected function logRequest(RequestMethod $method, string $uri, array $requestData, array $responseData, int $status, float $duration, bool $cached = false): void
    {
        if (! Config::get('n8n.logging.enabled')) {
            return;
        }

        $channel = Config::get('n8n.logging.channel', 'stack');
        $level = Config::get('n8n.logging.level', 'debug');

        $context = [
            'method' => $method->value,
            'uri' => $uri,
            'status' => $status,
            'duration' => round($duration * 1000, 2).'ms',
            'cached' => $cached,
        ];

        if (Config::get('n8n.logging.include_request_body', true)) {
            $context['request'] = $requestData;
        }

        if (Config::get('n8n.logging.include_response_body', true)) {
            $context['response'] = $responseData;
        }

        Log::channel($channel)->log($level, "N8n API {$method->value} {$uri}", $context);
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

    /**
     * Track metrics.
     */
    protected function trackMetrics(RequestMethod $method, string $uri, float $duration, int $status): void
    {
        if (! Config::get('n8n.metrics.enabled')) {
            return;
        }

        $store = Config::get('n8n.metrics.store', 'default');
        $key = 'n8n:metrics:'.date('Y-m-d-H');

        Cache::store($store)->increment($key.':total');
        Cache::store($store)->increment($key.':method:'.$method->value);
        Cache::store($store)->increment($key.':status:'.$status);

        // Store average duration
        $durationKey = $key.':duration';
        $currentAvg = (float) Cache::store($store)->get($durationKey, 0);
        $currentCount = Cache::store($store)->get($key.':total', 1);
        $newAvg = (($currentAvg * ($currentCount - 1)) + $duration) / $currentCount;
        Cache::store($store)->put($durationKey, $newAvg, 86400);
    }

    /**
     * Check if caching should be used.
     */
    protected function shouldUseCache(): bool
    {
        return $this->cachingEnabled || Config::get('n8n.cache.enabled', false);
    }

    /**
     * Get from cache.
     */
    protected function getFromCache(RequestMethod $method, string $uri, array $data): ?array
    {
        $key = $this->getCacheKey($method, $uri, $data);
        $store = Config::get('n8n.cache.store', 'default');

        return Cache::store($store)->get($key);
    }

    /**
     * Put in cache.
     */
    protected function putInCache(RequestMethod $method, string $uri, array $data, array $result): void
    {
        $key = $this->getCacheKey($method, $uri, $data);
        $store = Config::get('n8n.cache.store', 'default');
        $ttl = Config::get('n8n.cache.ttl', 300);

        Cache::store($store)->put($key, $result, $ttl);

        // Tag the cache entry
        $tag = $this->getCacheTag($uri);
        Cache::store($store)->tags([$tag])->put($key, $result, $ttl);
    }

    /**
     * Invalidate cache.
     */
    protected function invalidateCache(string $uri): void
    {
        if (! Config::get('n8n.cache.enabled')) {
            return;
        }

        $tag = $this->getCacheTag($uri);
        $store = Config::get('n8n.cache.store', 'default');

        try {
            Cache::store($store)->tags([$tag])->flush();
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tags, skip
        }
    }

    /**
     * Get cache key.
     */
    protected function getCacheKey(RequestMethod $method, string $uri, array $data): string
    {
        $prefix = Config::get('n8n.cache.prefix', 'n8n');

        return $prefix.':'.md5($method->value.$uri.serialize($data));
    }

    /**
     * Get cache tag from URI.
     */
    protected function getCacheTag(string $uri): string
    {
        $resource = explode('/', trim($uri, '/'))[0] ?? 'general';

        return 'n8n:'.$resource;
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
