<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
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
