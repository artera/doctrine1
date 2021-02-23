<?php

class Doctrine_Query_Registry
{
    protected array $_queries = [];

    public function add(string $key, Doctrine_Query|string $query): void
    {
        if ($query instanceof Doctrine_Query) {
            $query = clone $query;
        }

        if (strpos($key, '/') === false) {
            $this->_queries[$key] = $query;
        } else {
            // namespace found

            $e = explode('/', $key);

            $this->_queries[$e[0]][$e[1]] = $query;
        }
    }

    public function get(string $key, ?string $namespace = null): Doctrine_Query
    {
        if (isset($namespace)) {
            if (!isset($this->_queries[$namespace][$key])) {
                throw new Doctrine_Query_Registry_Exception('A query with the name ' . $namespace . '/' . $key . ' does not exist.');
            }
            $query = $this->_queries[$namespace][$key];
        } else {
            if (!isset($this->_queries[$key])) {
                throw new Doctrine_Query_Registry_Exception('A query with the name ' . $key . ' does not exist.');
            }
            $query = $this->_queries[$key];
        }

        if (!($query instanceof Doctrine_Query)) {
            $query = Doctrine_Query::create()
                ->parseDqlQuery($query);
        }

        return clone $query;
    }

    public function has(string $key, ?string $namespace = null): bool
    {
        return isset($namespace)
            ? isset($this->_queries[$namespace][$key])
            : isset($this->_queries[$key]);
    }
}
