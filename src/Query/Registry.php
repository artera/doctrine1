<?php

namespace Doctrine1\Query;

class Registry
{
    protected array $queries = [];

    public function add(string $key, \Doctrine1\Query|string $query): void
    {
        if ($query instanceof \Doctrine1\Query) {
            $query = clone $query;
        }

        if (strpos($key, '/') === false) {
            $this->queries[$key] = $query;
        } else {
            // namespace found

            $e = explode('/', $key);

            $this->queries[$e[0]][$e[1]] = $query;
        }
    }

    public function get(string $key, ?string $namespace = null): \Doctrine1\Query
    {
        if (isset($namespace)) {
            if (!isset($this->queries[$namespace][$key])) {
                throw new \Doctrine1\Query\Registry\Exception('A query with the name ' . $namespace . '/' . $key . ' does not exist.');
            }
            $query = $this->queries[$namespace][$key];
        } else {
            if (!isset($this->queries[$key])) {
                throw new \Doctrine1\Query\Registry\Exception('A query with the name ' . $key . ' does not exist.');
            }
            $query = $this->queries[$key];
        }

        if (!($query instanceof \Doctrine1\Query)) {
            $query = \Doctrine1\Query::create()
                ->parseDqlQuery($query);
        }

        return clone $query;
    }

    public function has(string $key, ?string $namespace = null): bool
    {
        return isset($namespace)
            ? isset($this->queries[$namespace][$key])
            : isset($this->queries[$key]);
    }
}
