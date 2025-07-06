<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a tag', function () {
    Http::fake(fn () => Http::response(['id' => 't1'], 201));

    $payload = ['name' => 'Marketing'];
    $resp = N8nClient::tags()->create($payload);

    expect($resp)->toMatchArray(['id' => 't1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Post->is($req->method()) &&
        $req->url() === "{$url}/tags" &&
        $req['name'] === 'Marketing'
    );
});

it('lists tags without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::tags()->list(50);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Get->is($req->method()) &&
        $req->url() === "{$url}/tags?limit=50"
    );
});

it('lists tags with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::tags()->list(25, 'abc');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Get->is($req->method()) &&
        $req->url() === "{$url}/tags?limit=25&cursor=abc"
    );
});

it('gets a tag', function () {
    Http::fake(fn () => Http::response(['id' => 't1'], 200));

    $resp = N8nClient::tags()->get('t1');

    expect($resp['id'])->toBe('t1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Get->is($req->method()) &&
        $req->url() === "{$url}/tags/t1"
    );
});

it('updates a tag', function () {
    Http::fake(fn () => Http::response(['name' => 'Updated'], 200));

    N8nClient::tags()->update('t1', ['name' => 'Updated']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Put->is($req->method()) &&
        $req->url() === "{$url}/tags/t1" &&
        $req['name'] === 'Updated'
    );
});

it('deletes a tag', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::tags()->delete('t1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Delete->is($req->method()) &&
        $req->url() === "{$url}/tags/t1"
    );
});
