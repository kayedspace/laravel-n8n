<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\CredentialCreated;
use KayedSpace\N8n\Events\CredentialDeleted;
use KayedSpace\N8n\Events\CredentialTransferred;
use KayedSpace\N8n\Facades\N8nClient;

it('creates credentials', function () {
    Http::fake(fn () => Http::response(['id' => '1'], 201));

    $payload = ['name' => 'Cred'];
    $resp = N8nClient::credentials()->create($payload);

    expect($resp)->toMatchArray(['id' => '1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Post->is($r->method())
        && $r->url() === "{$url}/credentials"
        && $r['name'] === 'Cred'
    );
});

it('lists credentials without cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::credentials()->list(['limit' => 50]);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/credentials?limit=50"
    );
});

it('lists credentials with cursor', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    N8nClient::credentials()->list(['limit' => 25, 'cursor' => 'abc']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/credentials?limit=25&cursor=abc"
    );
});

it('gets a credential', function () {
    Http::fake(fn () => Http::response(['id' => '1'], 200));

    $resp = N8nClient::credentials()->get('1');

    expect($resp['id'])->toBe('1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/credentials/1"
    );
});

it('deletes a credential', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::credentials()->delete('1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Delete->is($r->method())
        && $r->url() === "{$url}/credentials/1"
    );
});

it('gets credential schema', function () {
    Http::fake(fn () => Http::response(['schema' => []], 200));

    N8nClient::credentials()->schema('aws');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Get->is($r->method())
        && $r->url() === "{$url}/credentials/schema/aws"
    );
});

it('transfers a credential', function () {
    Event::fake([CredentialTransferred::class]);
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::credentials()->transfer('1', 'dest');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Put->is($r->method())
        && $r->url() === "{$url}/credentials/1/transfer"
        && $r['destinationProjectId'] === 'dest'
    );

    Event::assertDispatched(CredentialTransferred::class, function ($event) {
        return $event->data['id'] === '1'
            && $event->data['destination_project_id'] === 'dest';
    });
});

it('dispatches credential created event', function () {
    Event::fake([CredentialCreated::class]);
    Http::fake(fn () => Http::response(['id' => 'cred-created'], 201));

    N8nClient::credentials()->create(['name' => 'Test']);

    Event::assertDispatched(CredentialCreated::class, fn ($event) => $event->data['id'] === 'cred-created');
});

it('dispatches credential deleted event', function () {
    Event::fake([CredentialDeleted::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::credentials()->delete('cred-1');

    Event::assertDispatched(CredentialDeleted::class, fn ($event) => $event->data['id'] === 'cred-1');
});
