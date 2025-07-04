<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a workflow', function () {
    Http::fake(fn () => Http::response(['id' => 'wf1'], 201));

    $payload = ['name' => 'My flow'];
    $resp = N8nClient::workflows()->create($payload);

    expect($resp)->toMatchArray(['id' => 'wf1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isPost($req->method())
        && $req->url() === "{$url}/workflows"
        && $req['name'] === 'My flow'
    );
});

it('lists workflows with filters', function () {
    Http::fake(fn () => Http::response(['items' => []], 200));

    $filters = ['active' => 'true', 'limit' => 20];
    N8nClient::workflows()->list($filters);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method())
        && $req->url() === "{$url}/workflows?active=true&limit=20"
    );
});

it('gets workflow without pinned data', function () {
    Http::fake(fn () => Http::response(['id' => 'wf1'], 200));

    N8nClient::workflows()->get('wf1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method())
        && $req->url() === "{$url}/workflows/wf1?excludePinnedData=false"
    );
});

it('updates workflow', function () {
    Http::fake(fn () => Http::response(['name' => 'updated'], 200));

    N8nClient::workflows()->update('wf1', ['name' => 'updated']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isPut($req->method())
        && $req->url() === "{$url}/workflows/wf1"
        && $req['name'] === 'updated'
    );
});

it('deletes workflow', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::workflows()->delete('wf1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isDelete($req->method())
        && $req->url() === "{$url}/workflows/wf1"
    );
});

it('activates and deactivates workflow', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::workflows()->activate('wf1');
    N8nClient::workflows()->deactivate('wf1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSentCount(2);

    Http::assertSent(
        fn ($req) => RequestMethod::isPost($req->method())
        && $req->url() === "{$url}/workflows/wf1/activate"
    );
    Http::assertSent(
        fn ($req) => RequestMethod::isPost($req->method())
        && $req->url() === "{$url}/workflows/wf1/deactivate"
    );
});

it('transfers workflow', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::workflows()->transfer('wf1', 'dest');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isPut($req->method())
        && $req->url() === "{$url}/workflows/wf1/transfer"
        && $req['destinationProjectId'] === 'dest'
    );
});

it('gets and updates workflow tags', function () {
    Http::fake(fn () => Http::response([], 200));

    N8nClient::workflows()->tags('wf1');
    N8nClient::workflows()->updateTags('wf1', ['t1', 't2']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::isGet($req->method())
        && $req->url() === "{$url}/workflows/wf1/tags"
    );

    Http::assertSent(
        fn ($req) => RequestMethod::isPut($req->method())
        && $req->url() === "{$url}/workflows/wf1/tags"
        && $req->data() === ['t1', 't2']
    );
});
