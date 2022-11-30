<?php

namespace Doctrine1;

interface CacheInterface
{
    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param  string  $id                cache id
     * @param  boolean $testCacheValidity if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    public function fetch(string $id, bool $testCacheValidity = true);

    /**
     * Test if a cache record exists for the passed id
     *
     * @param  string $id cache id
     * @return bool
     */
    public function contains(string $id): bool;

    /**
     * Save a cache record and add the key to the index of cached keys
     *
     * @param  string    $id       cache id
     * @param  string    $data     data to cache
     * @param  int|null $lifeTime if != null, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @return boolean true if no problem
     */
    public function save(string $id, $data, ?int $lifeTime = null): bool;

    /**
     * Remove a cache record
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    public function delete(string $id): bool;
}
