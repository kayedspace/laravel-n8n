<?php

namespace KayedSpace\N8n\Console;

use Illuminate\Console\Command;
use KayedSpace\N8n\Facades\N8nClient;

class ListWorkflowsCommand extends Command
{
    protected $signature = 'n8n:workflows:list {--active= : Filter by active status} {--limit=10 : Number of workflows to show}';

    protected $description = 'List n8n workflows';

    public function handle(): int
    {
        $this->info('Fetching workflows...');

        try {
            $filters = ['limit' => $this->option('limit')];

            if ($this->option('active') !== null) {
                $filters['active'] = $this->option('active') === 'true';
            }

            $result = N8nClient::workflows()->list($filters);
            $workflows = is_array($result) ? ($result['data'] ?? $result) : $result;

            if (empty($workflows)) {
                $this->warn('No workflows found');
                return self::SUCCESS;
            }

            $rows = [];
            foreach ($workflows as $workflow) {
                $rows[] = [
                    is_array($workflow) ? ($workflow['id'] ?? 'N/A') : $workflow['id'],
                    is_array($workflow) ? ($workflow['name'] ?? 'N/A') : $workflow['name'],
                    is_array($workflow) ? (isset($workflow['active']) && $workflow['active'] ? 'Yes' : 'No') : ($workflow['active'] ? 'Yes' : 'No'),
                ];
            }

            $this->table(['ID', 'Name', 'Active'], $rows);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to list workflows: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
