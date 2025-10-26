<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\WebhookTriggered;
use KayedSpace\N8n\Facades\N8nClient;
use KayedSpace\N8n\Jobs\TriggerN8nWebhook;

it('send webhook request', function () {
    $url = Config::get('n8n.webhook.base_url');

    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::webhooks()->request('/path-to-your-webhook');

    Http::assertSent(fn (Request $req) => "$url/path-to-your-webhook" === $req->url());
});

it('dispatches webhook triggered event for sync requests', function () {
    Event::fake([WebhookTriggered::class]);
    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::webhooks()->request('/sync-webhook', ['foo' => 'bar']);

    Event::assertDispatched(WebhookTriggered::class, fn ($event) => $event->data['path'] === '/sync-webhook');
});

it('queues webhook job when async mode enabled', function () {
    Queue::fake();
    Config::set('n8n.queue.enabled', true);
    Config::set('n8n.queue.connection', 'sync');
    Config::set('n8n.queue.queue', 'default');

    N8nClient::webhooks()->async()->request('/queued', ['foo' => 'bar']);

    Queue::assertPushed(TriggerN8nWebhook::class, function ($job) {
        return $job->path === '/queued';
    });
});

it('dispatches webhook triggered event from queued job', function () {
    Event::fake([WebhookTriggered::class]);
    Http::fake(fn () => Http::response(['ok' => true], 200));

    $job = new TriggerN8nWebhook('/job-path', ['hello' => 'world'], RequestMethod::Post);
    $job->handle();

    Event::assertDispatched(WebhookTriggered::class, fn ($event) => $event->data['path'] === '/job-path');
});
