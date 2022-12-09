<?php

namespace Doctrine1;

/**
 * @template Record of Record
 * @template Type of Query\Type
 * @extends AbstractQuery<Record, Type>
 */
class RawSql extends AbstractQuery
{
    /**
     * @var array $fields
     */
    private $fields = [];

    /**
     * This was previously undefined, setting to public to prevent any sort of BC break
     *
     * @var bool
     */
    public $preQuery;

    /**
     * Constructor.
     *
     * @param Connection        $connection The connection object the query will use.
     * @param Hydrator\AbstractHydrator $hydrator   The hydrator that will be used for generating result sets.
     */
    public function __construct(Connection $connection = null, Hydrator\AbstractHydrator $hydrator = null)
    {
        parent::__construct($connection, $hydrator);

        // Fix #1472. It's alid to disable QueryCache since there's no DQL for RawSql.
        // RawSql expects to be plain SQL + syntax for SELECT part. It is used as is in query execution.
        $this->useQueryCache(false);
    }

    protected function clear(): void
    {
        $this->preQuery              = false;
        $this->pendingJoinConditions = [];
    }

    /**
     * parseDqlQueryPart
     * parses given DQL query part. Overrides AbstractQuery::parseDqlQueryPart().
     * This implementation does no parsing at all, except of the SELECT portion of the query
     * which is special in RawSql queries. The entire remaining parts are used "as is", so
     * the user of the RawSql query is responsible for writing SQL that is portable between
     * different DBMS.
     *
     * @param  string  $queryPartName the name of the query part
     * @param  string  $queryPart     query part to be parsed
     * @param  boolean $append        whether or not to append the query part to its stack
     *                                if false is given, this method will overwrite the
     *                                given query part stack with $queryPart
     * @return $this           this object
     */
    public function parseDqlQueryPart($queryPartName, $queryPart, $append = false)
    {
        if ($queryPartName == 'select') {
            $this->parseSelectFields($queryPart);
            return $this;
        }
        if (!isset($this->sqlParts[$queryPartName])) {
            $this->sqlParts[$queryPartName] = [];
        }

        if (!$append) {
            $this->sqlParts[$queryPartName] = [$queryPart];
        } else {
            $this->sqlParts[$queryPartName][] = $queryPart;
        }
        return $this;
    }

    /**
     * Adds a DQL query part. Overrides AbstractQuery::_addDqlQueryPart().
     * This implementation for RawSql parses the new parts right away, generating the SQL.
     * @return $this
     */
    protected function addDqlQueryPart(string $queryPartName, string|int $queryPart, bool $append = false): self
    {
        return $this->parseDqlQueryPart($queryPartName, (string) $queryPart, $append);
    }

    /**
     * Add select parts to fields.
     *
     * @param string $queryPart The name of the querypart
     *
     * @return void
     */
    private function parseSelectFields($queryPart)
    {
        preg_match_all('/{([^}{]*)}/U', $queryPart, $m);
        $this->fields              = $m[1];
        $this->sqlParts['select'] = [];
    }

    /**
     * parseDqlQuery
     * parses an sql query and adds the parts to internal array.
     * Overrides AbstractQuery::parseDqlQuery().
     * This implementation simply tokenizes the provided query string and uses them
     * as SQL parts right away.
     *
     * @param  string $query query to be parsed
     * @return $this  this object
     */
    public function parseDqlQuery($query)
    {
        $this->parseSelectFields($query);
        $this->clear();

        $tokens = $this->tokenizer->sqlExplode($query, ' ');

        $parts = [];
        $type  = null;
        foreach ($tokens as $key => $part) {
            $partLowerCase = strtolower($part);
            switch ($partLowerCase) {
                case 'select':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $type = $partLowerCase;
                    if (!isset($parts[$partLowerCase])) {
                        $parts[$partLowerCase] = [];
                    }
                    break;
                case 'order':
                case 'group':
                    $i = $key + 1;
                    if (isset($tokens[$i]) && strtolower($tokens[$i]) === 'by') {
                        $type         = $partLowerCase . 'by';
                        $parts[$type] = [];
                    } else {
                        //not a keyword so we add it to the previous type
                        $parts[$type][] = $part;
                    }
                    break;
                case 'by':
                    break;
                default:
                    //not a keyword so we add it to the previous type.
                    if (!isset($parts[$type][0])) {
                        $parts[$type][0] = $part;
                    } else {
                        // why does this add to index 0 and not append to the
                        // array. If it had done that one could have used
                        // parseQueryPart.
                        $parts[$type][0] .= ' ' . $part;
                    }
            }
        }

        $this->sqlParts           = $parts;
        $this->sqlParts['select'] = [];

        return $this;
    }

