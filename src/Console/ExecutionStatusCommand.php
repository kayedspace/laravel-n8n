<?php

namespace KayedSpace\N8n\Console;

use Exception;
use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class ExecutionStatusCommand extends Command
{
    protected $signature = 'n8n:executions:status {id : Execution ID}';

    protected $description = 'Check n8n execution status';

    public function handle(): int
    {
        $id = (int) $this->argument('id');

        $this->info("Fetching execution {$id}...");

        try {
            $execution = N8nClient::executions()->get($id, true);
            $executionArray = collect($execution)->toArray();

            $status = $executionArray['status'] ?? 'unknown';
            $mode = $executionArray['mode'] ?? 'N/A';
            $startedAt = $executionArray['startedAt'] ?? 'N/A';
            $stoppedAt = $executionArray['stoppedAt'] ?? 'N/A';

            $this->info("Execution ID: {$id}");
            $this->info("Status: {$status}");
            $this->info("Mode: {$mode}");
            $this->info("Started: {$startedAt}");
            $this->info("Stopped: {$stoppedAt}");

            if ($status === 'success') {
                $this->info('✓ Execution completed successfully');
            } elseif (in_array($status, ['error', 'failed', 'crashed'])) {
                $this->error('✗ Execution failed');
            } else {
                $this->warn('⚠ Execution is still running');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to fetch execution: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
