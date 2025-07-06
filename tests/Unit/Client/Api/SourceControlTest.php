<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('executes source-control pull', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    $payload = ['branch' => 'main', 'force' => true];
    $resp = N8nClient::sourceControl()->pull($payload);

    expect($resp)->toMatchArray(['ok' => true]);

    $url = Config::get('n8n.api.base_url');

    Http::assertSent(
        fn ($r) => RequestMethod::Post->is($r->method())
        && $r->url() === "{$url}/source-control/pull"
        && $r->data() === $payload
    );
});
