<?php

namespace KayedSpace\N8n\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use KayedSpace\N8n\Client\Webhook\Webhooks;
use KayedSpace\N8n\Enums\RequestMethod;

class TriggerN8nWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public array $data,
        public RequestMethod $method = RequestMethod::Post,
    ) {}

    public function handle(): void
    {
        // Create Webhooks client instance
        $webhooks = new Webhooks(Http::asJson(), $this->method);

        // Execute webhook request with all advanced features
        // (logging, metrics, events, caching, client modifiers, exception handling)
        $webhooks->sync()->request($this->path, $this->data);
    }
}
