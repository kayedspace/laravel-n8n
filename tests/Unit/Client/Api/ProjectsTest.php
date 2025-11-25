<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ProjectCreated;
use KayedSpace\N8n\Events\ProjectDeleted;
use KayedSpace\N8n\Events\ProjectUpdated;
use KayedSpace\N8n\Events\ProjectUserRemoved;
use KayedSpace\N8n\Events\ProjectUserRoleChanged;
use KayedSpace\N8n\Events\ProjectUsersAdded;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a project', function () {
    Http::fake(fn () => Http::response(['id' => 'p1'], 201));

    $payload = ['name' => 'Project 1'];
    $resp = N8nClient::projects()->create($payload);

    expect($resp)->toMatchArray(['id' => 'p1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Post->is($r->method())
        && $r->url() === "{$url}/projects"
        && $r->data() === $payload
    );
});

it('lists projects without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::projects()->list(['limit' => 50]);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/projects?limit=50"
    );
});

it('lists projects with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::projects()->list(['limit' => 25, 'cursor' => 'abc']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/projects?limit=25&cursor=abc"
    );
});

it('updates a project', function () {
    Http::fake(fn () => Http::response([], 204));

    $payload = ['name' => 'Updated'];
    N8nClient::projects()->update('p1', $payload);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Put->is($r->method())
        && $r->url() === "{$url}/projects/p1"
        && $r->data() === $payload
    );
});

it('deletes a project', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->delete('p1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Delete->is($r->method())
        && $r->url() === "{$url}/projects/p1"
    );
});

it('adds users to a project', function () {
    Event::fake([ProjectUsersAdded::class]);
    Http::fake(fn () => Http::response([], 204));

    $relations = [
        ['userId' => 'u1', 'role' => 'editor'],
        ['userId' => 'u2', 'role' => 'viewer'],
    ];

    N8nClient::projects()->addUsers('p1', $relations);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Post->is($r->method())
        && $r->url() === "{$url}/projects/p1/users"
        && $r['relations'] === $relations
    );

    Event::assertDispatched(ProjectUsersAdded::class, function ($event) use ($relations) {
        return $event->data['project_id'] === 'p1'
            && $event->data['relations'] === $relations;
    });
});

it('changes a user role in project', function () {
    Event::fake([ProjectUserRoleChanged::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->changeUserRole('p1', 'u1', 'admin');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Patch->is($r->method())
        && $r->url() === "{$url}/projects/p1/users/u1"
        && $r['role'] === 'admin'
    );

    Event::assertDispatched(ProjectUserRoleChanged::class, function ($event) {
        return $event->data['project_id'] === 'p1'
            && $event->data['user_id'] === 'u1'
            && $event->data['role'] === 'admin';
    });
});

it('removes a user from project', function () {
    Event::fake([ProjectUserRemoved::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->removeUser('p1', 'u1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Delete->is($r->method())
        && $r->url() === "{$url}/projects/p1/users/u1"
    );

    Event::assertDispatched(ProjectUserRemoved::class, function ($event) {
        return $event->data['project_id'] === 'p1'
            && $event->data['user_id'] === 'u1';
    });
});

it('dispatches project created event', function () {
    Event::fake([ProjectCreated::class]);
    Http::fake(fn () => Http::response(['id' => 'p1'], 201));

    N8nClient::projects()->create(['name' => 'Proj']);

    Event::assertDispatched(ProjectCreated::class, fn ($event) => $event->data['id'] === 'p1');
});

it('dispatches project updated event', function () {
    Event::fake([ProjectUpdated::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->update('p1', ['name' => 'updated']);

    Event::assertDispatched(ProjectUpdated::class, fn ($event) => $event->data['id'] === 'p1');
});

it('dispatches project deleted event', function () {
    Event::fake([ProjectDeleted::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::projects()->delete('p1');

    Event::assertDispatched(ProjectDeleted::class, fn ($event) => $event->data['id'] === 'p1');
});