    public function getSqlQuery(array $params = [], bool $limitSubquery = true): string
    {
        // Assign building/execution specific params
        $this->params['exec'] = $params;

        // Initialize prepared parameters array
        $this->execParams = $this->getFlattenedParams();

        // Initialize prepared parameters array
        $this->fixArrayParameterValues($this->execParams);

        $select = [];

        $formatter = $this->getConnection()->formatter;

        foreach ($this->fields as $field) {
            $e = explode('.', $field);
            if (!isset($e[1])) {
                throw new RawSql\Exception('All selected fields in Sql query must be in format tableAlias.fieldName');
            }
            // try to auto-add component
            if (!$this->hasSqlTableAlias($e[0])) {
                try {
                    $this->addComponent($e[0], ucwords($e[0]));
                } catch (Exception $exception) {
                    throw new RawSql\Exception("The associated component for table alias {$e[0]} couldn't be found.", previous: $exception);
                }
            }

            $componentAlias = $this->getComponentAlias($e[0]);

            if ($e[1] == '*') {
                foreach ($this->queryComponents[$componentAlias]['table']->getColumnNames() as $name) {
                    $field = $formatter->quoteIdentifier($e[0]) . '.' . $formatter->quoteIdentifier($name);

                    $select[$componentAlias][$field] = $field . ' AS ' . $formatter->quoteIdentifier($e[0] . '__' . $name);
                }
            } else {
                $field                           = $formatter->quoteIdentifier($e[0]) . '.' . $formatter->quoteIdentifier($e[1]);
                $select[$componentAlias][$field] = $field . ' AS ' . $formatter->quoteIdentifier($e[0] . '__' . $e[1]);
            }
        }

        // force-add all primary key fields
        if (!isset($this->sqlParts['distinct']) || $this->sqlParts['distinct'] != true) {
            foreach ($this->getTableAliasMap() as $tableAlias => $componentAlias) {
                $map = $this->queryComponents[$componentAlias];

                foreach ((array) $map['table']->getIdentifierColumnNames() as $key) {
                    $field = $formatter->quoteIdentifier($tableAlias) . '.' . $formatter->quoteIdentifier($key);

                    if (!isset($this->sqlParts['select'][$field])) {
                        $select[$componentAlias][$field] = $field . ' AS ' . $formatter->quoteIdentifier($tableAlias . '__' . $key);
                    }
                }
            }
        }

        $q = 'SELECT ';

        if (isset($this->sqlParts['distinct']) && $this->sqlParts['distinct'] == true) {
            $q .= 'DISTINCT ';
        }

        // first add the fields of the root component
        reset($this->queryComponents);
        $componentAlias = key($this->queryComponents);

        if ($componentAlias === null) {
            throw new RawSql\Exception('Missing component alias.');
        }

        $this->rootAlias = $componentAlias;

        $q .= implode(', ', $select[$componentAlias]);
        unset($select[$componentAlias]);

        foreach ($select as $component => $fields) {
            $q .= ', ' . implode(', ', $fields);
        }

        $string = $this->getInheritanceCondition($this->getRootAlias());

        if (!empty($string)) {
            $this->sqlParts['where'][] = $string;
        }

        $q .= (!empty($this->sqlParts['from'])) ? ' FROM ' . implode(' ', $this->sqlParts['from']) : '';
        $q .= (!empty($this->sqlParts['where'])) ? ' WHERE ' . implode(' AND ', $this->sqlParts['where']) : '';
        $q .= (!empty($this->sqlParts['groupby'])) ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupby']) : '';
        $q .= (!empty($this->sqlParts['having'])) ? ' HAVING ' . implode(' AND ', $this->sqlParts['having']) : '';
        $q .= (!empty($this->sqlParts['orderby'])) ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderby']) : '';
        $q .= (!empty($this->sqlParts['limit'])) ? ' LIMIT ' . implode(' ', $this->sqlParts['limit']) : '';
        $q .= (!empty($this->sqlParts['offset'])) ? ' OFFSET ' . implode(' ', $this->sqlParts['offset']) : '';

        if (!empty($string)) {
            array_pop($this->sqlParts['where']);
        }
        return $q;
    }

