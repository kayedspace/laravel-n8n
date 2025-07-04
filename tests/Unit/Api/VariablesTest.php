<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a variable', function () {
    Http::fake(fn () => Http::response(['id' => 'var1'], 201));

    $payload = ['key' => 'API_KEY', 'value' => '123'];
    $resp = N8nClient::variables()->create($payload);

    expect($resp)->toMatchArray(['id' => 'var1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPost($r->method())
        && $r->url() === "{$url}/variables"
        && $r->data() === $payload
    );
});

it('lists variables without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::variables()->list(50);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/variables?limit=50"
    );
});

it('lists variables with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::variables()->list(30, 'abc');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/variables?limit=30&cursor=abc"
    );
});

it('updates a variable', function () {
    Http::fake(fn () => Http::response([], 204));

    $payload = ['value' => '456'];
    N8nClient::variables()->update('var1', $payload);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPut($r->method())
        && $r->url() === "{$url}/variables/var1"
        && $r->data() === $payload
    );
});

it('deletes a variable', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::variables()->delete('var1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isDelete($r->method())
        && $r->url() === "{$url}/variables/var1"
    );
});
