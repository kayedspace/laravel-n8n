<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('creates credentials', function () {
    Http::fake(fn () => Http::response(['id' => '1'], 201));

    $payload = ['name' => 'Cred'];
    $resp = N8nClient::credentials()->create($payload);

    expect($resp)->toMatchArray(['id' => '1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPost($r->method())
        && $r->url() === "{$url}/credentials"
        && $r['name'] === 'Cred'
    );
});

it('lists credentials without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::credentials()->list(50);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/credentials?limit=50"
    );
});

it('lists credentials with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::credentials()->list(25, 'abc');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/credentials?limit=25&cursor=abc"
    );
});

it('gets a credential', function () {
    Http::fake(fn () => Http::response(['id' => '1'], 200));

    $resp = N8nClient::credentials()->get('1');

    expect($resp['id'])->toBe('1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/credentials/1"
    );
});

it('deletes a credential', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::credentials()->delete('1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isDelete($r->method())
        && $r->url() === "{$url}/credentials/1"
    );
});

it('gets credential schema', function () {
    Http::fake(fn () => Http::response(['schema' => []], 200));

    N8nClient::credentials()->schema('aws');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/credentials/schema/aws"
    );
});

it('transfers a credential', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::credentials()->transfer('1', 'dest');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPut($r->method())
        && $r->url() === "{$url}/credentials/1/transfer"
        && $r['destinationProjectId'] === 'dest'
    );
});
