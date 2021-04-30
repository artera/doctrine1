<?php

/**
 * @template Record of Doctrine_Record
 * @template Type of Doctrine_Query_Type
 */
abstract class Doctrine_Query_Abstract
{
    /**
     * A query object is in CLEAN state when it has NO unparsed/unprocessed DQL parts.
     */
    const STATE_CLEAN = 1;

    /**
     * A query object is in state DIRTY when it has DQL parts that have not yet been
     * parsed/processed.
     */
    const STATE_DIRTY = 2;

    /**
     * A query is in DIRECT state when ... ?
     */
    const STATE_DIRECT = 3;

    /**
     * A query object is on LOCKED state when ... ?
     */
    const STATE_LOCKED = 4;

    /**
     * @var array<string, string> Table alias map. Keys are SQL aliases and values DQL aliases.
     */
    protected array $tableAliasMap = [];

    /**
     * The view object used by this query, if any.
     */
    protected ?Doctrine_View $view = null;

    /**
     * The current state of this query.
     */
    protected int $state = Doctrine_Query::STATE_CLEAN;

    /**
     * @var array<string, mixed[]> $params The parameters of this query.
     */
    protected array $params = [
        'exec'   => [],
        'join'   => [],
        'where'  => [],
        'set'    => [],
        'having' => [],
    ];

    /**
     * @var mixed[] $execParams The parameters passed to connection statement
     */
    protected array $execParams = [];

    /* Caching properties */
    /**
     * @var Doctrine_Cache_Interface|true|null The cache driver used for caching result sets.
     */
    protected Doctrine_Cache_Interface|bool|null $resultCache = null;

    /**
     * Key to use for result cache entry in the cache driver
     */
    protected ?string $resultCacheHash = null;

    /**
     * @var boolean $expireResultCache  A boolean value that indicates whether or not
     *                                   expire the result cache.
     */
    protected bool $expireResultCache = false;

    protected ?int $resultCacheTTL = null;

    /**
     * The cache driver used for caching queries.
     */
    protected Doctrine_Cache_Interface|bool|null $queryCache = null;

    protected bool $expireQueryCache = false;

    protected ?int $queryCacheTTL = null;

    /**
     * The connection used by this query object.
     */
    protected Doctrine_Connection $connection;

    /**
     * Whether or not a connection was passed to this query object to use
     */
    protected bool $passedConn = false;

    /**
     * whether or not this query object is a subquery of another query object
     */
    protected bool $isSubquery = false;

    /**
     * @var array<string,mixed> $sqlParts  The SQL query string parts. Filled during the DQL parsing process.
     */
    protected array $sqlParts = [
        'select'    => [],
        'distinct'  => false,
        'forUpdate' => false,
        'from'      => [],
        'set'       => [],
        'join'      => [],
        'where'     => [],
        'groupby'   => [],
        'having'    => [],
        'orderby'   => [],
        'limit'     => false,
        'offset'    => false,
    ];

    /**
     * @var array<string,mixed> $dqlParts    an array containing all DQL query parts; @see Doctrine_Query::getDqlPart()
     */
    protected array $dqlParts = [
        'from'      => [],
        'select'    => [],
        'forUpdate' => false,
        'set'       => [],
        'join'      => [],
        'where'     => [],
        'groupby'   => [],
        'having'    => [],
        'orderby'   => [],
        'limit'     => [],
        'offset'    => [],
    ];


    /**
     * @phpstan-var array<string, array{table: Doctrine_Table, map?: ?string, parent?: string, relation?: Doctrine_Relation, ref?: bool, agg?: array<string, string>}>
     * @var array<string,mixed> $queryComponents   Two dimensional array containing the components of this query,
     *                                informations about their relations and other related information.
     *                                The components are constructed during query parsing.
     *
     *      Keys are component aliases and values the following:
     *
     *          table               table object associated with given alias
     *
     *          relation            the relation object owned by the parent
     *
     *          parent              the alias of the parent
     *
     *          agg                 the aggregates of this component
     *
     *          map                 the name of the column / aggregate value this
     *                              component is mapped to a collection
     */
    protected array $queryComponents = [];

    /**
     * Stores the root DQL alias
     *
     * @var string
     */
    protected string $rootAlias = '';

    protected Doctrine_Query_Type $type;

    /**
     * The hydrator object used to hydrate query results.
     */
    protected Doctrine_Hydrator|Doctrine_Hydrator_Abstract $hydrator;

    /**
     * The tokenizer that is used during the query parsing process.
     */
    protected Doctrine_Query_Tokenizer $tokenizer;

    /**
     * The parser that is used for query parsing.
     */
    protected Doctrine_Query_Parser $parser;

    /**
     * @var array $tableAliasSeeds         A simple array keys representing table aliases and values
     *                                      table alias seeds. The seeds are used for generating short table
     *                                      aliases.
     */
    protected array $tableAliasSeeds = [];

    protected array $options = [
        'hydrationMode' => Doctrine_Core::HYDRATE_RECORD
    ];

    protected bool $isLimitSubqueryUsed = false;

    protected array $components;

    /**
     * whether or not the preQuery process has been executed
     */
    protected bool $preQueried = false;

    /**
     * Fix for http://www.doctrine-project.org/jira/browse/DC-701
     *
     * @var bool Boolean variable for whether the limitSubquery method of accessing tables via a many relationship should be used.
     */
    protected bool $disableLimitSubquery = false;

    /**
     * an array containing pending joins
     */
    protected array $pendingJoinConditions = [];

    /**
     * an array of parser objects, each DQL query part has its own parser
     */
    protected array $parsers = [];

    /**
     * @param Doctrine_Connection             $connection The connection object the query will use.
     * @param Doctrine_Hydrator_Abstract|null $hydrator   The hydrator that will be used for generating result sets.
     */
    public function __construct(
        ?Doctrine_Connection $connection = null,
        ?Doctrine_Hydrator_Abstract $hydrator = null
    ) {
        $this->type = Doctrine_Query_Type::SELECT();
        $this->passedConn = $connection !== null;

        if ($hydrator === null) {
            $hydrator = new Doctrine_Hydrator();
        }
        $this->connection = $connection ?? Doctrine_Manager::getInstance()->getCurrentConnection();
        $this->hydrator       = $hydrator;
        $this->tokenizer      = new Doctrine_Query_Tokenizer();
        $this->resultCacheTTL = $this->connection->getAttribute(Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN);
        $this->queryCacheTTL  = $this->connection->getAttribute(Doctrine_Core::ATTR_QUERY_CACHE_LIFESPAN);
    }

    /**
     * Set the connection this query object should use
     */
    public function setConnection(Doctrine_Connection $connection): void
    {
        $this->passedConn = true;
        $this->connection = $connection;
    }

    /**
     * setOption
     *
     * @param  string $name  option name
     * @param  string $value option value
     * @return void
     */
    public function setOption($name, $value)
    {
        if (!isset($this->options[$name])) {
            throw new Doctrine_Query_Exception('Unknown option ' . $name);
        }
        $this->options[$name] = $value;
    }

