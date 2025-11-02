<?php

namespace KayedSpace\N8n\Client\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use KayedSpace\N8n\Concerns\HasPagination;
use KayedSpace\N8n\Enums\RequestMethod;
use KayedSpace\N8n\Events\CredentialCreated;
use KayedSpace\N8n\Events\CredentialDeleted;
use KayedSpace\N8n\Events\CredentialTransferred;

class Credentials extends AbstractApi
{
    use HasPagination;

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function create(array $payload): Collection|array
    {
        $result = $this->request(RequestMethod::Post, '/credentials', $payload);

        $this->dispatchResourceEvent(new CredentialCreated(
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
        return $this->request(RequestMethod::Get, '/credentials', array_filter([
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
        return $this->request(RequestMethod::Get, "/credentials/{$id}");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function delete(string $id): Collection|array
    {
        $result = $this->request(RequestMethod::Delete, "/credentials/{$id}");

        $this->dispatchResourceEvent(new CredentialDeleted($id));

        return $result;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function schema(string $typeName): Collection|array
    {
        return $this->request(RequestMethod::Get, "/credentials/schema/{$typeName}");
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function transfer(string $id, string $destinationProjectId): Collection|array
    {
        $result = $this->request(RequestMethod::Put, "/credentials/{$id}/transfer", [
            'destinationProjectId' => $destinationProjectId,
        ]);

        $this->dispatchResourceEvent(new CredentialTransferred(
            $id,
            $destinationProjectId,
            $this->asArray($result)
        ));

        return $result;
    }
}
