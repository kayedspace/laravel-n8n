<?php

namespace KayedSpace\N8n\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Client\Api\Audit;
use KayedSpace\N8n\Client\Api\Credentials;
use KayedSpace\N8n\Client\Api\Executions;
use KayedSpace\N8n\Client\Api\Projects;
use KayedSpace\N8n\Client\Api\SourceControl;
use KayedSpace\N8n\Client\Api\Tags;
use KayedSpace\N8n\Client\Api\Users;
use KayedSpace\N8n\Client\Api\Variables;
use KayedSpace\N8n\Client\Api\Workflows;
use KayedSpace\N8n\Client\Webhook\Webhooks;
use KayedSpace\N8n\Enums\RequestMethod;

class N8nClient
{
    protected PendingRequest $httpClient;

    public function __construct()
    {
        $timeout = Config::get('n8n.timeout');
        $throw = Config::get('n8n.throw');
        $retry = Config::get('n8n.retry');

        $this->httpClient = Http::when($timeout, fn ($request) => $request->timeout($timeout))
            ->when($throw, fn ($request) => $request->throwIf($throw))
            ->when($retry, fn ($request) => $request->retry($retry));
    }

    public function webhooks(RequestMethod $method = RequestMethod::Post): Webhooks
    {
        return new Webhooks($this->httpClient, $method);
    }

    public function audit(): Audit
    {
        return new Audit($this->httpClient);
    }

    public function credentials(): Credentials
    {
        return new Credentials($this->httpClient);
    }

    public function executions(): Executions
    {
        return new Executions($this->httpClient);
    }

    public function workflows(): Workflows
    {

        return new Workflows($this->httpClient);
    }

    public function tags(): Tags
    {
        return new Tags($this->httpClient);
    }

    public function users(): Users
    {
        return new Users($this->httpClient);
    }

    public function variables(): Variables
    {
        return new Variables($this->httpClient);
    }

    public function projects(): Projects
    {
        return new Projects($this->httpClient);
    }

    public function sourceControl(): SourceControl
    {
        return new SourceControl($this->httpClient);
    }
}
