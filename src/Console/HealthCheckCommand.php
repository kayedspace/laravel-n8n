<?php

namespace KayedSpace\N8n\Console;

use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class HealthCheckCommand extends Command
{
    protected $signature = 'n8n:health';

    protected $description = 'Check n8n instance connectivity and status';

    public function handle(): int
    {
        $this->info('Checking n8n instance health...');

        try {
            $workflows = N8nClient::workflows()->list(['limit' => 1]);

            $this->info('✓ Successfully connected to n8n');
            $this->info('✓ API is responsive');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to connect to n8n');
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
