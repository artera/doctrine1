<?php

namespace Doctrine1\Adapter;

use PDO;

class Mock extends PDO
{
    /**
     * Name of the dbms to mock
     *
     * @var string
     */
    private string $name;

    /**
     * Array of queries executed through this instance of the mock adapter
     *
     * @var array $queries
     */
    private $queries = [];

    /**
     * Array of exceptions thrown
     *
     * @var array $exception
     */
    private $exception = [];

    /**
     * Bool true/false variable for whether or not the last insert failed
     *
     * @var boolean $lastInsertIdFail
     */
    private $lastInsertIdFail = false;

    /**
     * Doctrine mock adapter constructor
     *
     * <code>
     * $conn = new \Doctrine1\Adapter\Mock('mysql');
     * </code>
     *
     * @param  string $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the dbms used in this instance of the mock adapter
     *
     * @return string $name Name of the dbms
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Pop the last executed query from the array of executed queries and return it
     *
     * @return string $sql Last executed sql string
     */
    public function pop()
    {
        return array_pop($this->queries);
    }

    /**
     * Force an exception in to the array of exceptions
     *
     * @param  string  $name    Name of exception
     * @param  string  $message Message for the exception
     * @param  integer $code    Code of the exception
     * @return void
     */
    public function forceException($name, $message = '', $code = 0): void
    {
        $this->exception = [$name, $message, $code];
    }

    /** @return \Doctrine1\Adapter\Statement\Mock|null */
    #[\ReturnTypeWillChange]
    public function prepare(string $query, array $options = []): \Doctrine1\Adapter\Statement\Mock|null
    {
        $mock = new \Doctrine1\Adapter\Statement\Mock($this);
        $mock->queryString = $query;
        return $mock;
    }

    public function addQuery(string $query): void
    {
        $this->queries[] = $query;
    }

    /** @return \Doctrine1\Adapter\Statement\Mock */
    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs)
    {
        $this->queries[] = $query;

        $e = $this->exception;

        if (!empty($e)) {
            $name = $e[0];

            $this->exception = [];

            /** @var \Throwable $exception */
            $exception = new $name($e[1], $e[2]);

            throw $exception;
        }

        $stmt = new \Doctrine1\Adapter\Statement\Mock($this);
        $stmt->queryString = $query;

        return $stmt;
    }

    public function getAll(): array
    {
        return $this->queries;
    }

    #[\ReturnTypeWillChange]
    public function quote(string $string, int $type = PDO::PARAM_STR)
    {
        return "'" . addslashes($string) . "'";
    }

    public function exec($statement): int
    {
        $this->queries[] = $statement;

        $e = $this->exception;

        if (!empty($e)) {
            $name = $e[0];

            $this->exception = [];

            /** @var \Throwable $exception */
            $exception = new $name($e[1], $e[2]);

            throw $exception;
        }

        return 0;
    }

    /**
     * Force last insert to be failed
     *
     * @param  boolean $fail
     * @return void
     */
    public function forceLastInsertIdFail($fail = true): void
    {
        if ($fail) {
            $this->lastInsertIdFail = true;
        } else {
            $this->lastInsertIdFail = false;
        }
    }

    public function lastInsertId(?string $name = null): string
    {
        $this->queries[] = 'LAST_INSERT_ID()';
        if ($this->lastInsertIdFail) {
            return '';
        } else {
            return '1';
        }
    }

    public function count(): int
    {
        return count($this->queries);
    }

    public function beginTransaction(): bool
    {
        $this->queries[] = 'BEGIN TRANSACTION';

        return true;
    }

    public function commit(): bool
    {
        $this->queries[] = 'COMMIT';

        return true;
    }

    public function rollBack(): bool
    {
        $this->queries[] = 'ROLLBACK';

        return true;
    }

    public function getAttribute(int $attribute): mixed
    {
        if ($attribute == PDO::ATTR_DRIVER_NAME) {
            return strtolower($this->name);
        }

        return null;
    }

    public function errorCode(): ?string
    {
        return null;
    }

    public function errorInfo(): array
    {
        return [];
    }

    /**
     * @param  string|int $attribute
     * @param  mixed      $value
     * @return bool
     */
    public function setAttribute($attribute, $value): bool
    {
        return true;
    }

    /**
     * @param string $function_name
     * @param callable $callback
     * @param int $num_args
     * @param int $flags
     */
    public function sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0): bool
    {
        return true;
    }
}
