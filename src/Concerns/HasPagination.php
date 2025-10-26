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

            $meta = $this->asArray($response);
            $items = $response instanceof Collection ? $response : ($meta['data'] ?? $meta['items'] ?? $meta);

            if ($items instanceof Collection) {
                $allItems = array_merge($allItems, $items->toArray());
            } elseif (is_array($items)) {
                $allItems = array_merge($allItems, $items);
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

            $meta = $this->asArray($response);
            $items = $response instanceof Collection ? $response : ($meta['data'] ?? $meta['items'] ?? $meta);

            if ($items instanceof Collection || is_array($items)) {
                foreach ($items as $item) {
                    yield $item;
                }
            }

            // Get next cursor
            $cursor = $meta['nextCursor'] ?? $meta['next_cursor'] ?? null;
        } while ($cursor);
    }
}
