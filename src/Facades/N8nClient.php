<?php

namespace KayedSpace\N8n\Facades;

use Illuminate\Support\Facades\Facade;
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

/**
 * @method static Webhooks webhooks(RequestMethod $method = RequestMethod::Get)
 * @method static Audit audit()
 * @method static Credentials credentials()
 * @method static Executions executions()
 * @method static Workflows workflows()
 * @method static Tags tags()
 * @method static Users users()
 * @method static Variables variables()
 * @method static Projects projects()
 * @method static SourceControl sourceControl()
 */
class N8nClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'n8n';
    }
}
