<?php

class Doctrine_View
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
     * @var string $_name                the name of the view
     */
    protected $_name;

    /**
     * @var Doctrine_Query $_query       the DQL query object this view is hooked into
     */
    protected $_query;

    protected Doctrine_Connection $connection;

    /**
     * The view dql string
     */
    protected string $_dql;

    /**
     * The view sql string
     */
    protected ?string $_sql;

    /**
     * @param Doctrine_Query $query
     * @param string $viewName
     */
    public function __construct(Doctrine_Query $query, string $viewName)
    {
        $this->_name = $viewName;
        $this->_query = $query;
        $this->_query->setView($this);
        $this->connection = $query->getConnection();
        $this->_dql = $query->getDql();
        $this->_sql = $query->getSqlQuery();
    }

    /**
     * returns the associated query object
     */
    public function getQuery(): Doctrine_Query
    {
        return $this->_query;
    }

    /**
     * returns the name of this view
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * returns the connection object
     */
    public function getConnection(): Doctrine_Connection
    {
        return $this->connection;
    }

    /**
     * @throws Doctrine_View_Exception
     */
    public function create(): void
    {
        $sql = sprintf(self::CREATE, $this->_name, $this->_query->getSqlQuery());
        try {
            $this->connection->execute($sql, $this->_query->getFlattenedParams());
        } catch (Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }

    /**
     * drops this view from the database
     *
     * @throws Doctrine_View_Exception
     * @return void
     */
    public function drop(): void
    {
        try {
            $this->connection->execute(sprintf(self::DROP, $this->_name));
        } catch (Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }

    /**
     * returns a collection of Doctrine_Record objects
     * @phpstan-return array|bool|Doctrine_Collection<Doctrine_Record>|Doctrine_Collection_OnDemand<Doctrine_Record>|float|int|string
     */
    public function execute(): array|bool|Doctrine_Collection|Doctrine_Collection_OnDemand|float|int|string
    {
        return $this->_query->execute();
    }

    /**
     * returns the select sql for this view
     */
    public function getSelectSql(): string
    {
        return sprintf(self::SELECT, $this->_name);
    }

    /**
     * Get the view sql string
     */
    public function getViewSql(): ?string
    {
        return $this->_sql;
    }

    /**
     * Get the view dql string
     */
    public function getViewDql(): string
    {
        return $this->_dql;
    }
}
