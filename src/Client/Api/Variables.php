<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\VariableCreated;
use KayedSpace\N8n\Events\VariableDeleted;
use KayedSpace\N8n\Events\VariableUpdated;

class Variables extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $payload): Collection|array
    {
        $result = $this->request(RequestMethod::Post, '/variables', $payload);

        $this->dispatchResourceEvent(new VariableCreated(
            $this->asArray($result)
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(int $limit = 100, ?string $cursor = null): Collection|array
    {
        return $this->request(RequestMethod::Get, '/variables', array_filter([
            'limit' => $limit,
            'cursor' => $cursor,
        ]));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $id): void
    {
        $this->request(RequestMethod::Delete, "/variables/{$id}"); // 204

        $this->dispatchResourceEvent(new VariableDeleted($id));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function update(string $id, array $payload): void
    {
        $this->request(RequestMethod::Put, "/variables/{$id}", $payload); // 204

        $this->dispatchResourceEvent(new VariableUpdated($id));
    }

    /**
     * Create multiple variables.
     */
    public function createMany(array $variables): array
    {
        $results = [];

        foreach ($variables as $variable) {
            try {
                $result = $this->create($variable);
                $results[] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage(), 'variable' => $variable];
            }
        }

        return $results;
    }

    /**
     * Delete multiple variables.
     */
    public function deleteMany(array $ids): array
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $this->delete($id);
                $results[$id] = ['success' => true];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
