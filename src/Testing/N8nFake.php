<?php

namespace KayedSpace\N8n\Testing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class N8nFake
{
    protected static array $responseQueue = [];

    protected static array $requests = [];

    public static function fake(): void
    {
        Http::fake(function ($request) {
            static::$requests[] = [
                'method' => $request->method(),
                'url' => $request->url(),
                'data' => $request->data(),
                'headers' => $request->headers(),
            ];

            if (! empty(static::$responseQueue)) {
                return array_shift(static::$responseQueue);
            }

            return Http::response(['fake' => true], 200);
        });
    }

    public static function workflows(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: ['data' => [
            ['id' => 'wf1', 'name' => 'Test Workflow', 'active' => true],
        ]], 200);
    }

    public static function workflow(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: [
            'id' => 'wf1',
            'name' => 'Test Workflow',
            'active' => true,
            'nodes' => [],
            'connections' => [],
        ], 200);
    }

    public static function executions(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: ['data' => [
            ['id' => 1, 'status' => 'success', 'workflowId' => 'wf1'],
        ]], 200);
    }

    public static function execution(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: [
            'id' => 1,
            'status' => 'success',
            'workflowId' => 'wf1',
            'mode' => 'manual',
            'data' => [],
        ], 200);
    }

    public static function success(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data, 200);
    }

    public static function error(int $status = 500, array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: ['message' => 'Error'], $status);
    }

    public static function notFound(array $data = []): void
    {
        static::$responseQueue[] = Http::response($data ?: ['message' => 'Not found'], 404);
    }

    public static function rateLimit(int $retryAfter = 60): void
    {
        static::$responseQueue[] = Http::response(['message' => 'Rate limit exceeded'], 429, [
            'Retry-After' => $retryAfter,
        ]);
    }

    public static function assertWorkflowCreated(callable $callback = null): void
    {
        static::assertSent(function ($request) use ($callback) {
            $isWorkflowCreate = str_contains($request['url'], '/workflows')
                && $request['method'] === 'POST';

            if (! $isWorkflowCreate) {
                return false;
            }

            return $callback ? $callback($request) : true;
        });
    }

    public static function assertWorkflowActivated(string $id): void
    {
        static::assertSent(function ($request) use ($id) {
            return str_contains($request['url'], "/workflows/{$id}/activate")
                && $request['method'] === 'POST';
        });
    }

    public static function assertWebhookTriggered(string $path): void
    {
        static::assertSent(function ($request) use ($path) {
            return str_contains($request['url'], $path);
        });
    }

    public static function assertSent(callable $callback): void
    {
        $found = false;

        foreach (static::$requests as $request) {
            if ($callback($request)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \PHPUnit\Framework\AssertionFailedError('Expected request was not sent');
        }
    }

    public static function assertNotSent(callable $callback): void
    {
        foreach (static::$requests as $request) {
            if ($callback($request)) {
                throw new \PHPUnit\Framework\AssertionFailedError('Unexpected request was sent');
            }
        }
    }

    public static function assertSentCount(int $count): void
    {
        $actual = count(static::$requests);

        if ($actual !== $count) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected {$count} requests, but {$actual} were sent"
            );
        }
    }

    public static function getRequests(): array
    {
        return static::$requests;
    }

    public static function reset(): void
    {
        static::$responseQueue = [];
        static::$requests = [];
    }
}
