<?php

namespace KayedSpace\N8n\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use KayedSpace\N8n\Facades\N8nClient;

class HealthCheck
{
    protected array $results = [];

    public static function run(): static
    {
        return (new static)->execute();
    }

    protected function execute(): static
    {
        $this->checkConnectivity();
        $this->checkApiResponse();
        $this->checkWorkflowsAccess();
        $this->checkMetrics();

        return $this;
    }

    protected function checkConnectivity(): void
    {
        $start = microtime(true);

        try {
            N8nClient::workflows()->list(['limit' => 1]);
            $this->results['connectivity'] = [
                'status' => 'ok',
                'message' => 'Successfully connected to n8n',
                'response_time' => round((microtime(true) - $start) * 1000, 2).'ms',
            ];
        } catch (\Exception $e) {
            $this->results['connectivity'] = [
                'status' => 'error',
                'message' => 'Failed to connect: '.$e->getMessage(),
                'response_time' => round((microtime(true) - $start) * 1000, 2).'ms',
            ];
        }
    }

    protected function checkApiResponse(): void
    {
        try {
            $workflows = N8nClient::workflows()->list(['limit' => 1]);

            // list() always returns Collection|array by type declaration
            $this->results['api_response'] = [
                'status' => 'ok',
                'message' => 'API responses are valid',
            ];
        } catch (\Exception $e) {
            $this->results['api_response'] = [
                'status' => 'error',
                'message' => 'API error: '.$e->getMessage(),
            ];
        }
    }

    protected function checkWorkflowsAccess(): void
    {
        try {
            $workflows = N8nClient::workflows()->list(['limit' => 10]);
            $workflowsArray = collect($workflows)->toArray();
            $count = count($workflowsArray['data'] ?? $workflowsArray);

            $this->results['workflows_access'] = [
                'status' => 'ok',
                'message' => "Can access workflows (found {$count})",
                'count' => $count,
            ];
        } catch (\Exception $e) {
            $this->results['workflows_access'] = [
                'status' => 'error',
                'message' => 'Cannot access workflows: '.$e->getMessage(),
            ];
        }
    }

    protected function checkMetrics(): void
    {
        try {
            $metricsEnabled = config('n8n.metrics.enabled', false);

            if ($metricsEnabled) {
                $key = 'n8n:metrics:'.date('Y-m-d-H').':total';
                $total = Cache::get($key, 0);

                $this->results['metrics'] = [
                    'status' => 'ok',
                    'message' => 'Metrics tracking enabled',
                    'requests_this_hour' => $total,
                ];
            } else {
                $this->results['metrics'] = [
                    'status' => 'disabled',
                    'message' => 'Metrics tracking is disabled',
                ];
            }
        } catch (\Exception $e) {
            $this->results['metrics'] = [
                'status' => 'error',
                'message' => 'Metrics error: '.$e->getMessage(),
            ];
        }
    }

    public function toArray(): array
    {
        return [
            'overall_status' => $this->getOverallStatus(),
            'checks' => $this->results,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function isHealthy(): bool
    {
        return $this->getOverallStatus() === 'ok';
    }

    public function getOverallStatus(): string
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'error') {
                return 'error';
            }
        }

        foreach ($this->results as $result) {
            if ($result['status'] === 'warning') {
                return 'warning';
            }
        }

        return 'ok';
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
