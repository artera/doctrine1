<?php

class Doctrine_Cache_Apcu extends Doctrine_Cache_Driver
{
    /**
     * @param array $options associative array of cache driver options
     *
     * @throws Doctrine_Cache_Exception
     */
    public function __construct($options = [])
    {
        if (!function_exists('apcu_fetch')) {
            throw new Doctrine_Cache_Exception('The apcu extension must be loaded for using this backend !');
        }
        parent::__construct($options);
    }

    protected function doFetch(string $id, bool $testCacheValidity = true)
    {
        return apcu_fetch($id);
    }

    protected function doContains(string $id): bool
    {
        apcu_fetch($id, $found);
        return $found;
    }

    protected function doSave(string $id, $data, ?int $lifeTime = null): bool
    {
        return apcu_store($id, $data, $lifeTime ?: 0);
    }

    protected function doDelete(string $id): bool
    {
        return apcu_delete($id);
    }

    protected function getCacheKeys(): array
    {
        $ci   = apcu_cache_info();
        $keys = [];

        foreach ($ci['cache_list'] as $entry) {
            $keys[] = $entry['info'];
        }

        return $keys;
    }
}
