<?php

namespace App\Traits;

trait HasMemory
{
    /**
     * A quick-and-dirty in-memory cache for instances of objects.
     */
    private array $cache = [];

    /**
     * Stores the results of $callback at a $key in the $cache private variable.
     */
    private function remember(string $key, callable $callback): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $this->cache[$key] = $callback();

        return $this->cache[$key];
    }
}
