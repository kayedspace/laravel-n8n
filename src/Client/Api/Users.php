<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\UserCreated;
use KayedSpace\N8n\Events\UserDeleted;
use KayedSpace\N8n\Events\UserRoleChanged;

class Users extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(array $filters = []): Collection|array
    {
        // filters: limit, cursor, includeRole, projectId
        return $this->request(RequestMethod::Get, '/users', $filters);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $userPayloads): Collection|array
    {
        // expects array of user objects
        $result = $this->request(RequestMethod::Post, '/users', $userPayloads);

        $this->dispatchResourceEvent(new UserCreated(
            is_array($result) ? $result : $result->toArray()
        ));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function get(string $idOrEmail, bool $includeRole = false): Collection|array
    {
        return $this->request(RequestMethod::Get, "/users/{$idOrEmail}", ['includeRole' => $includeRole]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $idOrEmail): Collection|array
    {
        $result = $this->request(RequestMethod::Delete, "/users/{$idOrEmail}");

        $this->dispatchResourceEvent(new UserDeleted($idOrEmail));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function changeRole(string $idOrEmail, string $newRoleName): Collection|array
    {
        $result = $this->request(RequestMethod::Patch, "/users/{$idOrEmail}/role", ['newRoleName' => $newRoleName]);

        $this->dispatchResourceEvent(new UserRoleChanged($idOrEmail, $newRoleName));

        return $result;
    }
}
