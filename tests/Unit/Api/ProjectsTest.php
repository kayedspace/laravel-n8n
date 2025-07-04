<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a project', function () {
    Http::fake(fn () => Http::response(['id' => 'p1'], 201));

    $payload = ['name' => 'Project 1'];
    $resp = N8nClient::projects()->create($payload);

    expect($resp)->toMatchArray(['id' => 'p1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPost($r->method())
        && $r->url() === "{$url}/projects"
        && $r->data() === $payload
    );
});

it('lists projects without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::projects()->list(50);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/projects?limit=50"
    );
});

it('lists projects with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::projects()->list(25, 'abc');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isGet($r->method())
        && $r->url() === "{$url}/projects?limit=25&cursor=abc"
    );
});

it('updates a project', function () {
    Http::fake(fn () => Http::response([], 204));

    $payload = ['name' => 'Updated'];
    N8nClient::projects()->update('p1', $payload);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPut($r->method())
        && $r->url() === "{$url}/projects/p1"
        && $r->data() === $payload
    );
});

it('deletes a project', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->delete('p1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isDelete($r->method())
        && $r->url() === "{$url}/projects/p1"
    );
});

it('adds users to a project', function () {
    Http::fake(fn () => Http::response([], 204));

    $relations = [
        ['userId' => 'u1', 'role' => 'editor'],
        ['userId' => 'u2', 'role' => 'viewer'],
    ];

    N8nClient::projects()->addUsers('p1', $relations);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPost($r->method())
        && $r->url() === "{$url}/projects/p1/users"
        && $r['relations'] === $relations
    );
});

it('changes a user role in project', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->changeUserRole('p1', 'u1', 'admin');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isPatch($r->method())
        && $r->url() === "{$url}/projects/p1/users/u1"
        && $r['role'] === 'admin'
    );
});

it('removes a user from project', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->removeUser('p1', 'u1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::isDelete($r->method())
        && $r->url() === "{$url}/projects/p1/users/u1"
    );
});
