<?php

namespace KayedSpace\N8n\Console;

use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class ActivateWorkflowCommand extends Command
{
    protected $signature = 'n8n:workflows:activate {id : Workflow ID}';

    protected $description = 'Activate an n8n workflow';

    public function handle(): int
    {
        $id = $this->argument('id');

        $this->info("Activating workflow {$id}...");

        try {
            N8nClient::workflows()->activate($id);

            $this->info("✓ Workflow {$id} activated successfully");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to activate workflow: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