    /**
     * getCountQuery
     * builds the count query.
     *
     * @param  array $params
     * @return string       the built sql query
     */
    public function getCountSqlQuery($params = [])
    {
        //Doing COUNT( DISTINCT rootComponent.id )
        //This is not correct, if the result is not hydrated by doctrine, but it mimics the behaviour of Query::getCountQuery
        reset($this->queryComponents);
        $componentAlias = key($this->queryComponents);

        if ($componentAlias === null) {
            throw new RawSql\Exception('Missing component alias.');
        }

        $this->rootAlias = $componentAlias;

        $tableAlias = $this->getSqlTableAlias($componentAlias);
        $fields     = [];

        foreach ((array) $this->queryComponents[$componentAlias]['table']->getIdentifierColumnNames() as $key) {
            $fields[] = $tableAlias . '.' . $key;
        }

        $q = 'SELECT COUNT(*) as num_results FROM (SELECT DISTINCT ' . implode(', ', $fields);

        $string = $this->getInheritanceCondition($this->getRootAlias());
        if (!empty($string)) {
            $this->sqlParts['where'][] = $string;
        }

        $q .= (!empty($this->sqlParts['from'])) ? ' FROM ' . implode(' ', $this->sqlParts['from']) : '';
        $q .= (!empty($this->sqlParts['where'])) ? ' WHERE ' . implode(' AND ', $this->sqlParts['where']) : '';
        $q .= (!empty($this->sqlParts['groupby'])) ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupby']) : '';
        $q .= (!empty($this->sqlParts['having'])) ? ' HAVING ' . implode(' AND ', $this->sqlParts['having']) : '';

        $q .= ') as results';

        if (!empty($string)) {
            array_pop($this->sqlParts['where']);
        }

        return $q;
    }

    /**
     * count
     * fetches the count of the query
     *
     * This method executes the main query without all the
     * selected fields, ORDER BY part, LIMIT part and OFFSET part.
     *
     * This is an exact copy of the Dql Version
     *
     * @see    Query::count()
     * @param  array $params an array of prepared statement parameters
     * @return integer             the count of this query
     */
    public function count($params = []): int
    {
        $sql     = $this->getCountSqlQuery();
        $params  = $this->getCountQueryParams($params);
        $results = $this->getConnection()->fetchAll($sql, $params);

        if (count($results) > 1) {
            $count = count($results);
        } else {
            if (isset($results[0])) {
                $results[0] = array_change_key_case($results[0], CASE_LOWER);
                $count      = $results[0]['num_results'];
            } else {
                $count = 0;
            }
        }

        return (int) $count;
    }

    /**
     * getFields
     * returns the fields associated with this parser
     *
     * @return array    all the fields associated with this parser
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * addComponent
     *
     * @param  string $tableAlias
     * @param  string $path
     * @return $this
     */
    public function addComponent($tableAlias, $path)
    {
        $tmp           = explode(' ', $path);
        $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = explode('.', $tmp[0]);

        $fullPath   = $tmp[0];
        $fullLength = strlen($fullPath);

        $table = null;

        $parent = '';
        $currPath = '';

        if (isset($this->queryComponents[$e[0]])) {
            $table = $this->queryComponents[$e[0]]['table'];

            $currPath = $parent = array_shift($e);
        }

        foreach ($e as $k => $component) {
            // get length of the previous path
            $length = strlen($currPath);

            // build the current component path
            $currPath = ($currPath) ? $currPath . '.' . $component : $component;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($currPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $currPath;
            }
            if (!isset($table)) {
                $conn = Manager::getInstance()
                        ->getConnectionForComponent($component);

                $table                                   = $conn->getTable($component);
                $this->queryComponents[$componentAlias] = ['table' => $table];
            } else {
                $relation = $table->getRelation($component);

                $this->queryComponents[$componentAlias] = [
                    'table' => $relation->getTable(),
                    'parent' => $parent,
                    'relation' => $relation,
                ];
            }
            $this->addSqlTableAlias($tableAlias, $componentAlias);

            $parent = $currPath;
        }

        return $this;
    }

    /**
     * calculateResultCacheHash
     * calculate hash key for result cache
     *
     * @param  array $params
     * @return string    the hash
     */
    public function calculateResultCacheHash($params = []): string
    {
        $sql    = $this->getSqlQuery();
        $conn   = $this->getConnection();
        $params = $this->getFlattenedParams($params);
        $scalarMode = $this->hydrator->getHydrationMode();
        $scalarMode = $scalarMode instanceof HydrationMode ? $scalarMode->value : $scalarMode;
        $hash   = md5($scalarMode . $conn->getName() . $conn->getOption('dsn') . $sql . var_export($params, true));
        return $hash;
    }
}
