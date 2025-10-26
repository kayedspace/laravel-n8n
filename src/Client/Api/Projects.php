<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\ProjectCreated;
use KayedSpace\N8n\Events\ProjectDeleted;
use KayedSpace\N8n\Events\ProjectUpdated;
use KayedSpace\N8n\Events\ProjectUserRemoved;
use KayedSpace\N8n\Events\ProjectUserRoleChanged;
use KayedSpace\N8n\Events\ProjectUsersAdded;

class Projects extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $payload): Collection|array
    {
        $result = $this->request(RequestMethod::Post, '/projects', $payload);

        $this->dispatchResourceEvent(new ProjectCreated(
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
        return $this->request(RequestMethod::Get, '/projects', array_filter([
            'limit' => $limit,
            'cursor' => $cursor,
        ]));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function update(string $projectId, array $payload): void
    {
        $this->request(RequestMethod::Put, "/projects/{$projectId}", $payload); // 204

        $this->dispatchResourceEvent(new ProjectUpdated($projectId));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $projectId): void
    {
        $this->request(RequestMethod::Delete, "/projects/{$projectId}"); // 204

        $this->dispatchResourceEvent(new ProjectDeleted($projectId));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function addUsers(string $projectId, array $relations): void
    {
        $result = $this->request(RequestMethod::Post, "/projects/{$projectId}/users", ['relations' => $relations]);

        $this->dispatchResourceEvent(new ProjectUsersAdded(
            $projectId,
            $relations,
            $this->asArray($result)
        ));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function changeUserRole(string $projectId, string $userId, string $role): void
    {
        $result = $this->request(RequestMethod::Patch, "/projects/{$projectId}/users/{$userId}", ['role' => $role]);

        $this->dispatchResourceEvent(new ProjectUserRoleChanged(
            $projectId,
            $userId,
            $role,
            $this->asArray($result)
        ));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function removeUser(string $projectId, string $userId): void
    {
        $result = $this->request(RequestMethod::Delete, "/projects/{$projectId}/users/{$userId}");

        $this->dispatchResourceEvent(new ProjectUserRemoved(
            $projectId,
            $userId,
            $this->asArray($result)
        ));
    }
}
