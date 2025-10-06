<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\WorkflowActivated;
use KayedSpace\N8n\Events\WorkflowCreated;
use KayedSpace\N8n\Events\WorkflowDeactivated;
use KayedSpace\N8n\Events\WorkflowDeleted;
use KayedSpace\N8n\Events\WorkflowUpdated;

class Workflows extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $payload): Collection|array
    {
        $result = $this->request(RequestMethod::Post, '/workflows', $payload);

        $this->dispatchResourceEvent(new WorkflowCreated(
            is_array($result) ? $result : $result->toArray()
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(array $filters = []): Collection|array
    {
        // filters: active, tags, name, projectId, excludePinnedData, limit, cursor
        return $this->request(RequestMethod::Get, '/workflows', $filters);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function get(string $id, bool $excludePinnedData = false): Collection|array
    {
        return $this->request(RequestMethod::Get, "/workflows/{$id}", ['excludePinnedData' => $excludePinnedData]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function update(string $id, array $payload): Collection|array
    {
        $result = $this->request(RequestMethod::Put, "/workflows/{$id}", $payload);

        $this->dispatchResourceEvent(new WorkflowUpdated(
            is_array($result) ? $result : $result->toArray()
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $id): Collection|array
    {
        $result = $this->request(RequestMethod::Delete, "/workflows/{$id}");

        $this->dispatchResourceEvent(new WorkflowDeleted($id));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function activate(string $id): Collection|array
    {
        $result = $this->request(RequestMethod::Post, "/workflows/{$id}/activate");

        $this->dispatchResourceEvent(new WorkflowActivated(
            $id,
            is_array($result) ? $result : $result->toArray()
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function deactivate(string $id): Collection|array
    {
        $result = $this->request(RequestMethod::Post, "/workflows/{$id}/deactivate");

        $this->dispatchResourceEvent(new WorkflowDeactivated(
            $id,
            is_array($result) ? $result : $result->toArray()
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function transfer(string $id, string $destinationProjectId): Collection|array
    {
        return $this->request(RequestMethod::Put, "/workflows/{$id}/transfer", [
            'destinationProjectId' => $destinationProjectId,
        ]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function tags(string $id): Collection|array
    {
        return $this->request(RequestMethod::Get, "/workflows/{$id}/tags");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function updateTags(string $id, array $tagIds): Collection|array
    {
        return $this->request(RequestMethod::Put, "/workflows/{$id}/tags", $tagIds);
    }

    /**
     * Activate multiple workflows.
     */
    public function activateMany(array $ids): array
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $result = $this->activate($id);
                $results[$id] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Deactivate multiple workflows.
     */
    public function deactivateMany(array $ids): array
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $result = $this->deactivate($id);
                $results[$id] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Delete multiple workflows.
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
     * Export workflows to array.
     */
    public function export(array $ids): array
    {
        $workflows = [];

        foreach ($ids as $id) {
            try {
                $workflow = $this->get($id);
                $workflows[] = is_array($workflow) ? $workflow : $workflow->toArray();
            } catch (\Exception $e) {
                // Skip failed exports
            }
        }

        return $workflows;
    }

    /**
     * Import workflows from array.
     */
    public function import(array $workflows): array
    {
        $results = [];

        foreach ($workflows as $workflow) {
            try {
                // Remove ID if present to create new workflow
                unset($workflow['id']);
                $result = $this->create($workflow);
                $results[] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage(), 'workflow' => $workflow['name'] ?? 'unknown'];
            }
        }

        return $results;
    }
}
