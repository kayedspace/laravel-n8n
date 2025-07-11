<?php

use KayedSpace\N8n\Client\Api\Audit;
use KayedSpace\N8n\Client\Api\Credentials;
use KayedSpace\N8n\Client\Api\Executions;
use KayedSpace\N8n\Client\Api\Projects;
use KayedSpace\N8n\Client\Api\SourceControl;
use KayedSpace\N8n\Client\Api\Tags;
use KayedSpace\N8n\Client\Api\Users;
use KayedSpace\N8n\Client\Api\Variables;
use KayedSpace\N8n\Client\Api\Workflows;
use KayedSpace\N8n\Client\N8nClient;
use KayedSpace\N8n\Client\Webhook\Webhooks;

test('returns correct API instances', function () {
    $client = app(N8nClient::class);
    expect($client->audit())->toBeInstanceOf(Audit::class)
        ->and($client->credentials())->toBeInstanceOf(Credentials::class)
        ->and($client->workflows())->toBeInstanceOf(Workflows::class)
        ->and($client->executions())->toBeInstanceOf(Executions::class)
        ->and($client->users())->toBeInstanceOf(Users::class)
        ->and($client->projects())->toBeInstanceOf(Projects::class)
        ->and($client->sourceControl())->toBeInstanceOf(SourceControl::class)
        ->and($client->tags())->toBeInstanceOf(Tags::class)
        ->and($client->variables())->toBeInstanceOf(Variables::class)
        ->and($client->webhooks())->toBeInstanceOf(Webhooks::class);
});
