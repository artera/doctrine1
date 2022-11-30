<?php

namespace Doctrine1\Cache;

abstract class Driver implements \Doctrine1\CacheInterface
{
    /**
     * @var array $options      an array of options
     */
    protected $options = [];

    /**
     * Configure cache driver with an array of options
     *
     * @param array $options an array of options
     */
    public function __construct($options = [])
    {
        $this->options = $options;
    }

    /**
     * Set option name and value
     *
     * @param  mixed $option the option name
     * @param  mixed $value  option value
     * @return boolean          TRUE on success, FALSE on failure
     */
    public function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
            return true;
        }
        return false;
    }

    /**
     * Get value of option
     *
     * @param  mixed $option the option name
     * @return mixed            option value
     */
    public function getOption($option)
    {
        if (!isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param  string  $id                cache id
     * @param  boolean $testCacheValidity if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    public function fetch(string $id, bool $testCacheValidity = true)
    {
        $key = $this->getKey($id);
        return $this->doFetch($key, $testCacheValidity);
    }

    public function contains(string $id): bool
    {
        $key = $this->getKey($id);
        return $this->doContains($key);
    }

    /**
     * Save some string datas into a cache record
     *
     * @param  string    $id       cache id
     * @param  string    $data     data to cache
     * @param  int|null $lifeTime if != false, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @return boolean true if no problem
     */
    public function save(string $id, $data, ?int $lifeTime = null): bool
    {
        $key = $this->getKey($id);
        return $this->doSave($key, $data, $lifeTime);
    }

    /**
     * Remove a cache record
     *
     * Note: This method accepts wildcards with the * character
     *
     * @param  string $id cache id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $key = $this->getKey($id);

        if (strpos($key, '*') !== false) {
            return $this->deleteByRegex('/' . str_replace('*', '.*', $key) . '/') > 0;
        }

        return $this->doDelete($key);
    }

    /**
     * Delete cache entries where the key matches a PHP regular expressions
     *
     * @param  string $regex
     * @return integer $count The number of deleted cache entries
     */
    public function deleteByRegex(string $regex): int
    {
        $count = 0;
        $keys  = $this->getCacheKeys();
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (preg_match($regex, $key)) {
                    $count++;
                    $this->delete($key);
                }
            }
        }
        return $count;
    }

    /**
     * Delete cache entries where the key has the passed prefix
     *
     * @param  string $prefix
     * @return integer $count The number of deleted cache entries
     */
    public function deleteByPrefix(string $prefix): int
    {
        $count = 0;
        $keys  = $this->getCacheKeys();
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (strpos($key, $prefix) === 0) {
                    $count++;
                    $this->delete($key);
                }
            }
        }
        return $count;
    }

    /**
     * Delete cache entries where the key has the passed suffix
     *
     * @param  string $suffix
     * @return integer $count The number of deleted cache entries
     */
    public function deleteBySuffix(string $suffix): int
    {
        $count = 0;
        $keys  = $this->getCacheKeys();
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (substr($key, -1 * strlen($suffix)) == $suffix) {
                    $count++;
                    $this->delete($key);
                }
            }
        }
        return $count;
    }

    /**
     * Delete all cache entries from the cache driver
     *
     * @return integer $count The number of deleted cache entries
     */
    public function deleteAll(): int
    {
        $count = 0;
        if (is_array($keys = $this->getCacheKeys())) {
            foreach ($keys as $key) {
                $count++;
                $this->delete($key);
            }
        }
        return $count;
    }

    /**
     * Get the hash key passing its suffix
     *
     * @param  string $id The hash key suffix
     * @return string     Hash key to be used by drivers
     */
    protected function getKey(string $id): string
    {
        $prefix = isset($this->options['prefix']) ? $this->options['prefix'] : '';

        if (!$prefix || strpos($id, $prefix) === 0) {
            return $id;
        } else {
            return $prefix . $id;
        }
    }

    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param  string  $id                cache id
     * @param  boolean $testCacheValidity if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    abstract protected function doFetch(string $id, bool $testCacheValidity = true);

    /**
     * Test if a cache record exists for the passed id
     *
     * @param  string $id cache id
     * @return bool
     */
    abstract protected function doContains(string $id): bool;

    /**
     * Save a cache record directly. This method is implemented by the cache
     * drivers and used in \Doctrine1\Cache\Driver::save()
     *
     * @param  string    $id       cache id
     * @param  string    $data     data to cache
     * @param  int|null $lifeTime if != null, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @return boolean true if no problem
     */
    abstract protected function doSave(string $id, $data, ?int $lifeTime = null): bool;

    /**
     * Remove a cache record directly. This method is implemented by the cache
     * drivers and used in \Doctrine1\Cache\Driver::delete()
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    abstract protected function doDelete(string $id): bool;

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    abstract protected function getCacheKeys(): array;
}
