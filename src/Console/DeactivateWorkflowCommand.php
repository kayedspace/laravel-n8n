<?php

namespace KayedSpace\N8n\Console;

use Exception;
use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class DeactivateWorkflowCommand extends Command
{
    protected $signature = 'n8n:workflows:deactivate {id : Workflow ID}';

    protected $description = 'Deactivate an n8n workflow';

    public function handle(): int
    {
        $id = $this->argument('id');

        $this->info("Deactivating workflow {$id}...");

        try {
            N8nClient::workflows()->deactivate($id);

            $this->info("✓ Workflow {$id} deactivated successfully");

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to deactivate workflow: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
