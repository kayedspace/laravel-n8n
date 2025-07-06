<?php

use KayedSpace\N8n\Facades\N8nClient;

test('sends auth header', function () {
    $url = Config::get('n8n.api.base_url');

    Http::fake(fn () => Http::response(['ok' => true], 200));

    N8nClient::audit()->generate();

    Http::assertSent(
        fn ($req) => $req->header('X-N8N-API-KEY') === ['test-api-key']
        && $req->url() === "$url/audit"
    );
});