    /**
     * hasSqlTableAlias
     * whether or not this object has given tableAlias
     *
     * @param  string $sqlTableAlias the table alias to be checked
     * @return boolean              true if this object has given alias, otherwise false
     */
    public function hasSqlTableAlias($sqlTableAlias)
    {
        return (isset($this->tableAliasMap[$sqlTableAlias]));
    }

    /**
     * getTableAliasMap
     * returns all table aliases
     *
     * @return array<string,string>        table aliases as an array
     */
    public function getTableAliasMap()
    {
        return $this->tableAliasMap;
    }

    /**
     * getDql
     * returns the DQL query that is represented by this query object.
     *
     * the query is built from $dqlParts
     *
     * @return string   the DQL query
     */
    public function getDql()
    {
        $q = '';
        if ($this->type->isSelect()) {
            $q .= (!empty($this->dqlParts['select'])) ? 'SELECT ' . implode(', ', $this->dqlParts['select']) : '';
            $q .= (!empty($this->dqlParts['from'])) ? ' FROM ' . implode(' ', $this->dqlParts['from']) : '';
        } elseif ($this->type->isDelete()) {
            $q .= 'DELETE';
            $q .= (!empty($this->dqlParts['from'])) ? ' FROM ' . implode(' ', $this->dqlParts['from']) : '';
        } elseif ($this->type->isUpdate()) {
            $q .= 'UPDATE ';
            $q .= (!empty($this->dqlParts['from'])) ? implode(' ', $this->dqlParts['from']) : '';
            $q .= (!empty($this->dqlParts['set'])) ? ' SET ' . implode(' ', $this->dqlParts['set']) : '';
        }
        $q .= (!empty($this->dqlParts['where'])) ? ' WHERE ' . implode(' ', $this->dqlParts['where']) : '';
        $q .= (!empty($this->dqlParts['groupby'])) ? ' GROUP BY ' . implode(', ', $this->dqlParts['groupby']) : '';
        $q .= (!empty($this->dqlParts['having'])) ? ' HAVING ' . implode(' AND ', $this->dqlParts['having']) : '';
        $q .= (!empty($this->dqlParts['orderby'])) ? ' ORDER BY ' . implode(', ', $this->dqlParts['orderby']) : '';
        $q .= (!empty($this->dqlParts['limit'])) ? ' LIMIT ' . implode(' ', $this->dqlParts['limit']) : '';
        $q .= (!empty($this->dqlParts['offset'])) ? ' OFFSET ' . implode(' ', $this->dqlParts['offset']) : '';

        return $q;
    }

    /**
     * getSqlQueryPart
     * gets an SQL query part from the SQL query part array
     *
     * @param  string $part query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return mixed     this object
     */
    public function getSqlQueryPart($part)
    {
        if (!isset($this->sqlParts[$part])) {
            throw new Doctrine_Query_Exception('Unknown SQL query part ' . $part);
        }
        return $this->sqlParts[$part];
    }

