<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;

class Tags extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $payload): Collection|array
    {
        return $this->request(RequestMethod::Post, '/tags', $payload);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(int $limit = 100, ?string $cursor = null): Collection|array
    {
        return $this->request(RequestMethod::Get, '/tags', array_filter([
            'limit' => $limit,
            'cursor' => $cursor,
        ]));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function get(string $id): Collection|array
    {
        return $this->request(RequestMethod::Get, "/tags/{$id}");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function update(string $id, array $payload): Collection|array
    {
        return $this->request(RequestMethod::Put, "/tags/{$id}", $payload);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $id): Collection|array
    {
        return $this->request(RequestMethod::Delete, "/tags/{$id}");
    }

    /**
     * Create multiple tags.
     */
    public function createMany(array $tags): array
    {
        $results = [];

        foreach ($tags as $tag) {
            try {
                $result = $this->create($tag);
                $results[] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage(), 'tag' => $tag];
            }
        }

        return $results;
    }

    /**
     * Delete multiple tags.
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
}
