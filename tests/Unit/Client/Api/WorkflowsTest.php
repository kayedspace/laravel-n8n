<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ApiRequestCompleted;
use KayedSpace\N8n\Events\WorkflowActivated;
use KayedSpace\N8n\Events\WorkflowCreated;
use KayedSpace\N8n\Events\WorkflowDeactivated;
use KayedSpace\N8n\Events\WorkflowDeleted;
use KayedSpace\N8n\Events\WorkflowTagsUpdated;
use KayedSpace\N8n\Events\WorkflowTransferred;
use KayedSpace\N8n\Events\WorkflowUpdated;
use KayedSpace\N8n\Facades\N8nClient;

it('creates a workflow', function () {
    Http::fake(fn () => Http::response(['id' => 'wf1'], 201));

    $payload = ['name' => 'My flow'];
    $resp = N8nClient::workflows()->create($payload);

    expect($resp)->toMatchArray(['id' => 'wf1']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Post->is($req->method())
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
        fn ($req) => RequestMethod::Get->is($req->method())
            && $req->url() === "{$url}/workflows?active=true&limit=20"
    );
});

it('gets workflow without pinned data', function () {
    Http::fake(fn () => Http::response(['id' => 'wf1'], 200));

    N8nClient::workflows()->get('wf1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Get->is($req->method())
            && $req->url() === "{$url}/workflows/wf1?excludePinnedData=false"
    );
});

it('updates workflow', function () {
    Http::fake(fn () => Http::response(['name' => 'updated'], 200));

    N8nClient::workflows()->update('wf1', ['name' => 'updated']);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Put->is($req->method())
            && $req->url() === "{$url}/workflows/wf1"
            && $req['name'] === 'updated'
    );
});

it('deletes workflow', function () {
    Http::fake(fn () => Http::response([], 204));

    N8nClient::workflows()->delete('wf1');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Delete->is($req->method())
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
        fn ($req) => RequestMethod::Post->is($req->method())
            && $req->url() === "{$url}/workflows/wf1/activate"
    );
    Http::assertSent(
        fn ($req) => RequestMethod::Post->is($req->method())
            && $req->url() === "{$url}/workflows/wf1/deactivate"
    );
});

it('transfers workflow', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::workflows()->transfer('wf1', 'dest');

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($req) => RequestMethod::Put->is($req->method())
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
        fn ($req) => RequestMethod::Get->is($req->method())
            && $req->url() === "{$url}/workflows/wf1/tags"
    );

    Http::assertSent(
        fn ($req) => RequestMethod::Put->is($req->method())
            && $req->url() === "{$url}/workflows/wf1/tags"
            && $req->data() === ['t1', 't2']
    );
});

it('activates many workflows', function () {
    Http::fake(fn () => Http::response(['active' => true], 200));

    $results = N8nClient::workflows()->activateMany(['wf1', 'wf2', 'wf3']);

    expect($results)->toHaveCount(3)
        ->and($results['wf1']['success'])->toBeTrue()
        ->and($results['wf2']['success'])->toBeTrue()
        ->and($results['wf3']['success'])->toBeTrue();

    Http::assertSentCount(3);
});

it('deactivates many workflows', function () {
    Http::fake(fn () => Http::response(['active' => false], 200));

    $results = N8nClient::workflows()->deactivateMany(['wf1', 'wf2']);

    expect($results)->toHaveCount(2)
        ->and($results['wf1']['success'])->toBeTrue()
        ->and($results['wf2']['success'])->toBeTrue();

    Http::assertSentCount(2);
});

it('deletes many workflows', function () {
    Http::fake(fn () => Http::response([], 204));

    $results = N8nClient::workflows()->deleteMany(['wf1', 'wf2']);

    expect($results)->toHaveCount(2)
        ->and($results['wf1']['success'])->toBeTrue()
        ->and($results['wf2']['success'])->toBeTrue();

    Http::assertSentCount(2);
});

it('exports workflows', function () {
    Http::fake(fn () => Http::response(['id' => 'wf1', 'name' => 'Test'], 200));

    $workflows = N8nClient::workflows()->export(['wf1', 'wf2']);

    expect($workflows)->toHaveCount(2)
        ->and($workflows[0])->toHaveKey('id')
        ->and($workflows[0])->toHaveKey('name');

    Http::assertSentCount(2);
});

