<?php

namespace KayedSpace\N8n\Concerns;

use Generator;
use Illuminate\Support\Collection;

trait HasPagination
{
    /**
     * Automatically paginate through all items.
     */
    public function all(array $filters = []): Collection|array
    {
        $allItems = [];
        $cursor = null;

        do {
            $response = $this->list(array_merge($filters, array_filter(['cursor' => $cursor])));

            // Handle both array and Collection responses
            $items = is_array($response) ? ($response['data'] ?? $response['items'] ?? $response) : $response;
            $meta = is_array($response) ? $response : $response->toArray();

            if (is_array($items) || $items instanceof Collection) {
                $allItems = array_merge($allItems, is_array($items) ? $items : $items->toArray());
            }

            // Get next cursor
            $cursor = $meta['nextCursor'] ?? $meta['next_cursor'] ?? null;
        } while ($cursor);

        return $this->formatResponse($allItems);
    }

    /**
     * Return a generator for memory-efficient pagination.
     */
    public function listIterator(array $filters = []): Generator
    {
        $cursor = null;

        do {
            $response = $this->list(array_merge($filters, array_filter(['cursor' => $cursor])));

            // Handle both array and Collection responses
            $items = is_array($response) ? ($response['data'] ?? $response['items'] ?? $response) : $response;
            $meta = is_array($response) ? $response : $response->toArray();

            if (is_array($items)) {
                foreach ($items as $item) {
                    yield $item;
                }
            } elseif ($items instanceof Collection) {
                foreach ($items as $item) {
                    yield $item;
                }
            }

            // Get next cursor
            $cursor = $meta['nextCursor'] ?? $meta['next_cursor'] ?? null;
        } while ($cursor);
    }
}
