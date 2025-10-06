<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ExecutionCompleted;
use KayedSpace\N8n\Events\ExecutionDeleted;
use KayedSpace\N8n\Events\ExecutionFailed;
use KayedSpace\N8n\Exceptions\ExecutionFailedException;

class Executions extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(array $filters = []): Collection|array
    {
        // filters: includeData, status, workflowId, projectId, limit, cursor

        return $this->request(RequestMethod::Get, '/executions', $filters);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function get(int $id, bool $includeData = false): Collection|array
    {
        $result = $this->request(RequestMethod::Get, "/executions/{$id}", ['includeData' => $includeData]);

        // Dispatch appropriate event based on status
        $resultArray = is_array($result) ? $result : $result->toArray();
        $status = $resultArray['status'] ?? null;

        if ($status === 'success') {
            $this->dispatchResourceEvent(new ExecutionCompleted($resultArray));
        } elseif (in_array($status, ['error', 'failed', 'crashed'])) {
            $this->dispatchResourceEvent(new ExecutionFailed($resultArray));
        }

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(int $id): Collection|array
    {
        $result = $this->request(RequestMethod::Delete, "/executions/{$id}");

        $this->dispatchResourceEvent(new ExecutionDeleted($id));

        return $result;
    }

    /**
     * Delete multiple executions.
     */
    public function deleteMany(array $ids): array
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $result = $this->delete($id);
                $results[$id] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Wait for execution to complete with polling.
     *
     * @param  int  $id  Execution ID
     * @param  int  $timeout  Maximum wait time in seconds (default: 60)
     * @param  int  $interval  Polling interval in seconds (default: 2)
     * @return Collection|array Final execution state
     *
     * @throws ExecutionFailedException If execution fails or times out
     */
    public function wait(int $id, int $timeout = 60, int $interval = 2): Collection|array
    {
        $startTime = time();

        while (true) {
            $execution = $this->get($id, true);
            $executionArray = is_array($execution) ? $execution : $execution->toArray();
            $status = $executionArray['status'] ?? 'unknown';

            // Check if execution is complete
            if ($status === 'success') {
                return $execution;
            }

            if (in_array($status, ['error', 'failed', 'crashed'])) {
                throw new ExecutionFailedException(
                    "Execution {$id} failed with status: {$status}",
                    0,
                    null,
                    null,
                    $executionArray
                );
            }

            // Check timeout
            if ((time() - $startTime) >= $timeout) {
                throw new ExecutionFailedException(
                    "Execution {$id} timed out after {$timeout} seconds. Current status: {$status}",
                    0,
                    null,
                    null,
                    $executionArray
                );
            }

            // Wait before next poll
            sleep($interval);
        }
    }
}