it('imports workflows', function () {
    Http::fake(fn () => Http::response(['id' => 'new-wf'], 201));

    $workflows = [
        ['name' => 'Workflow 1', 'nodes' => []],
        ['name' => 'Workflow 2', 'nodes' => []],
    ];

    $results = N8nClient::workflows()->import($workflows);

    expect($results)->toHaveCount(2)
        ->and($results[0]['success'])->toBeTrue()
        ->and($results[1]['success'])->toBeTrue();

    Http::assertSentCount(2);
});

it('dispatches workflow transferred event', function () {
    Event::fake([WorkflowTransferred::class]);
    Http::fake(fn () => Http::response(['id' => 'wf1'], 200));

    N8nClient::workflows()->transfer('wf1', 'proj-1');

    Event::assertDispatched(WorkflowTransferred::class, function ($event) {
        return $event->data['id'] === 'wf1'
            && $event->data['destination_project_id'] === 'proj-1';
    });
});

it('dispatches workflow tags updated event', function () {
    Event::fake([WorkflowTagsUpdated::class]);
    Http::fake(fn () => Http::response(['tags' => ['t1']], 200));

    N8nClient::workflows()->updateTags('wf1', ['t1', 't2']);

    Event::assertDispatched(WorkflowTagsUpdated::class, function ($event) {
        return $event->data['id'] === 'wf1'
            && $event->data['tags'] === ['t1', 't2'];
    });
});

it('dispatches workflow created event', function () {
    Event::fake([WorkflowCreated::class]);
    Http::fake(fn () => Http::response(['id' => 'wf-created'], 201));

    N8nClient::workflows()->create(['name' => 'wf-created']);

    Event::assertDispatched(WorkflowCreated::class, fn ($event) => $event->data['id'] === 'wf-created');
});

it('dispatches workflow updated event', function () {
    Event::fake([WorkflowUpdated::class]);
    Http::fake(fn () => Http::response(['id' => 'wf1', 'name' => 'updated'], 200));

    N8nClient::workflows()->update('wf1', ['name' => 'updated']);

    Event::assertDispatched(WorkflowUpdated::class, fn ($event) => $event->data['name'] === 'updated');
});

it('dispatches workflow activated event', function () {
    Event::fake([WorkflowActivated::class]);
    Http::fake(fn () => Http::response(['active' => true], 200));

    N8nClient::workflows()->activate('wf1');

    Event::assertDispatched(WorkflowActivated::class, fn ($event) => $event->data['id'] === 'wf1');
});

it('dispatches workflow deactivated event', function () {
    Event::fake([WorkflowDeactivated::class]);
    Http::fake(fn () => Http::response(['active' => false], 200));

    N8nClient::workflows()->deactivate('wf1');

    Event::assertDispatched(WorkflowDeactivated::class, fn ($event) => $event->data['id'] === 'wf1');
});

it('dispatches workflow deleted event', function () {
    Event::fake([WorkflowDeleted::class]);
    Http::fake(fn () => Http::response([], 204));

    N8nClient::workflows()->delete('wf1');

    Event::assertDispatched(WorkflowDeleted::class, fn ($event) => $event->data['id'] === 'wf1');
});

it('dispatches api request events for cached responses', function () {
    Event::fake([ApiRequestCompleted::class]);
    Config::set('n8n.cache.enabled', true);
    Config::set('n8n.cache.store', 'array');
    Cache::store('array')->flush();

    Http::fake(fn () => Http::response(['id' => 'wf-cache'], 200));

    N8nClient::workflows()->get('wf-cache');
    N8nClient::workflows()->get('wf-cache');

    Http::assertSentCount(1);
    Event::assertDispatchedTimes(ApiRequestCompleted::class, 2);
});

it('auto-paginates all workflows', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['items' => [['id' => 'wf1']], 'nextCursor' => 'cursor1'], 200)
            ->push(['items' => [['id' => 'wf2']], 'nextCursor' => null], 200),
    ]);

    $workflows = N8nClient::workflows()->all();

    // Verify pagination worked (2 requests made)
    Http::assertSentCount(2);

    // Verify we got data back
    expect($workflows)->not->toBeEmpty();
});

it('iterates through workflows', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['items' => [['id' => 'wf1']], 'nextCursor' => 'cursor1'], 200)
            ->push(['items' => [['id' => 'wf2']], 'nextCursor' => null], 200),
    ]);

    $items = [];
    foreach (N8nClient::workflows()->listIterator() as $workflow) {
        $items[] = $workflow;
    }

    // Verify pagination worked (2 requests made)
    Http::assertSentCount(2);

    // Verify we iterated through items
    expect($items)->not->toBeEmpty();
});
