<?php

class Doctrine_Adapter_Mock extends PDO
{
    /**
     * Name of the dbms to mock
     *
     * @var string
     */
    private string $_name;

    /**
     * Array of queries executed through this instance of the mock adapter
     *
     * @var array $_queries
     */
    private $_queries = [];

    /**
     * Array of exceptions thrown
     *
     * @var array $_exception
     */
    private $_exception = [];

    /**
     * Bool true/false variable for whether or not the last insert failed
     *
     * @var boolean $_lastInsertIdFail
     */
    private $_lastInsertIdFail = false;

    /**
     * Doctrine mock adapter constructor
     *
     * <code>
     * $conn = new Doctrine_Adapter_Mock('mysql');
     * </code>
     *
     * @param  string $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->_name = $name;
    }

    /**
     * Get the name of the dbms used in this instance of the mock adapter
     *
     * @return string $name Name of the dbms
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Pop the last executed query from the array of executed queries and return it
     *
     * @return string $sql Last executed sql string
     */
    public function pop()
    {
        return array_pop($this->_queries);
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
        $this->_exception = [$name, $message, $code];
    }

    public function prepare(string $query, array $options = [])
    {
        $mock              = new Doctrine_Adapter_Statement_Mock($this);
        $mock->queryString = $query;
        return $mock;
    }

    public function addQuery(string $query): void
    {
        $this->_queries[] = $query;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs)
    {
        $this->_queries[] = $query;

        $e = $this->_exception;

        if (!empty($e)) {
            $name = $e[0];

            $this->_exception = [];

            /** @var Exception $exception */
            $exception = new $name($e[1], $e[2]);

            throw $exception;
        }

        $stmt = new Doctrine_Adapter_Statement_Mock($this);
        $stmt->queryString = $query;

        return $stmt;
    }

    public function getAll()
    {
        return $this->_queries;
    }

    public function quote(string $string, int $type = PDO::PARAM_STR)
    {
        return "'" . addslashes($input) . "'";
    }

    public function exec($statement): int
    {
        $this->_queries[] = $statement;

        $e = $this->_exception;

        if (!empty($e)) {
            $name = $e[0];

            $this->_exception = [];

            /** @var Exception $exception */
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
            $this->_lastInsertIdFail = true;
        } else {
            $this->_lastInsertIdFail = false;
        }
    }

    public function lastInsertId(?string $name = null): string
    {
        $this->_queries[] = 'LAST_INSERT_ID()';
        if ($this->_lastInsertIdFail) {
            return '';
        } else {
            return '1';
        }
    }

    public function count(): int
    {
        return count($this->_queries);
    }

    public function beginTransaction(): bool
    {
        $this->_queries[] = 'BEGIN TRANSACTION';

        return true;
    }

    public function commit(): bool
    {
        $this->_queries[] = 'COMMIT';

        return true;
    }

    public function rollBack(): bool
    {
        $this->_queries[] = 'ROLLBACK';

        return true;
    }

    public function getAttribute(int $attribute): mixed
    {
        if ($attribute == Doctrine_Core::ATTR_DRIVER_NAME) {
            return strtolower($this->_name);
        }

        return null;
    }

    public function errorCode(): int
    {
        return 0;
    }

    public function errorInfo(): string
    {
        return '';
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
     * @return void
     */
    public function sqliteCreateFunction()
    {
    }
}
