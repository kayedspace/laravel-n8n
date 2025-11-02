<?php

namespace KayedSpace\N8n\Console;

use Exception;
use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class TestWebhookCommand extends Command
{
    protected $signature = 'n8n:test-webhook {path : Webhook path} {--data= : JSON data to send}';

    protected $description = 'Test n8n webhook trigger';

    public function handle(): int
    {
        $path = $this->argument('path');
        $dataJson = $this->option('data');

        $data = $dataJson ? json_decode($dataJson, true) : ['test' => true, 'timestamp' => time()];

        if ($dataJson && json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data provided');

            return self::FAILURE;
        }

        $this->info("Triggering webhook: {$path}");
        $this->info('Data: '.json_encode($data, JSON_PRETTY_PRINT));

        try {
            $response = N8nClient::webhooks()->request($path, $data);

            $this->info('✓ Webhook triggered successfully');
            $this->line('Response:');
            $this->line(json_encode(collect($response)->toArray(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to trigger webhook: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
