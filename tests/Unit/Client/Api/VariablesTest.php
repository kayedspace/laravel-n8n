<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\VariableCreated;
use KayedSpace\N8n\Events\VariableDeleted;
use KayedSpace\N8n\Events\VariableUpdated;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a variable', function () {
    Http::fake(fn () => Http::response(['id' => 'var1'], 201));

    $payload = ['key' => 'API_KEY', 'value' => '123'];
    $resp = N8nClient::variables()->create($payload);

    expect($resp)->toMatchArray(['id' => 'var1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Post->is($r->method())
        && $r->url() === "{$url}/variables"
        && $r->data() === $payload
    );
});

it('lists variables without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::variables()->list(['limit' => 50]);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/variables?limit=50"
    );
});

it('lists variables with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::variables()->list(['limit' => 30, 'cursor' => 'abc']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/variables?limit=30&cursor=abc"
    );
});

it('updates a variable', function () {
    Http::fake(fn () => Http::response([], 204));

    $payload = ['value' => '456'];
    N8nClient::variables()->update('var1', $payload);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Put->is($r->method())
        && $r->url() === "{$url}/variables/var1"
        && $r->data() === $payload
    );
});

it('deletes a variable', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::variables()->delete('var1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Delete->is($r->method())
        && $r->url() === "{$url}/variables/var1"
    );
});

it('creates many variables', function () {
    Http::fake(fn () => Http::response(['id' => 'new-var'], 201));

    $variables = [
        ['key' => 'API_URL', 'value' => 'https://api.example.com'],
        ['key' => 'DEBUG_MODE', 'value' => 'false'],
        ['key' => 'MAX_RETRIES', 'value' => '3'],
    ];

    $results = N8nClient::variables()->createMany($variables);

    expect($results)->toHaveCount(3)
        ->and($results[0]['success'])->toBeTrue()
        ->and($results[1]['success'])->toBeTrue()
        ->and($results[2]['success'])->toBeTrue();

    Http::assertSentCount(3);
});

it('deletes many variables', function () {
    Http::fake(fn () => Http::response([], 204));

    $results = N8nClient::variables()->deleteMany(['var1', 'var2', 'var3']);

    expect($results)->toHaveCount(3)
        ->and($results['var1']['success'])->toBeTrue()
        ->and($results['var2']['success'])->toBeTrue()
        ->and($results['var3']['success'])->toBeTrue();

    Http::assertSentCount(3);
});

it('dispatches variable created event', function () {
    Event::fake([VariableCreated::class]);
    Http::fake(fn () => Http::response(['id' => 'var-created'], 201));

    N8nClient::variables()->create(['key' => 'KEY', 'value' => 'VALUE']);

    Event::assertDispatched(VariableCreated::class, fn ($event) => $event->data['id'] === 'var-created');
});

it('dispatches variable updated event', function () {
    Event::fake([VariableUpdated::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::variables()->update('var-1', ['value' => 'updated']);

    Event::assertDispatched(VariableUpdated::class, fn ($event) => $event->data['id'] === 'var-1');
});

it('dispatches variable deleted event', function () {
    Event::fake([VariableDeleted::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::variables()->delete('var-1');

    Event::assertDispatched(VariableDeleted::class, fn ($event) => $event->data['id'] === 'var-1');
});
