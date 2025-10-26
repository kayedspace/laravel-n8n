<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ExecutionCompleted;
use KayedSpace\N8n\Events\ExecutionDeleted;
use KayedSpace\N8n\Events\ExecutionFailed;
use KayedSpace\N8n\Facades\N8nClient;

it('lists executions without filters', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::executions()->list();

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/executions"
    );
});

it('lists executions with filters', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    $filters = [
        'workflowId' => 'w1',
        'limit' => 25,
        'status' => 'success',
    ];

    N8nClient::executions()->list($filters);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/executions?workflowId=w1&limit=25&status=success"
    );
});

it('gets execution without data', function () {
    Http::fake(fn () => Http::response(['id' => 1], 200));

    $resp = N8nClient::executions()->get(1);

    expect($resp['id'])->toBe(1);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/executions/1?includeData=false"
    );
});

it('gets execution with data', function () {
    Http::fake(fn () => Http::response(['id' => 1, 'data' => []], 200));

    N8nClient::executions()->get(1, true);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/executions/1?includeData=true"
    );
});

it('deletes execution', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::executions()->delete(1);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Delete->is($r->method())
        && $r->url() === "{$url}/executions/1"
    );
});

it('dispatches execution completed event', function () {
    Event::fake([ExecutionCompleted::class]);
    Http::fake(fn () => Http::response(['id' => 99, 'status' => 'success'], 200));

    N8nClient::executions()->get(99, true);

    Event::assertDispatched(ExecutionCompleted::class, fn ($event) => $event->data['id'] === 99);
});

it('dispatches execution failed event', function () {
    Event::fake([ExecutionFailed::class]);
    Http::fake(fn () => Http::response(['id' => 77, 'status' => 'error'], 200));

    N8nClient::executions()->get(77);

    Event::assertDispatched(ExecutionFailed::class, fn ($event) => $event->data['id'] === 77);
});

it('dispatches execution deleted event', function () {
    Event::fake([ExecutionDeleted::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::executions()->delete(123);

    Event::assertDispatched(ExecutionDeleted::class, fn ($event) => $event->data['id'] === 123);
});

it('deletes many executions', function () {
    Http::fake(fn () => Http::response([], 204));

    $results = N8nClient::executions()->deleteMany([1, 2, 3]);

    expect($results)->toHaveCount(3)
        ->and($results[1]['success'])->toBeTrue()
        ->and($results[2]['success'])->toBeTrue()
        ->and($results[3]['success'])->toBeTrue();

    Http::assertSentCount(3);
});

it('waits for execution to complete successfully', function () {
    Http::fake([
        '*/executions/1?*' => Http::sequence()
            ->push(['id' => 1, 'status' => 'running'], 200)
            ->push(['id' => 1, 'status' => 'running'], 200)
            ->push(['id' => 1, 'status' => 'success'], 200),
    ]);

    $execution = N8nClient::executions()->wait(1, timeout: 10, interval: 0);

    expect($execution['status'])->toBe('success');
    Http::assertSentCount(3);
});

it('throws exception when execution fails', function () {
    Http::fake(fn () => Http::response(['id' => 1, 'status' => 'error'], 200));

    N8nClient::executions()->wait(1, timeout: 10, interval: 0);
})->throws(\KayedSpace\N8n\Exceptions\ExecutionFailedException::class);

it('throws exception when execution times out', function () {
    Http::fake(fn () => Http::response(['id' => 1, 'status' => 'running'], 200));

    N8nClient::executions()->wait(1, timeout: 1, interval: 0);
})->throws(\KayedSpace\N8n\Exceptions\ExecutionFailedException::class);

it('auto-paginates all executions', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['items' => [['id' => 1]], 'nextCursor' => 'cursor1'], 200)
            ->push(['items' => [['id' => 2]], 'nextCursor' => null], 200),
    ]);

    $executions = N8nClient::executions()->all();

    // Verify pagination worked (2 requests made)
    Http::assertSentCount(2);

    // Verify we got data back
    expect($executions)->not->toBeEmpty();
});

it('iterates through executions', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['items' => [['id' => 1]], 'nextCursor' => 'cursor1'], 200)
            ->push(['items' => [['id' => 2]], 'nextCursor' => null], 200),
    ]);

    $items = [];
    foreach (N8nClient::executions()->listIterator() as $execution) {
        $items[] = $execution;
    }

    // Verify pagination worked (2 requests made)
    Http::assertSentCount(2);

    // Verify we iterated through items
    expect($items)->not->toBeEmpty();
});
