<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use KayedSpace\N8n\Enums\RequestMethod;

class Users extends AbstractApi
{
    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function list(array $filters = []): array
    {
        // filters: limit, cursor, includeRole, projectId
        return $this->request(RequestMethod::Get, '/users', $filters);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $userPayloads): array
    {
        // expects array of user objects
        return $this->request(RequestMethod::Post, '/users', $userPayloads);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function get(string $idOrEmail, bool $includeRole = false): array
    {
        return $this->request(RequestMethod::Get, "/users/{$idOrEmail}", ['includeRole' => $includeRole]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $idOrEmail): array
    {
        return $this->request(RequestMethod::Delete, "/users/{$idOrEmail}");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function changeRole(string $idOrEmail, string $newRoleName): array
    {
        return $this->request(RequestMethod::Patch, "/users/{$idOrEmail}/role", ['newRoleName' => $newRoleName]);
    }
}
