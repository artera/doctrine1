<?php

namespace Doctrine1\Cache;

class Db extends Driver
{
    /**
     * Configure Database cache driver. Specify instance of \Doctrine1\Connection
     * and tableName to store cache in
     *
     * @param array $options an array of options
     */
    public function __construct($options = [])
    {
        if (!isset($options['connection'])
            || !($options['connection'] instanceof \Doctrine1\Connection)
        ) {
            throw new Exception('Connection option not set.');
        }

        if (!isset($options['tableName'])
            || !is_string($options['tableName'])
        ) {
            throw new Exception('Table name option not set.');
        }

        parent::__construct($options);
    }

    /**
     * Get the connection object associated with this cache driver
     *
     * @return \Doctrine1\Connection $connection
     */
    public function getConnection()
    {
        return $this->options['connection'];
    }

    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param  string  $id                cache id
     * @param  boolean $testCacheValidity if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    protected function doFetch(string $id, bool $testCacheValidity = true)
    {
        $sql = "SELECT data, expire FROM {$this->options['tableName']} WHERE id = ?";

        if ($testCacheValidity) {
            $sql .= " AND (expire is null OR expire > '" . date('Y-m-d H:i:s') . "')";
        }

        /** @phpstan-var array<int, mixed>[] */
        $result = $this->getConnection()->execute($sql, [$id])->fetchAll(\PDO::FETCH_NUM);

        if (!isset($result[0])) {
            return false;
        }

        return unserialize($this->hex2bin($result[0][0]));
    }

    /**
     * Test if a cache record exists for the passed id
     *
     * @param  string $id cache id
     */
    protected function doContains(string $id): bool
    {
        $sql = "SELECT id, expire FROM {$this->options['tableName']} WHERE id = ?";
        $result = $this->getConnection()->fetchOne($sql, [$id]);
        return isset($result[0]);
    }

    protected function doSave(string $id, $data, ?int $lifeTime = null): bool
    {
        if ($this->contains($id)) {
            //record is in database, do update
            $sql = "UPDATE {$this->options['tableName']} SET data = ?, expire=?  WHERE id = ?";

            if ($lifeTime) {
                $expire = date('Y-m-d H:i:s', time() + $lifeTime);
            } else {
                $expire = null;
            }

            $params = [bin2hex(serialize($data)), $expire, $id];
        } else {
            //record is not in database, do insert
            $sql = "INSERT INTO {$this->options['tableName']} (id, data, expire) VALUES (?, ?, ?)";

            if ($lifeTime) {
                $expire = date('Y-m-d H:i:s', time() + $lifeTime);
            } else {
                $expire = null;
            }

            $params = [$id, bin2hex(serialize($data)), $expire];
        }

        return (bool) $this->getConnection()->exec($sql, $params);
    }

    /**
     * Remove a cache record directly. This method is implemented by the cache
     * drivers and used in \Doctrine1\Cache\Driver::delete()
     *
     * @param  string $id cache id
     */
    protected function doDelete(string $id): bool
    {
        $sql = "DELETE FROM {$this->options['tableName']} WHERE id = ?";
        return (bool) $this->getConnection()->exec($sql, [$id]);
    }

    /**
     * Create the cache table
     */
    public function createTable(): void
    {
        $name = $this->options['tableName'];

        $fields = [
            'id' => [
                'type' => 'string',
                'length' => 255
            ],
            'data' => [
                'type' => 'blob'
            ],
            'expire' => [
                'type' => 'timestamp'
            ]
        ];

        $options = [
            'primary' => ['id']
        ];

        $this->getConnection()->export->createTable($name, $fields, $options);
    }

    /**
     * Convert hex data to binary data. If passed data is not hex then
     * it is returned as is.
     *
     * @param  string $hex
     * @return string $binary
     */
    private function hex2bin(string $hex): string
    {
        if (!ctype_xdigit($hex)) {
            return $hex;
        }
        return pack('H*', $hex);
    }

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    protected function getCacheKeys(): array
    {
        $sql     = "SELECT id FROM {$this->options['tableName']}";
        $keys    = [];
        /** @phpstan-var array<int, mixed>[] */
        $results = $this->getConnection()->execute($sql)->fetchAll(\PDO::FETCH_NUM);
        for ($i = 0, $count = count($results); $i < $count; $i++) {
            $keys[] = $results[$i][0];
        }
        return $keys;
    }
}
