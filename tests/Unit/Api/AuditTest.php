<?php

use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Facades\N8nClient;

it('generates audit ', function () {
    $url = Config::get('n8n.api.base_url');

    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::audit()->generate(['foo' => 'bar']);

    Http::assertSent(
        fn ($req) => RequestMethod::isPost($req->method())
        && $req->url() === "$url/audit"
        && $req['additionalOptions'] === ['foo' => 'bar']
    );
});
