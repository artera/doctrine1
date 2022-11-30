<?php

namespace Doctrine1;

class View
{
    /**
     * SQL DROP constant
     */
    const DROP = 'DROP VIEW %s';

    /**
     * SQL CREATE constant
     */
    const CREATE = 'CREATE VIEW %s AS %s';

    /**
     * SQL SELECT constant
     */
    const SELECT = 'SELECT * FROM %s';

    /**
     * @var string $name                the name of the view
     */
    protected $name;

    /**
     * @var Query $query       the DQL query object this view is hooked into
     */
    protected $query;

    protected Connection $connection;

    /**
     * The view dql string
     */
    protected string $dql;

    /**
     * The view sql string
     */
    protected ?string $sql;

    /**
     * @param Query $query
     * @param string $viewName
     */
    public function __construct(Query $query, string $viewName)
    {
        $this->name = $viewName;
        $this->query = $query;
        $this->query->setView($this);
        $this->connection = $query->getConnection();
        $this->dql = $query->getDql();
        $this->sql = $query->getSqlQuery();
    }

    /**
     * returns the associated query object
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * returns the name of this view
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * returns the connection object
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @throws View\Exception
     */
    public function create(): void
    {
        $sql = sprintf(self::CREATE, $this->name, $this->query->getSqlQuery());
        try {
            $this->connection->execute($sql, $this->query->getFlattenedParams());
        } catch (Exception $e) {
            throw new View\Exception($e->__toString());
        }
    }

    /**
     * drops this view from the database
     *
     * @throws View\Exception
     * @return void
     */
    public function drop(): void
    {
        try {
            $this->connection->execute(sprintf(self::DROP, $this->name));
        } catch (Exception $e) {
            throw new View\Exception($e->__toString());
        }
    }

    /**
     * returns a collection of Record objects
     * @phpstan-return array|bool|Collection<Record>|Collection\OnDemand<Record>|float|int|string
     */
    public function execute(): array|bool|Collection|Collection\OnDemand|float|int|string
    {
        return $this->query->execute();
    }

    /**
     * returns the select sql for this view
     */
    public function getSelectSql(): string
    {
        return sprintf(self::SELECT, $this->name);
    }

    /**
     * Get the view sql string
     */
    public function getViewSql(): ?string
    {
        return $this->sql;
    }

    /**
     * Get the view dql string
     */
    public function getViewDql(): string
    {
        return $this->dql;
    }
}
