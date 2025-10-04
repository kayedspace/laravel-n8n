<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;

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
        return $this->request(RequestMethod::Post, '/users', $userPayloads);
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
        return $this->request(RequestMethod::Delete, "/users/{$idOrEmail}");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function changeRole(string $idOrEmail, string $newRoleName): Collection|array
    {
        return $this->request(RequestMethod::Patch, "/users/{$idOrEmail}/role", ['newRoleName' => $newRoleName]);
    }
}
