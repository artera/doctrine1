<?php

/**
 * @mixin PDOStatement
 */
class Doctrine_Connection_Statement
{
    protected Doctrine_Connection $connection;
    protected PDOStatement $statement;

    public function __construct(Doctrine_Connection $connection, PDOStatement $statement)
    {
        $this->connection = $connection;
        $this->statement = $statement;
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->statement->$method(...$arguments);
    }

    public function getConnection(): Doctrine_Connection
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

    public function execute(array $input_parameters = null): bool
    {
        try {
            $event = new Doctrine_Event($this, Doctrine_Event::STMT_EXECUTE, $this->getQuery(), $input_parameters ?? []);
            $this->connection->getListener()->preStmtExecute($event);

            if ($input_parameters) {
                if ($this->connection->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_EMPTY_TO_NULL) {
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
        } catch (PDOException|Doctrine_Adapter_Exception $e) {
            $this->connection->rethrowException($e, $this);
        }
    }

    public function fetch(int $fetch_style = PDO::FETCH_BOTH, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0): mixed
    {
        $event = new Doctrine_Event($this, Doctrine_Event::STMT_FETCH, $this->getQuery());

        $event->fetchMode         = $fetch_style;
        $event->cursorOrientation = $cursor_orientation;
        $event->cursorOffset      = $cursor_offset;

        $data = $this->connection->getListener()->preFetch($event);
        $data = $this->statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        $this->connection->getListener()->postFetch($event);

        return $data;
    }

    public function fetchAll(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, mixed $fetch_argument = null, array $ctor_args = []): array
    {
        $event              = new Doctrine_Event($this, Doctrine_Event::STMT_FETCHALL, $this->getQuery());
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
}
