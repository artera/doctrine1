<?php

namespace Doctrine1\Cache;

class PHPArray extends \Doctrine1\Cache\Driver
{
    /**
     * @var array $data         an array of cached data
     */
    protected $data = [];

    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param  string  $id                cache id
     * @param  boolean $testCacheValidity if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    protected function doFetch(string $id, bool $testCacheValidity = true)
    {
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }
        return false;
    }

    protected function doContains(string $id): bool
    {
        return isset($this->data[$id]);
    }

    protected function doSave(string $id, $data, ?int $lifeTime = null): bool
    {
        $this->data[$id] = $data;
        return true;
    }

    /**
     * Remove a cache record directly. This method is implemented by the cache
     * drivers and used in \Doctrine1\Cache\Driver::delete()
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    protected function doDelete(string $id): bool
    {
        $exists = isset($this->data[$id]);

        unset($this->data[$id]);

        return $exists;
    }

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    protected function getCacheKeys(): array
    {
        return array_keys($this->data);
    }
}
