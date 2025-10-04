<?php

namespace KayedSpace\N8n\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\WebhookTriggered;

class TriggerN8nWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public array $data,
        public RequestMethod $method = RequestMethod::Post,
        public ?array $basicAuth = null
    ) {
    }

    public function handle(): void
    {
        $baseUrl = Config::string('n8n.webhook.base_url');

        $request = Http::baseUrl($baseUrl)
            ->timeout(Config::integer('n8n.timeout', 120));

        if ($this->basicAuth) {
            $request = $request->withBasicAuth(
                $this->basicAuth['username'],
                $this->basicAuth['password']
            );
        }

        $response = $request->{$this->method->value}($this->path, $this->data);

        // Dispatch event
        if (Config::get('n8n.events.enabled', true)) {
            Event::dispatch(new WebhookTriggered(
                $this->path,
                $this->data,
                $response->json() ?? []
            ));
        }
    }
}
