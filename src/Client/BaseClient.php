<?php

namespace KayedSpace\N8n\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Exceptions\AuthenticationException;
use KayedSpace\N8n\Exceptions\N8nException;
use KayedSpace\N8n\Exceptions\RateLimitException;

abstract class BaseClient
{
    use Macroable;

    protected bool $cachingEnabled = false;

    protected array $clientModifiers = [];

    protected array $metrics = [];

    public function __construct(protected PendingRequest $httpClient)
    {
        //
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
     * Format response based on config (collection or array).
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

    /**
     * Log the request.
     */
    protected function logRequest(RequestMethod $method, string $uri, array $requestData, ?array $responseData, int $status, float $duration, bool $cached = false): void
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

        Log::channel($channel)->log($level, "N8n {$method->value} {$uri}", $context);
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
    protected function putInCache(RequestMethod $method, string $uri, array $data, mixed $result): void
    {
        $key = $this->getCacheKey($method, $uri, $data);
        $store = Config::get('n8n.cache.store', 'default');
        $ttl = Config::get('n8n.cache.ttl', 300);

        Cache::store($store)->put($key, $result, $ttl);

        // Tag the cache entry
        $tag = $this->getCacheTag($uri);

        try {
            Cache::store($store)->tags([$tag])->put($key, $result, $ttl);
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tags, skip
        }
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

    /**
     * Apply client modifiers to the HTTP client.
     */
    protected function applyClientModifiers(): void
    {
        foreach ($this->clientModifiers as $modifier) {
            $this->httpClient = $modifier($this->httpClient);
        }
    }

    /**
     * Execute HTTP request with full observability (caching, logging, metrics, events).
     */
    protected function executeRequest(
        RequestMethod $method,
        string $uri,
        array $data,
        callable $httpCall,
        ?callable $eventDispatcher = null
    ): Collection|array|null {
        $startTime = microtime(true);

        // Apply client modifiers
        $this->applyClientModifiers();

        // Check cache for GET requests
        if ($method === RequestMethod::Get && $this->shouldUseCache()) {
            $cached = $this->getFromCache($method, $uri, $data);
            if ($cached !== null) {
                $this->logRequest($method, $uri, $data, $cached, 200, microtime(true) - $startTime, true);

                return $this->formatResponse($cached);
            }
        }

        // Debug mode - log request
        if (Config::get('n8n.debug')) {
            Log::debug("N8N {$method->value} Request", [
                'method' => $method->value,
                'uri' => $uri,
                'data' => $data,
            ]);
        }

        // Execute HTTP call
        [$httpResponse, $responseData] = $httpCall($this->httpClient, $method, $uri, $data);

        $duration = microtime(true) - $startTime;
        $status = $httpResponse?->status() ?? 200;

        // Cache GET responses
        if ($method === RequestMethod::Get && $this->shouldUseCache() && $responseData !== null) {
            $this->putInCache($method, $uri, $data, $responseData);
        }

        // Logging
        $this->logRequest($method, $uri, $data, $responseData, $status, $duration);

        // Metrics
        $this->trackMetrics($method, $uri, $duration, $status);

        // Debug mode - log response
        if (Config::get('n8n.debug')) {
            Log::debug("N8N {$method->value} Response", [
                'status' => $status,
                'duration' => round($duration * 1000, 2).'ms',
                'data' => $responseData,
            ]);
        }

        // Dispatch custom event (if provided)
        if ($eventDispatcher) {
            $eventDispatcher($method, $uri, $data, $responseData, $status, $duration);
        }

        // Invalidate cache on mutations
        if ($method !== RequestMethod::Get) {
            $this->invalidateCache($uri);
        }

        return $this->formatResponse($responseData);
    }

    /**
     * Handle exceptions and convert to domain exceptions.
     */
    protected function handleException(RequestException $exception, RequestMethod $method, string $uri, array $data, string $context = 'request'): never
    {
        $response = $exception->response;

        // Log the error
        if (Config::get('n8n.logging.enabled')) {
            $channel = Config::get('n8n.logging.channel', 'stack');
            Log::channel($channel)->error("N8n {$context} failed", [
                'method' => $method->value,
                'uri' => $uri,
                'status' => $response?->status(),
                'response' => $response?->json(),
            ]);
        }

        // Convert to domain exception
        throw match ($response?->status()) {
            401, 403 => AuthenticationException::fromResponse($response, 'Authentication failed'),
            429 => RateLimitException::fromResponse($response),
            default => N8nException::fromResponse($response, '', ['method' => $method->value, 'uri' => $uri, 'data' => $data, 'context' => $context]),
        };
    }

    /**
     * Dispatch resource-specific event if events are enabled.
     */
    protected function dispatchResourceEvent(object $event): void
    {
        if (Config::get('n8n.events.enabled', true)) {
            Event::dispatch($event);
        }
    }
}
