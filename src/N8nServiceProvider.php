<?php

namespace KayedSpace\N8n;

use Illuminate\Support\ServiceProvider;
use KayedSpace\N8n\Client\N8nClient;
use KayedSpace\N8n\Console\ActivateWorkflowCommand;
use KayedSpace\N8n\Console\DeactivateWorkflowCommand;
use KayedSpace\N8n\Console\ExecutionStatusCommand;
use KayedSpace\N8n\Console\HealthCheckCommand;
use KayedSpace\N8n\Console\ListWorkflowsCommand;
use KayedSpace\N8n\Console\TestWebhookCommand;

class N8nServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/n8n.php', 'n8n');
        $this->app->bind('n8n', fn ($app) => new N8nClient);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/n8n.php' => $this->app->configPath('n8n.php'),
        ], 'n8n-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                HealthCheckCommand::class,
                ListWorkflowsCommand::class,
                ActivateWorkflowCommand::class,
                DeactivateWorkflowCommand::class,
                ExecutionStatusCommand::class,
                TestWebhookCommand::class,
            ]);
        }
    }
}
