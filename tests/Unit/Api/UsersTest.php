<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('lists users without filters', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::users()->list();

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method()) &&
        $req->url() === "{$url}/users"
    );
});

it('lists users with filters', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    $filters = [
        'limit' => 20,
        'cursor' => 'xyz',
        'includeRole' => 'true',
        'projectId' => 'proj',
    ];

    N8nClient::users()->list($filters);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method()) &&
        $req->url() === "{$url}/users?limit=20&cursor=xyz&includeRole=true&projectId=proj"
    );
});

it('creates users', function () {
    Http::fake(fn () => Http::response(['ids' => ['u1']], 201));

    $payload = [
        ['email' => 'a@example.com', 'firstName' => 'A'],
    ];

    $resp = N8nClient::users()->create($payload);

    expect($resp)->toMatchArray(['ids' => ['u1']]);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isPost($req->method()) &&
        $req->url() === "{$url}/users" &&
        $req->data() === $payload
    );
});

it('gets user without role', function () {
    Http::fake(fn () => Http::response(['id' => 'u1'], 200));

    N8nClient::users()->get('u1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method()) &&
        $req->url() === "{$url}/users/u1?includeRole=false"
    );
});

it('gets user with role', function () {
    Http::fake(fn () => Http::response(['id' => 'u1', 'role' => 'admin'], 200));

    N8nCLient::users()->get('u1', true);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method()) &&
        $req->url() === "{$url}/users/u1?includeRole=true"
    );
});

it('deletes user', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nCLient::users()->delete('u1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isDelete($req->method()) &&
        $req->url() === "{$url}/users/u1"
    );
});

it('changes user role', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nCLient::users()->changeRole('u1', 'editor');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isPatch($req->method()) &&
        $req->url() === "{$url}/users/u1/role" &&
        $req['newRoleName'] === 'editor'
    );
});
