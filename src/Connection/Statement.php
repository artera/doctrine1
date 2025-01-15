<?php

namespace Doctrine1\Connection;

use BackedEnum;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @mixin PDOStatement
 */
class Statement
{
    protected \Doctrine1\Connection $connection;
    protected PDOStatement $statement;
    /** @phpstan-var array<string, string> */
    protected array $bindAliases;

    /** @phpstan-param array<string, string> $bindAliases */
    public function __construct(\Doctrine1\Connection $connection, PDOStatement $statement, array $bindAliases = [])
    {
        $this->connection = $connection;
        $this->statement = $statement;
        $this->bindAliases = $bindAliases;
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->statement->$method(...$arguments);
    }

    public function getConnection(): \Doctrine1\Connection
    {
        return $this->connection;
    }

    public function getStatement(): PDOStatement
    {
        return $this->statement;
    }

    public function getQuery(): string
    {
        return $this->statement->queryString;
    }

    public function setBindAliases(array $bindAliases): void
    {
        $this->bindAliases = $bindAliases;
    }

    public function execute(?array $input_parameters = null): bool
    {
        $input_parameters = $this->filterParams($this->statement->queryString, $input_parameters ?? [], $this->bindAliases);

        try {
            $event = new \Doctrine1\Event($this, \Doctrine1\Event::STMT_EXECUTE, $this->getQuery(), $input_parameters);
            $this->connection->getListener()->preStmtExecute($event);

            if ($input_parameters) {
                if ($this->connection->getPortability() & \Doctrine1\Core::PORTABILITY_EMPTY_TO_NULL) {
                    foreach ($input_parameters as $key => $value) {
                        if ($value === '') {
                            $input_parameters[$key] = null;
                        }
                    }
                }

                $pos = 0;
                foreach ($input_parameters as $key => $value) {
                    $pos++;
                    $param = is_numeric($key) ? $pos : $key;
                    $this->statement->bindParam($param, $input_parameters[$key], is_resource($value) ? PDO::PARAM_LOB : PDO::PARAM_STR);
                }
            }

            $result = $this->statement->execute();

            $this->connection->incrementQueryCount();
            $this->connection->getListener()->postStmtExecute($event);

            return $result;
        } catch (PDOException $e) {
            $this->connection->rethrowException($e, $this, $this->getQuery());
        }
    }

    public function fetch(int $fetch_style = PDO::FETCH_BOTH, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0): mixed
    {
        $event = new \Doctrine1\Event\Fetch($this, \Doctrine1\Event::STMT_FETCH, $this->getQuery());

        $event->fetchMode         = $fetch_style;
        $event->cursorOrientation = $cursor_orientation;
        $event->cursorOffset      = $cursor_offset;

        $this->connection->getListener()->preFetch($event);
        $data = $this->statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        $this->connection->getListener()->postFetch($event);

        return $data;
    }

    public function fetchAll(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, mixed $fetch_argument = null, array $ctor_args = []): array
    {
        $event              = new \Doctrine1\Event\Fetch($this, \Doctrine1\Event::STMT_FETCHALL, $this->getQuery());
        $event->fetchMode   = $fetch_style;
        $event->columnIndex = $fetch_argument;
        $data               = [];

        $this->connection->getListener()->preFetchAll($event);

        /** @var array */
        $data = $this->statement->fetchAll(...func_get_args());

        $event->data = $data;
        $this->connection->getListener()->postFetchAll($event);

        return $data;
    }

    /**
     * Filter query parameters that are not present in the query string
     * @param string $query
     * @param (string|BackedEnum)[] $params
     * @param array<string, string> $aliases
     * @return mixed[]
     */
    public function filterParams(string $query, array $params = [], iterable $aliases = []): array
    {
        foreach ($params as &$v) {
            if ($v instanceof BackedEnum) {
                $v = $v->value;
            }
        }

        if (empty($params) || strpos($query, '?') !== false) {
            return $params;
        }

        if (preg_match_all('/:\w+/', $query, $m)) {
            $new_params = [];
            foreach ($m[0] as $var) {
                if (array_key_exists($var, $params)) {
                    $new_params[$var] = $params[$var];
                } else {
                    $vark = substr($var, 1);
                    if (array_key_exists($vark, $params)) {
                        $new_params[$var] = $params[$vark];
                    }
                }
            }
            $params = $new_params;
        }

        foreach ($aliases as $alias => $var) {
            if (isset($params[$var])) {
                $params[$alias] = $params[$var];
            }
        }

        return $params;
    }
}