    /**
     * setSqlQueryPart
     * sets an SQL query part in the SQL query part array
     *
     * @param  string          $name the name of the query part to be set
     * @param  string|string[] $part query part string
     * @throws Doctrine_Query_Exception   if trying to set unknown query part
     * @return $this     this object
     */
    public function setSqlQueryPart($name, $part)
    {
        if (!isset($this->sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }

        if ($name !== 'limit' && $name !== 'offset') {
            if (is_array($part)) {
                $this->sqlParts[$name] = $part;
            } else {
                $this->sqlParts[$name] = [$part];
            }
        } else {
            $this->sqlParts[$name] = $part;
        }

        return $this;
    }

    /**
     * addSqlQueryPart
     * adds an SQL query part to the SQL query part array
     *
     * @param  string          $name the name of the query part to be added
     * @param  string|string[] $part query part string
     * @throws Doctrine_Query_Exception   if trying to add unknown query part
     * @return $this     this object
     */
    public function addSqlQueryPart($name, $part)
    {
        if (!isset($this->sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }
        if (is_array($part)) {
            $this->sqlParts[$name] = array_merge($this->sqlParts[$name], $part);
        } else {
            $this->sqlParts[$name][] = $part;
        }
        return $this;
    }

    /**
     * removeSqlQueryPart
     * removes a query part from the query part array
     *
     * @param  string $name the name of the query part to be removed
     * @throws Doctrine_Query_Exception   if trying to remove unknown query part
     * @return $this     this object
     */
    public function removeSqlQueryPart($name)
    {
        if (!isset($this->sqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }

        if ($name == 'limit' || $name == 'offset' || $name == 'forUpdate') {
            $this->sqlParts[$name] = false;
        } else {
            $this->sqlParts[$name] = [];
        }

        return $this;
    }

    /**
     * removeDqlQueryPart
     * removes a dql query part from the dql query part array
     *
     * @param  string $name the name of the query part to be removed
     * @throws Doctrine_Query_Exception   if trying to remove unknown query part
     * @return $this     this object
     */
    public function removeDqlQueryPart($name)
    {
        if (!isset($this->dqlParts[$name])) {
            throw new Doctrine_Query_Exception('Unknown query part ' . $name);
        }

        if ($name == 'limit' || $name == 'offset') {
            $this->dqlParts[$name] = false;
        } else {
            $this->dqlParts[$name] = [];
        }

        return $this;
    }

    /**
     * Get raw array of parameters for query and all parts.
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get flattened array of parameters for query.
     * Used internally and used to pass flat array of params to the database.
     *
     * @param  mixed $params
     * @return array
     */
    public function getFlattenedParams($params = [])
    {
        return array_merge(
            (array) $params,
            (array) $this->params['exec'],
            $this->params['join'],
            $this->params['set'],
            $this->params['where'],
            $this->params['having']
        );
    }

    /**
     * getInternalParams
     *
     * @param  array $params
     * @return array
     */
    public function getInternalParams($params = [])
    {
        return array_merge($params, $this->execParams);
    }

    /**
     * setParams
     *
     * @param array $params
     *
     * @return void
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * getCountQueryParams
     * Retrieves the parameters for count query
     *
     * @param  array $params
     * @return array Parameters array
     */
    public function getCountQueryParams($params = [])
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        $this->params['exec'] = $params;

        $params = array_merge($this->params['join'], $this->params['where'], $this->params['having'], $this->params['exec']);

        $this->fixArrayParameterValues($params);

        return $this->execParams;
    }

    /**
     * @nodoc
     * @param  array $params
     * @return void
     */
    public function fixArrayParameterValues($params = [])
    {
        $i = 0;

        foreach ($params as $param) {
            if (is_array($param)) {
                $c = count($param);

                array_splice($params, $i, 1, $param);

                $i += $c;
            } else {
                $i++;
            }
        }

        $this->execParams = $params;
    }

    /**
     * setView
     * sets a database view this query object uses
     * this method should only be called internally by doctrine
     *
     * @param  Doctrine_View $view database view
     * @return void
     */
    public function setView(Doctrine_View $view)
    {
        $this->view = $view;
    }

    /**
     * getView
     * returns the view associated with this query object (if any)
     *
     * @return Doctrine_View|null        the view associated with this query object
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * limitSubqueryUsed
     *
     * @return boolean
     */
    public function isLimitSubqueryUsed()
    {
        return $this->isLimitSubqueryUsed;
    }

    /**
     * Returns the inheritance condition for the passed componentAlias
     * If no component alias is specified it defaults to the root component
     *
     * This function is used to append a SQL condition to models which have inheritance mapping
     * The condition is applied to the FROM component in the WHERE, but the condition is applied to
     * JOINS in the ON condition and not the WHERE
     *
     * @param  string $componentAlias
     * @return string|null $str  SQL condition string
     */
    public function getInheritanceCondition($componentAlias)
    {
        $map = $this->queryComponents[$componentAlias]['table']->inheritanceMap;

        // No inheritance map so lets just return
        if (empty($map)) {
            return null;
        }

        $tableAlias = $this->getSqlTableAlias($componentAlias);

        if ($this->type->isSelect()) {
            $tableAlias .= '.';
        } else {
            $tableAlias = '';
        }

        // Fix for 2015: loop through whole inheritanceMap to add all
        // keyFields for inheritance (and not only the first)
        $retVal = '';
        $count  = 0;

        foreach ($map as $field => $value) {
            if ($count++ > 0) {
                $retVal .= ' AND ';
            }

            $identifier = $this->connection->quoteIdentifier($tableAlias . $field);
            $retVal .= $identifier . ' = ' . $this->connection->quote($value);
        }

        return $retVal;
    }

    /**
     * getSqlTableAlias
     * some database need the identifier lengths to be < ~30 chars
     * hence Doctrine creates as short identifier aliases as possible
     *
     * this method is used for the creation of short table aliases, its also
     * smart enough to check if an alias already exists for given component (componentAlias)
     *
     * @param  string $componentAlias the alias for the query component to search table alias for
     * @param  string $tableName      the table name from which the table alias is being created
     * @return string                   the generated / fetched short alias
     */
    public function getSqlTableAlias($componentAlias, $tableName = null)
    {
        $alias = array_search($componentAlias, $this->tableAliasMap);

        if ($alias !== false) {
            return $alias;
        }

        if ($tableName === null) {
            throw new Doctrine_Query_Exception("Couldn't get short alias for " . $componentAlias);
        }

        return $this->generateSqlTableAlias($componentAlias, $tableName);
    }

    /**
     * generateNewSqlTableAlias
     * generates a new alias from given table alias
     *
     * @param  string $oldAlias table alias from which to generate the new alias from
     * @return string               the created table alias
     */
    public function generateNewSqlTableAlias($oldAlias)
    {
        if (isset($this->tableAliasMap[$oldAlias])) {
            // generate a new alias
            $name = substr($oldAlias, 0, 1);
            $i    = ((int) substr($oldAlias, 1));

            // Fix #1530: It was reaching unexistent seeds index
            if (!isset($this->tableAliasSeeds[$name])) {
                $this->tableAliasSeeds[$name] = 1;
            }

            $newIndex = ($this->tableAliasSeeds[$name] + (($i == 0) ? 1 : $i));

            return $name . $newIndex;
        }

        return $oldAlias;
    }

    /**
     * getSqlTableAliasSeed
     * returns the alias seed for given table alias
     *
     * @param  string $sqlTableAlias table alias that identifies the alias seed
     * @return integer              table alias seed
     */
    public function getSqlTableAliasSeed($sqlTableAlias)
    {
        if (!isset($this->tableAliasSeeds[$sqlTableAlias])) {
            return 0;
        }
        return $this->tableAliasSeeds[$sqlTableAlias];
    }

    /**
     * hasAliasDeclaration
     * whether or not this object has a declaration for given component alias
     *
     * @param  string $componentAlias the component alias the retrieve the declaration from
     * @return boolean
     */
    public function hasAliasDeclaration($componentAlias)
    {
        return isset($this->queryComponents[$componentAlias]);
    }

    /**
     * getQueryComponent
     * get the declaration for given component alias
     *
     * @param  string $componentAlias the component alias the retrieve the declaration from
     * @return array                    the alias declaration
     */
    public function getQueryComponent($componentAlias)
    {
        if (!isset($this->queryComponents[$componentAlias])) {
            throw new Doctrine_Query_Exception('Unknown component alias ' . $componentAlias);
        }

        return $this->queryComponents[$componentAlias];
    }

    /**
     * copy aliases from another Hydrate object
     *
     * this method is needed by DQL subqueries which need the aliases
     * of the parent query
     *
     * @param Doctrine_Query_Abstract $query the query object from which the aliases are copied from
     * @phpstan-param Doctrine_Query_Abstract<Record, Doctrine_Query_Type> $query
     */
    public function copySubqueryInfo(Doctrine_Query_Abstract $query): void
    {
        $this->params          = &$query->params;
        $this->tableAliasMap   = &$query->tableAliasMap;
        $this->queryComponents = &$query->queryComponents;
        $this->tableAliasSeeds = $query->tableAliasSeeds;
    }

    /**
     * getRootAlias
     * returns the alias of the root component
     *
     * @return string
     */
    public function getRootAlias()
    {
        if (!$this->queryComponents) {
            $this->getSqlQuery([], false);
        }
        return $this->rootAlias;
    }

    /**
     * getRootDeclaration
     * returns the root declaration
     *
     * @return array
     */
    public function getRootDeclaration()
    {
        $map = $this->queryComponents[$this->rootAlias];
        return $map;
    }

    /**
     * getRoot
     * returns the root component for this object
     *
     * @return Doctrine_Table       root components table
     */
    public function getRoot()
    {
        $map = $this->queryComponents[$this->rootAlias];

        if (!isset($map['table'])) {
            throw new Doctrine_Query_Exception('Root component not initialized.');
        }

        return $map['table'];
    }

    /**
     * generateSqlTableAlias
     * generates a table alias from given table name and associates
     * it with given component alias
     *
     * @param  string $componentAlias the component alias to be associated with generated table alias
     * @param  string $tableName      the table name from which to generate the table alias
     * @return string                   the generated table alias
     */
    public function generateSqlTableAlias($componentAlias, $tableName)
    {
        preg_match('/([^_|\d])/', $tableName, $matches);
        $char = strtolower($matches[0]);

        $alias = $char;

        if (!isset($this->tableAliasSeeds[$alias])) {
            $this->tableAliasSeeds[$alias] = 1;
        }

        while (isset($this->tableAliasMap[$alias])) {
            if (!isset($this->tableAliasSeeds[$alias])) {
                $this->tableAliasSeeds[$alias] = 1;
            }
            $alias = $char . ++$this->tableAliasSeeds[$alias];
        }

        $this->tableAliasMap[$alias] = $componentAlias;

        return $alias;
    }

    /**
     * getComponentAlias
     * get component alias associated with given table alias
     *
     * @param  string $sqlTableAlias the SQL table alias that identifies the component alias
     * @return string               component alias
     */
    public function getComponentAlias($sqlTableAlias)
    {
        $sqlTableAlias = trim($sqlTableAlias, '[]`"');
        if (!isset($this->tableAliasMap[$sqlTableAlias])) {
            throw new Doctrine_Query_Exception('Unknown table alias ' . $sqlTableAlias);
        }
        return $this->tableAliasMap[$sqlTableAlias];
    }

    /**
     * calculateQueryCacheHash
     * calculate hash key for query cache
     *
     * @param  mixed $params
     * @return string    the hash
     */
    public function calculateQueryCacheHash($params = [])
    {
        $paramString = '';
        $dql         = $this->getDql();
        $params      = $this->getFlattenedParams($params);
        foreach ($params as $array) {
            $count = is_array($array) ? count($array) : 1;
            $paramString .= '|' . $count;
        }

        $hash = md5($dql . var_export($this->pendingJoinConditions, true) . $paramString . 'DOCTRINE_QUERY_CACHE_SALT');
        return $hash;
    }

    /**
     * calculateResultCacheHash
     * calculate hash key for result cache
     *
     * @param  array $params
     * @return string    the hash
     */
    public function calculateResultCacheHash($params = [])
    {
        $dql    = $this->getDql();
        $conn   = $this->getConnection();
        $params = $this->getFlattenedParams($params);
        $hash   = md5($this->hydrator->getHydrationMode() . $conn->getName() . $conn->getOption('dsn') . $dql . var_export($this->pendingJoinConditions, true) . var_export($params, true));
        return $hash;
    }

    /**
     * Get the result cache hash/key. Returns key set with useResultCache()
     * or generates a unique key from the query automatically.
     *
     * @param  array $params
     * @return string $hash
     */
    public function getResultCacheHash($params = [])
    {
        if ($this->resultCacheHash) {
            return $this->resultCacheHash;
        } else {
            return $this->calculateResultCacheHash($params);
        }
    }

    protected function doExecute(array $params): Doctrine_Connection_Statement|int
    {
        // Apply boolean conversion in DQL params
        $params = (array) $this->connection->convertBooleans($params);

        foreach ($this->params as $k => $v) {
            $this->params[$k] = $this->connection->convertBooleans($v); // @phpstan-ignore-line
        }

        $dqlParams = $this->getFlattenedParams($params);

        // Check if we're not using a Doctrine_View
        if (!$this->view) {
            if ($this->queryCache !== false && ($this->queryCache || $this->connection->getAttribute(Doctrine_Core::ATTR_QUERY_CACHE))) {
                $queryCacheDriver = $this->getQueryCacheDriver();
                $hash             = $this->calculateQueryCacheHash($params);
                $cached           = $queryCacheDriver->fetch($hash);

                // If we have a cached query...
                if ($cached) {
                    // Rebuild query from cache
                    $query = $this->constructQueryFromCache($cached);
                    assert(is_string($query));

                    // Assign building/execution specific params
                    $this->params['exec'] = $params;

                    // Initialize prepared parameters array
                    $this->execParams = $this->getFlattenedParams();

                    // Fix possible array parameter values in SQL params
                    $this->fixArrayParameterValues($this->getInternalParams());
                } else {
                    // Generate SQL or pick already processed one
                    $query = $this->getSqlQuery($params);

                    // Check again because getSqlQuery() above could have flipped the _queryCache flag
                    // if this query contains the limit sub query algorithm we don't need to cache it
                    if ($this->queryCache || $this->connection->getAttribute(Doctrine_Core::ATTR_QUERY_CACHE)) {
                        // Convert query into a serialized form
                        $serializedQuery = $this->getCachedForm($query);

                        // Save cached query
                        $queryCacheDriver->save($hash, $serializedQuery, $this->getQueryCacheLifeSpan());
                    }
                }
            } else {
                $query = $this->getSqlQuery($params);
            }
        } else {
            $query = $this->view->getSelectSql();
        }

        // Get prepared SQL params for execution
        $params = $this->getInternalParams();

        if (!$this->type->isSelect()) {
            return $this->connection->exec($query, $params);
        }

        $stmt = $this->connection->execute($query, $params);

        $this->params['exec'] = [];

        return $stmt;
    }

    /**
     * executes the query and populates the data set
     *
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract>|null $hydrationMode
     * @phpstan-return Doctrine_Collection<Record>|Doctrine_Collection_OnDemand<Record>|array|scalar
     */
    public function execute(array $params = [], int|string|null $hydrationMode = null): Doctrine_Collection|Doctrine_Collection_OnDemand|array|int|string|float|bool
    {
        try {
            // Clean any possible processed params
            $this->execParams = [];

            if (empty($this->dqlParts['from']) && empty($this->sqlParts['from'])) {
                throw new Doctrine_Query_Exception('You must have at least one component specified in your from.');
            }

            $dqlParams = $this->getFlattenedParams($params);

            $this->invokePreQuery($dqlParams);

            if ($hydrationMode !== null) {
                $this->hydrator->setHydrationMode($hydrationMode);
            }

            $hydrationMode = $this->hydrator->getHydrationMode();

            if ($this->resultCache && $this->type->isSelect()) {
                $cacheDriver = $this->getResultCacheDriver();
                $hash        = $this->getResultCacheHash($params);
                $cached      = ($this->expireResultCache) ? false : $cacheDriver->fetch($hash);

                if ($cached === false) {
                    // cache miss
                    $stmt = $this->doExecute($params);
                    assert($stmt instanceof Doctrine_Connection_Statement);
                    $this->hydrator->setQueryComponents($this->queryComponents);
                    $result = $this->hydrator->hydrateResultSet($stmt, $this->hydrator instanceof Doctrine_Hydrator ? $this->tableAliasMap : []);

                    $cached = $this->getCachedForm($result);
                    $cacheDriver->save($hash, $cached, $this->getResultCacheLifeSpan());

                    return $result;
                }

                return $this->constructQueryFromCache($cached);
            } else {
                $stmt = $this->doExecute($params);

                if (is_integer($stmt)) {
                    return $stmt;
                }

                $this->hydrator->setQueryComponents($this->queryComponents);
                if ($this->hydrator instanceof Doctrine_Hydrator) {
                    if ($this->type->isSelect() && $hydrationMode == Doctrine_Core::HYDRATE_ON_DEMAND) {
                        $hydrationDriver = $this->hydrator->getHydratorDriver($hydrationMode, $this->tableAliasMap);
                        /** @var Doctrine_Collection_OnDemand<Record> */
                        $result = new Doctrine_Collection_OnDemand($stmt, $hydrationDriver, $this->tableAliasMap);
                        return $result;
                    }
                    return $this->hydrator->hydrateResultSet($stmt, $this->tableAliasMap);
                }

                return $this->hydrator->hydrateResultSet($stmt);
            }
        } finally {
            if ($this->getConnection()->getAttribute(Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS)) {
                $this->free();
            }
        }
    }

    /**
     * Blank template method free(). Override to be used to free query object memory
     *
     * @return void
     */
    public function free()
    {
    }

    /**
     * Get the dql call back for this query
     *
     * @return array{callback:string,const:int}|null $callback
     */
    protected function getDqlCallback(): ?array
    {
        if (empty($this->dqlParts['from'])) {
            return null;
        }

        if ($this->type->isDelete()) {
            return [
                'callback' => 'preDqlDelete',
                'const'    => Doctrine_Event::RECORD_DQL_DELETE
            ];
        }

        if ($this->type->isUpdate()) {
            return [
                'callback' => 'preDqlUpdate',
                'const'    => Doctrine_Event::RECORD_DQL_UPDATE
            ];
        }

        if ($this->type->isSelect()) {
            return [
                'callback' => 'preDqlSelect',
                'const'    => Doctrine_Event::RECORD_DQL_SELECT
            ];
        }

        return null;
    }

    /**
     * Pre query method which invokes the pre*Query() methods on the model instance or any attached
     * record listeners
     *
     * @param  array $params
     * @return void
     */
    protected function invokePreQuery($params = [])
    {
        if (!$this->preQueried && $this->getConnection()->getAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS)) {
            $this->preQueried = true;

            $callback = $this->getDqlCallback();

            // if there is no callback for the query type, then we can return early
            if (!$callback) {
                return;
            }

            foreach ($this->getDqlCallbackComponents($params) as $alias => $component) {
                $table  = $component['table'];
                $record = $table->getRecordInstance();

                // Trigger preDql*() callback event
                $params = ['component' => $component, 'alias' => $alias];
                $event  = new Doctrine_Event($record, $callback['const'], $this, $params);

                $record->{$callback['callback']}($event);
                $table->getRecordListener()->{$callback['callback']}($event);
            }
        }

        // Invoke preQuery() hook on Doctrine_Query for child classes which implement this hook
        $this->preQuery();
    }

    /**
     * Returns an array of components to execute the query callbacks for
     *
     * @param  array $params
     * @return array $components
     */
    protected function getDqlCallbackComponents($params = [])
    {
        $componentsBefore = [];
        if ($this->isSubquery()) {
            $componentsBefore = $this->getQueryComponents();
        }

        $copy = clone $this;
        $copy->getSqlQuery($params, false);
        $componentsAfter = $copy->getQueryComponents();

        $this->rootAlias = $copy->getRootAlias();

        $copy->free();

        if ($componentsBefore !== $componentsAfter) {
            return static::arrayDiffAssocRecursive($componentsAfter, $componentsBefore);
        } else {
            return $componentsAfter;
        }
    }

    /**
     * Blank hook methods which can be implemented in Doctrine_Query child classes
     *
     * @return void
     */
    public function preQuery()
    {
    }

    /**
     * Constructs the query from the cached form.
     *
     * @param  string $cached The cached query, in a serialized form.
     * @return mixed  The custom component that was cached together with the essential
     *                query data. This can be either a result set (result caching)
     *                or an SQL query string (query caching).
     */
    protected function constructQueryFromCache($cached): mixed
    {
        $cached               = unserialize($cached);
        $this->tableAliasMap = $cached[2];
        $customComponent      = $cached[0];

        $queryComponents  = [];
        $cachedComponents = $cached[1];
        foreach ($cachedComponents as $alias => $components) {
            assert(is_string($alias));

            $component = [];

            $e = explode('.', $components['name']);
            if (count($e) === 1) {
                $manager = Doctrine_Manager::getInstance();
                if (!$this->passedConn && $manager->hasConnectionForComponent($e[0])) {
                    $this->connection = $manager->getConnectionForComponent($e[0]);
                }
                $component['table'] = $this->connection->getTable($e[0]);
            } else {
                /** @var array{table: Doctrine_Table} */
                $parentComponent = $queryComponents[$e[0]];
                $component['parent']   = $e[0];
                $component['relation'] = $parentComponent['table']->getRelation($e[1]);
                $component['table']    = $component['relation']->getTable();
            }
            if (isset($components['agg']) && is_array($components['agg'])) {
                /** @var array<string, string> */
                $agg = $components['agg'];
                $component['agg'] = $agg;
            }
            $component['map'] = (isset($components['map']) && is_string($components['map'])) ? $components['map'] : null;

            $queryComponents[$alias] = $component;
        }
        $this->queryComponents = $queryComponents;

        return $customComponent;
    }

    /**
     * getCachedForm
     * returns the cached form of this query for given resultSet
     *
     * @param  array|Doctrine_Collection|string $customComponent
     * @return string           serialized string representation of this query
     */
    public function getCachedForm($customComponent = null)
    {
        $componentInfo = [];

        foreach ($this->getQueryComponents() as $alias => $components) {
            if (!isset($components['parent'])) {
                $componentInfo[$alias]['name'] = $components['table']->getComponentName();
            } else {
                $componentInfo[$alias]['name'] = $components['parent'] . '.' . $components['relation']->getAlias();
            }
            if (isset($components['agg'])) {
                $componentInfo[$alias]['agg'] = $components['agg'];
            }
            if (isset($components['map'])) {
                $componentInfo[$alias]['map'] = $components['map'];
            }
        }

        if ($customComponent instanceof Doctrine_Collection) {
            foreach ($customComponent as $record) {
                $record->serializeReferences(true);
            }
        }

        return serialize([$customComponent, $componentInfo, $this->getTableAliasMap()]);
    }

    /**
     * Adds fields or aliased functions.
     *
     * This method adds fields or dbms functions to the SELECT query part.
     * <code>
     * $query->addSelect('COUNT(p.id) as num_phonenumbers');
     * </code>
     *
     * @param  string $select Query SELECT part
     * @return $this
     */
    public function addSelect($select)
    {
        return $this->addDqlQueryPart('select', $select, true);
    }

    /**
     * addSqlTableAlias
     * adds an SQL table alias and associates it a component alias
     *
     * @param  string $componentAlias the alias for the query component associated with given tableAlias
     * @param  string $sqlTableAlias  the table alias to be added
     * @return $this
     */
    public function addSqlTableAlias($sqlTableAlias, $componentAlias)
    {
        $this->tableAliasMap[$sqlTableAlias] = $componentAlias;
        return $this;
    }

    /**
     * addFrom
     * adds fields to the FROM part of the query
     *
     * @param  string $from Query FROM part
     * @return $this
     */
    public function addFrom($from)
    {
        return $this->addDqlQueryPart('from', $from, true);
    }

    /**
     * Alias for @see andWhere().
     *
     * @param  string            $where
     * @param  array|scalar|null $params
     * @return $this   this object
     */
    public function addWhere($where, $params = [])
    {
        return $this->andWhere($where, $params);
    }

    /**
     * Adds conditions to the WHERE part of the query.
     * <code>
     * $q->andWhere('u.birthDate > ?', '1975-01-01');
     * </code>
     *
     * @param  string            $where  Query WHERE part
     * @param  array|scalar|null $params An array of parameters or a simple scalar
     * @return $this
     */
    public function andWhere($where, $params = [])
    {
        if (is_array($params)) {
            $this->params['where'] = array_merge($this->params['where'], $params);
        } else {
            $this->params['where'][] = $params;
        }

        if ($this->hasDqlQueryPart('where')) {
            $this->addDqlQueryPart('where', 'AND', true);
        }

        return $this->addDqlQueryPart('where', $where, true);
    }

    /**
     * Adds conditions to the WHERE part of the query
     * <code>
     * $q->orWhere('u.role = ?', 'admin');
     * </code>
     *
     * @param  string            $where  Query WHERE part
     * @param  array|scalar|null $params An array of parameters or a simple scalar
     * @return $this
     */
    public function orWhere($where, $params = [])
    {
        if (is_array($params)) {
            $this->params['where'] = array_merge($this->params['where'], $params);
        } else {
            $this->params['where'][] = $params;
        }

        if ($this->hasDqlQueryPart('where')) {
            $this->addDqlQueryPart('where', 'OR', true);
        }

        return $this->addDqlQueryPart('where', $where, true);
    }

    /**
     * Adds IN condition to the query WHERE part. Alias to @see andWhereIn().
     *
     * @param  string       $expr   the operand of the IN
     * @param  array|scalar $params an array of parameters or a simple scalar
     * @param  boolean      $not    whether or not to use NOT in front of IN
     * @return $this
     */
    public function whereIn($expr, $params = [], $not = false)
    {
        return $this->andWhereIn($expr, $params, $not);
    }

    /**
     * Adds IN condition to the query WHERE part
     * <code>
     * $q->whereIn('u.id', array(10, 23, 44));
     * </code>
     *
     * @param  string       $expr   The operand of the IN
     * @param  array|scalar $params An array of parameters or a simple scalar
     * @param  boolean      $not    Whether or not to use NOT in front of IN. Defaults to false (simple IN clause)
     * @return $this   this object.
     */
    public function andWhereIn($expr, $params = [], $not = false)
    {
        if (is_array($params) && (count($params) == 0)) {
            // if there's no params, change WHERE x IN (), which is invalid SQL, to WHERE x IN (NULL)
            $params = [null];
        }

        if ($this->hasDqlQueryPart('where')) {
            $this->addDqlQueryPart('where', 'AND', true);
        }

        return $this->addDqlQueryPart('where', $this->processWhereIn($expr, $params, $not), true);
    }

    /**
     * Adds IN condition to the query WHERE part, appending it with an OR operator.
     * <code>
     * $q->orWhereIn('u.id', array(10, 23))
     *   ->orWhereIn('u.id', 44);
     * // will select all record with id equal to 10, 23 or 44
     * </code>
     *
     * @param  string       $expr   The operand of the IN
     * @param  array|scalar $params An array of parameters or a simple scalar
     * @param  boolean      $not    Whether or not to use NOT in front of IN
     * @return $this
     */
    public function orWhereIn($expr, $params = [], $not = false)
    {
        // if there's no params, return (else we'll get a WHERE IN (), invalid SQL)
        if (is_array($params) && (count($params) == 0)) {
            return $this;
        }

        if ($this->hasDqlQueryPart('where')) {
            $this->addDqlQueryPart('where', 'OR', true);
        }

        return $this->addDqlQueryPart('where', $this->processWhereIn($expr, $params, $not), true);
    }

    /**
     * @param  string       $expr
     * @param  array|scalar $params
     * @param  bool         $not
     * @return string
     */
    protected function processWhereIn($expr, $params = [], $not = false)
    {
        $params = (array) $params;

        // if there's no params, return (else we'll get a WHERE IN (), invalid SQL)
        if (is_array($params) && count($params) == 0) {
            throw new Doctrine_Query_Exception('You must pass at least one parameter when using an IN() condition.');
        }

        $a = [];
        foreach ($params as $k => $value) {
            if ($value instanceof Doctrine_Expression) {
                $value = $value->getSql();
                unset($params[$k]);
            } else {
                $value = '?';
            }
            $a[] = $value;
        }

        $this->params['where'] = array_merge($this->params['where'], $params);

        return $expr . ($not === true ? ' NOT' : '') . ' IN (' . implode(', ', $a) . ')';
    }

    /**
     * Adds NOT IN condition to the query WHERE part.
     * <code>
     * $q->whereNotIn('u.id', array(10, 20));
     * // will exclude users with id 10 and 20 from the select
     * </code>
     *
     * @param  string       $expr   the operand of the NOT IN
     * @param  array|scalar $params an array of parameters or a simple scalar
     * @return $this       this object
     */
    public function whereNotIn($expr, $params = [])
    {
        return $this->whereIn($expr, $params, true);
    }

    /**
     * Adds NOT IN condition to the query WHERE part
     * Alias for @see whereNotIn().
     *
     * @param  string       $expr   The operand of the NOT IN
     * @param  array|scalar $params An array of parameters or a simple scalar
     * @return $this
     */
    public function andWhereNotIn($expr, $params = [])
    {
        return $this->andWhereIn($expr, $params, true);
    }

    /**
     * Adds NOT IN condition to the query WHERE part
     *
     * @param  string       $expr   The operand of the NOT IN
     * @param  array|scalar $params An array of parameters or a simple scalar
     * @return $this
     */
    public function orWhereNotIn($expr, $params = [])
    {
        return $this->orWhereIn($expr, $params, true);
    }

    /**
     * Adds fields to the GROUP BY part of the query.
     * <code>
     * $q->groupBy('u.id');
     * </code>
     *
     * @param  string $groupby Query GROUP BY part
     * @return $this
     */
    public function addGroupBy($groupby)
    {
        return $this->addDqlQueryPart('groupby', $groupby, true);
    }

    /**
     * Adds conditions to the HAVING part of the query.
     *
     * This methods add HAVING clauses. These clauses are used to narrow the
     * results by operating on aggregated values.
     * <code>
     * $q->having('num_phonenumbers > ?', 1);
     * </code>
     *
     * @param  string       $having Query HAVING part
     * @param  array|scalar $params an array of parameters or a simple scalar
     * @return $this
     */
    public function addHaving($having, $params = [])
    {
        if (is_array($params)) {
            $this->params['having'] = array_merge($this->params['having'], $params);
        } else {
            $this->params['having'][] = $params;
        }
        return $this->addDqlQueryPart('having', $having, true);
    }

    /**
     * addOrderBy
     * adds fields to the ORDER BY part of the query
     *
     * @param  string $orderby Query ORDER BY part
     * @return $this
     */
    public function addOrderBy($orderby)
    {
        return $this->addDqlQueryPart('orderby', $orderby, true);
    }

    /**
     * sets the SELECT part of the query
     *
     * @param  string $select Query SELECT part
     * @return $this
     * @phpstan-return static<Record, Doctrine_Query_Type_Select>
     */
    public function select($select = null)
    {
        $this->type = Doctrine_Query_Type::SELECT();
        if ($select) {
            return $this->addDqlQueryPart('select', $select);
        } else {
            return $this;
        }
    }

    /**
     * distinct
     * Makes the query SELECT DISTINCT.
     * <code>
     * $q->distinct();
     * </code>
     *
     * @param  bool $flag Whether or not the SELECT is DISTINCT (default true).
     * @return $this
     */
    public function distinct($flag = true)
    {
        $this->sqlParts['distinct'] = (bool) $flag;
        return $this;
    }

    /**
     * forUpdate
     * Makes the query SELECT FOR UPDATE.
     *
     * @param  bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return $this
     */
    public function forUpdate($flag = true)
    {
        $this->sqlParts['forUpdate'] = (bool) $flag;
        return $this;
    }

    /**
     * delete
     * sets the query type to DELETE
     *
     * @param  string $from
     * @return $this
     * @phpstan-return static<Record, Doctrine_Query_Type_Delete>
     */
    public function delete($from = null)
    {
        $this->type = Doctrine_Query_Type::DELETE();
        if ($from != null) {
            return $this->addDqlQueryPart('from', $from);
        }
        return $this;
    }

    /**
     * update
     * sets the UPDATE part of the query
     *
     * @param  string $from
     * @return $this
     * @phpstan-return static<Record, Doctrine_Query_Type_Update>
     */
    public function update($from = null)
    {
        $this->type = Doctrine_Query_Type::UPDATE();
        if ($from != null) {
            return $this->addDqlQueryPart('from', $from);
        }
        return $this;
    }

    /**
     * set
     * sets the SET part of the query
     *
     * @param  array|string $key
     * @param  mixed        $value
     * @param  array|scalar $params
     * @return $this
     */
    public function set($key, $value = null, $params = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, '?', [$v]);
            }
            return $this;
        } else {
            if ($params !== null) {
                if (is_array($params)) {
                    $this->params['set'] = array_merge($this->params['set'], $params);
                } else {
                    $this->params['set'][] = $params;
                }
            }

            return $this->addDqlQueryPart('set', $key . ' = ' . $value, true);
        }
    }

    /**
     * from
     * sets the FROM part of the query
     * <code>
     * $q->from('User u');
     * </code>
     *
     * @param  string $from Query FROM part
     * @return $this
     */
    public function from($from)
    {
        return $this->addDqlQueryPart('from', $from);
    }

    /**
     * innerJoin
     * appends an INNER JOIN to the FROM part of the query
     *
     * @param  string       $join   Query INNER JOIN
     * @param  array|scalar $params
     * @return $this
     */
    public function innerJoin($join, $params = [])
    {
        if (is_array($params)) {
            $this->params['join'] = array_merge($this->params['join'], $params);
        } else {
            $this->params['join'][] = $params;
        }

        return $this->addDqlQueryPart('from', 'INNER JOIN ' . $join, true);
    }

    /**
     * leftJoin
     * appends a LEFT JOIN to the FROM part of the query
     *
     * @param  string       $join   Query LEFT JOIN
     * @param  array|scalar $params
     * @return $this
     */
    public function leftJoin($join, $params = [])
    {
        if (is_array($params)) {
            $this->params['join'] = array_merge($this->params['join'], $params);
        } else {
            $this->params['join'][] = $params;
        }

        return $this->addDqlQueryPart('from', 'LEFT JOIN ' . $join, true);
    }

    /**
     * groupBy
     * sets the GROUP BY part of the query
     *
     * @param  string $groupby Query GROUP BY part
     * @return $this
     */
    public function groupBy($groupby)
    {
        return $this->addDqlQueryPart('groupby', $groupby);
    }

    /**
     * where
     * sets the WHERE part of the query
     *
     * @param  string            $where  Query WHERE part
     * @param  array|scalar|null $params an array of parameters or a simple scalar
     * @return $this
     */
    public function where($where, $params = [])
    {
        $this->params['where'] = [];

        if (is_array($params)) {
            $this->params['where'] = $params;
        } else {
            $this->params['where'][] = $params;
        }

        return $this->addDqlQueryPart('where', $where);
    }

    /**
     * having
     * sets the HAVING part of the query
     *
     * @param  string       $having Query HAVING part
     * @param  array|scalar $params an array of parameters or a simple scalar
     * @return $this
     */
    public function having($having, $params = [])
    {
        $this->params['having'] = [];
        if (is_array($params)) {
            $this->params['having'] = $params;
        } else {
            $this->params['having'][] = $params;
        }

        return $this->addDqlQueryPart('having', $having);
    }

    /**
     * Sets the ORDER BY part of the query.
     * <code>
     * $q->orderBy('u.name');
     * $query->orderBy('u.birthDate DESC');
     * </code>
     *
     * @param  string $orderby Query ORDER BY part
     * @return $this
     */
    public function orderBy($orderby)
    {
        return $this->addDqlQueryPart('orderby', $orderby);
    }

    /**
     * limit
     * sets the Query query limit
     *
     * @param  integer $limit limit to be used for limiting the query results
     * @return $this
     */
    public function limit($limit)
    {
        return $this->addDqlQueryPart('limit', $limit);
    }

    /**
     * offset
     * sets the Query query offset
     *
     * @param  integer $offset offset to be used for paginating the query
     * @return $this
     */
    public function offset($offset)
    {
        return $this->addDqlQueryPart('offset', $offset);
    }

    /**
     * Resets all the sql parts.
     *
     * @return void
     */
    protected function clear()
    {
        $this->sqlParts = [
                    'select'    => [],
                    'distinct'  => false,
                    'forUpdate' => false,
                    'from'      => [],
                    'set'       => [],
                    'join'      => [],
                    'where'     => [],
                    'groupby'   => [],
                    'having'    => [],
                    'orderby'   => [],
                    'limit'     => false,
                    'offset'    => false,
                    ];
    }

    /**
     * @param  int $hydrationMode
     * @return $this
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->hydrator->setHydrationMode($hydrationMode);
        return $this;
    }

    /**
     * Gets the components of this query.
     *
     * @return array
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    /**
     * Return the SQL parts.
     *
     * @return array The parts
     */
    public function getSqlParts()
    {
        return $this->sqlParts;
    }

    /**
     * returns the type of this query object
     * by default the type is Doctrine_Query_Type::SELECT but if update() or delete()
     * are being called the type is Doctrine_Query_Type::UPDATE and Doctrine_Query_Type::DELETE,
     * respectively
     */
    public function getType(): Doctrine_Query_Type
    {
        return $this->type;
    }

    /**
     * @param  integer|null $timeToLive how long the cache entry is valid
     * @param  string|null $resultCacheHash The key to use for storing the queries result cache entry
     * @return $this
     */
    public function useResultCache(Doctrine_Cache_Interface|bool|null $driver = true, ?int $timeToLive = null, ?string $resultCacheHash = null): self
    {
        if ($driver === false) {
            $driver = null;
        }
        $this->resultCache = $driver;
        $this->resultCacheHash = $resultCacheHash;

        if ($timeToLive !== null) {
            $this->setResultCacheLifeSpan($timeToLive);
        }
        return $this;
    }

    /**
     * Set the result cache hash to be used for storing the results in the cache driver
     *
     * @param  string $resultCacheHash
     * @return $this
     */
    public function setResultCacheHash($resultCacheHash)
    {
        $this->resultCacheHash = $resultCacheHash;

        return $this;
    }

    /**
     * Clear the result cache entry for this query
     *
     * @return $this
     */
    public function clearResultCache()
    {
        $this->getResultCacheDriver()
            ->delete($this->getResultCacheHash());

        return $this;
    }

    /**
     * @param  integer|null $timeToLive how long the cache entry is valid
     * @return $this
     */
    public function useQueryCache(Doctrine_Cache_Interface|bool|null $driver = true, ?int $timeToLive = null): self
    {
        $this->queryCache = $driver;
        if ($timeToLive !== null) {
            $this->setQueryCacheLifeSpan($timeToLive);
        }
        return $this;
    }

    /**
     * @param  boolean $expire whether or not to force cache expiration
     * @return $this     this object
     */
    public function expireResultCache($expire = true)
    {
        $this->expireResultCache = $expire;
        return $this;
    }

    /**
     * @param  boolean $expire whether or not to force cache expiration
     * @return $this     this object
     */
    public function expireQueryCache($expire = true)
    {
        $this->expireQueryCache = $expire;
        return $this;
    }

    /**
     * @param  integer $timeToLive how long the cache entry is valid (in seconds)
     * @return $this     this object
     */
    public function setResultCacheLifeSpan($timeToLive)
    {
        if ($timeToLive !== null) {
            $timeToLive = (int) $timeToLive;
        }
        $this->resultCacheTTL = $timeToLive;

        return $this;
    }

    /**
     * Gets the life span of the result cache in seconds.
     */
    public function getResultCacheLifeSpan(): ?int
    {
        return $this->resultCacheTTL;
    }

    /**
     * setQueryCacheLifeSpan
     *
     * @param  integer|null $timeToLive how long the cache entry is valid
     * @return $this     this object
     */
    public function setQueryCacheLifeSpan(?int $timeToLive): self
    {
        $this->queryCacheTTL = $timeToLive;
        return $this;
    }

    /**
     * Gets the life span of the query cache the Query object is using.
     *
     * @return integer  The life span in seconds.
     */
    public function getQueryCacheLifeSpan(): ?int
    {
        return $this->queryCacheTTL;
    }

    /**
     * returns the cache driver used for caching result sets
     *
     * @return Doctrine_Cache_Interface   cache driver
     */
    public function getResultCacheDriver()
    {
        if ($this->resultCache instanceof Doctrine_Cache_Interface) {
            return $this->resultCache;
        } else {
            return $this->connection->getResultCacheDriver();
        }
    }

    /**
     * getQueryCacheDriver
     * returns the cache driver used for caching queries
     *
     * @return Doctrine_Cache_Interface    cache driver
     */
    public function getQueryCacheDriver()
    {
        if ($this->queryCache instanceof Doctrine_Cache_Interface) {
            return $this->queryCache;
        } else {
            return $this->connection->getQueryCacheDriver();
        }
    }

    /**
     * getConnection
     *
     * @return Doctrine_Connection
     */
    public function getConnection(): Doctrine_Connection
    {
        return $this->connection;
    }

    /**
     * Checks if there's at least one DQL part defined to the internal parts collection.
     *
     * @param  string $queryPartName The name of the query part.
     * @return boolean
     */
    protected function hasDqlQueryPart($queryPartName)
    {
        return count($this->dqlParts[$queryPartName]) > 0;
    }

    /**
     * Adds a DQL part to the internal parts collection.
     *
     * This method add the part specified to the array named by $queryPartName.
     * Most part names support multiple parts addition.
     *
     * @see    $dqlParts;
     * @see    Doctrine_Query::getDqlPart()
     * @param  string     $queryPartName The name of the query part.
     * @param  string|int $queryPart     The actual query part to add.
     * @param  boolean    $append        Whether to append $queryPart to already existing
     *                                   parts under the same $queryPartName. Defaults to
     *                                   FALSE (previously added parts with the same name
     *                                   get overridden).
     * @return $this
     */
    protected function addDqlQueryPart(string $queryPartName, string|int $queryPart, bool $append = false): self
    {
        if ($append) {
            $this->dqlParts[$queryPartName][] = $queryPart;
        } else {
            $this->dqlParts[$queryPartName] = [$queryPart];
        }

        $this->state = Doctrine_Query::STATE_DIRTY;
        return $this;
    }

    /**
     * @param string $queryPartName the name of the query part
     * @param string[] $queryParts an array containing the query part data
     */
    protected function processDqlQueryPart(string $queryPartName, array $queryParts): void
    {
        $this->removeSqlQueryPart($queryPartName);

        foreach ($queryParts as $queryPart) {
            $parser = $this->getParser($queryPartName);
            assert(method_exists($parser, 'parse'));
            $sql = $parser->parse($queryPart);
            if (isset($sql)) {
                if ($queryPartName == 'limit' || $queryPartName == 'offset') {
                    $this->setSqlQueryPart($queryPartName, $sql);
                } else {
                    $this->addSqlQueryPart($queryPartName, $sql);
                }
            }
        }
    }

    /**
     * _getParser
     * parser lazy-loader
     *
     * @throws Doctrine_Query_Exception     if unknown parser name given
     * @param  string $name
     * @return Doctrine_Query_Part
     * @todo   Doc/Description: What is the parameter for? Which parsers are available?
     */
    protected function getParser($name)
    {
        if (!isset($this->parsers[$name])) {
            $class = 'Doctrine_Query_' . ucwords(strtolower($name));

            if (!class_exists($class)) {
                throw new Doctrine_Query_Exception('Unknown parser ' . $name);
            }

            $this->parsers[$name] = new $class($this, $this->tokenizer);
        }

        return $this->parsers[$name];
    }

    /**
     * Gets the SQL query that corresponds to this query object.
     * The returned SQL syntax depends on the connection driver that is used
     * by this query object at the time of this method call.
     */
    abstract public function getSqlQuery(array $params = [], bool $limitSubquery = true): string;

    /**
     * parseDqlQuery
     * parses a dql query
     *
     * @param  string $query query to be parsed
     * @return $this  this object
     */
    abstract public function parseDqlQuery($query);

    /**
     * toString magic call
     * this method is automatically called when Doctrine_Query object is trying to be used as a string
     * So, it it converted into its DQL correspondant
     *
     * @return string DQL string
     */
    public function __toString()
    {
        return $this->getDql();
    }

    /**
     * Gets the disableLimitSubquery property.
     *
     * @return boolean
     */
    public function getDisableLimitSubquery()
    {
        return $this->disableLimitSubquery;
    }

    /**
     * Allows you to set the disableLimitSubquery property -- setting this to true will
     * restrict the query object from using the limit sub query method of tranversing many relationships.
     *
     * @param boolean $disableLimitSubquery
     *
     * @return void
     */
    public function setDisableLimitSubquery($disableLimitSubquery)
    {
        $this->disableLimitSubquery = $disableLimitSubquery;
    }

    /**
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    protected static function arrayDiffAssocRecursive($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = static::arrayDiffAssocRecursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }

    /**
     * if $bool parameter is set this method sets the value of
     * Doctrine_Query::$isSubquery. If this value is set to true
     * the query object will not load the primary key fields of the selected
     * components.
     */
    public function isSubquery(?bool $bool = null): bool
    {
        if ($bool !== null) {
            $this->isSubquery = $bool;
        }
        return $this->isSubquery;
    }
}
