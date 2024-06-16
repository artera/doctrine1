<?php

namespace Doctrine1;

use Doctrine1\Connection\Mysql;
use Doctrine1\Connection\Pgsql;
use Doctrine1\Query\State;

/**
 * @template Record of Record
 * @template Type of Query\Type
 * @extends AbstractQuery<Record, Type>
 */
class Query extends AbstractQuery implements \Countable
{
    /**
     * @var string[] The DQL keywords.
     */
    protected static array $keywords = [
        "ALL",
        "AND",
        "ANY",
        "AS",
        "ASC",
        "AVG",
        "BETWEEN",
        "BIT_LENGTH",
        "BY",
        "CHARACTER_LENGTH",
        "CHAR_LENGTH",
        "CURRENT_DATE",
        "CURRENT_TIME",
        "CURRENT_TIMESTAMP",
        "DELETE",
        "DESC",
        "DISTINCT",
        "EMPTY",
        "EXISTS",
        "FALSE",
        "FETCH",
        "FROM",
        "GROUP",
        "HAVING",
        "IN",
        "INDEXBY",
        "INNER",
        "IS",
        "JOIN",
        "LEFT",
        "LIKE",
        "LOWER",
        "MEMBER",
        "MOD",
        "NEW",
        "NOT",
        "NULL",
        "OBJECT",
        "OF",
        "OR",
        "ORDER",
        "OUTER",
        "POSITION",
        "SELECT",
        "SOME",
        "TRIM",
        "TRUE",
        "UNKNOWN",
        "UPDATE",
        "WHERE",
    ];

    /**
     * @var array
     */
    protected array $subqueryAliases = [];

    /**
     * @var array $aggregateAliasMap an array containing all aggregate aliases, keys as dql aliases
     *                                and values as sql aliases
     */
    protected array $aggregateAliasMap = [];

    /**
     * @phpstan-var array{string, (string|int)[], string}[]
     */
    protected array $pendingAggregates = [];

    protected bool $needsSubquery = false;

    /**
     * @var array $neededTables an array containing the needed table aliases
     */
    protected array $neededTables = [];

    /**
     * @var array $pendingSubqueries SELECT part subqueries, these are called pending subqueries since
     *                                they cannot be parsed directly (some queries might be correlated)
     */
    protected array $pendingSubqueries = [];

    /**
     * @var array $pendingFields an array of pending fields (fields waiting to be parsed)
     */
    protected array $pendingFields = [];

    /**
     * @var array<string, Query\Part> $parsers an array of parser objects, each DQL query part has its own parser
     */
    protected array $parsers = [];

    /**
     * @var array $pendingJoinConditions an array containing pending joins
     */
    protected array $pendingJoinConditions = [];

    /**
     * @var array
     */
    protected array $expressionMap = [];

    /**
     * cached SQL query
     */
    protected string $sql;

    /**
     * returns a new Query object
     *
     * @phpstan-param  class-string<Query> $class
     * @return         Query
     * @phpstan-return Query<Record, Query\Type\Select>
     */
    public static function create(?Connection $conn = null, string $class = null): Query
    {
        if (!$class) {
            $class = Manager::getInstance()->getQueryClass();
        }
        return new $class($conn);
    }

    /**
     * Clears all the sql parts.
     */
    protected function clear(): void
    {
        $this->preQueried = false;
        $this->pendingJoinConditions = [];
        $this->state = State::Dirty;
    }

    /**
     * Resets the query to the state just after it has been instantiated.
     *
     * @return void
     */
    public function reset()
    {
        $this->subqueryAliases = [];
        $this->aggregateAliasMap = [];
        $this->pendingAggregates = [];
        $this->pendingSubqueries = [];
        $this->pendingFields = [];
        $this->neededTables = [];
        $this->expressionMap = [];
        $this->subqueryAliases = [];
        $this->needsSubquery = false;
        $this->isLimitSubqueryUsed = false;
    }

    /**
     * @return static
     * @phpstan-return Query<Record, Query\Type\Select>
     */
    public function createSubquery(): self
    {
        $obj = new static();

        // copy the aliases to the subquery
        $obj->copySubqueryInfo($this);

        // this prevents the 'id' being selected, re ticket #307
        $obj->isSubquery(true);

        return $obj;
    }

    /**
     * addPendingJoinCondition
     *
     * @param  string $componentAlias component alias
     * @param  string $joinCondition  dql join condition
     * @return void
     */
    public function addPendingJoinCondition($componentAlias, $joinCondition)
    {
        if (!isset($this->pendingJoinConditions[$componentAlias])) {
            $this->pendingJoinConditions[$componentAlias] = [];
        }

        $this->pendingJoinConditions[$componentAlias][] = $joinCondition;
    }

    /**
     * Convenience method to execute using array fetching as hydration mode.
     *
     * @param mixed[] $params
     *
     * @return array<string,mixed>[]
     */
    public function fetchArray($params = [])
    {
        /** @var array<string,mixed>[] */
        return $this->execute($params, HydrationMode::Array);
    }

    /**
     * Convenience method to execute the query and return the first item
     * of the collection.
     *
     * @param  mixed[] $params        Query parameters
     * @param  HydrationMode     $hydrationMode Hydration mode: see HydrationModes
     * @return Record|scalar Array or Record, depending on hydration mode. null if no result.
     * @phpstan-return Record|array<string,mixed>|scalar|null
     */
    public function fetchOne($params = [], ?HydrationMode $hydrationMode = null)
    {
        $collection = $this->execute($params, $hydrationMode);

        if (is_scalar($collection)) {
            return $collection;
        }

        if (is_countable($collection) && count($collection) === 0) {
            return null;
        }

        if ($collection instanceof \Iterator) {
            return $collection->current();
        }

        if ($collection instanceof Collection) {
            return $collection->getFirst();
        } elseif (is_array($collection)) {
            return array_shift($collection);
        }

        // @phpstan-ignore-next-line
        return null;
    }

    /**
     * @param  string $dqlAlias the dql alias of an aggregate value
     * @return string
     */
    public function getSqlAggregateAlias($dqlAlias)
    {
        if (isset($this->aggregateAliasMap[$dqlAlias])) {
            // mark the expression as used
            $this->expressionMap[$dqlAlias][1] = true;

            return $this->aggregateAliasMap[$dqlAlias];
        } elseif (!empty($this->pendingAggregates)) {
            $this->processPendingAggregates();

            return $this->getSqlAggregateAlias($dqlAlias);
        } elseif (!($this->connection->getPortability() & Core::PORTABILITY_EXPR)) {
            return $dqlAlias;
        } else {
            throw new Query\Exception("Unknown aggregate alias: " . $dqlAlias);
        }
    }

    /**
     * Check if a dql alias has a sql aggregate alias
     *
     * @param  string $dqlAlias
     * @return boolean
     */
    public function hasSqlAggregateAlias($dqlAlias)
    {
        try {
            $this->getSqlAggregateAlias($dqlAlias);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Adjust the processed param index for "foo.bar IN ?" support
     *
     * @param  int $index
     * @return void
     */
    public function adjustProcessedParam($index)
    {
        // Retrieve all params
        $params = $this->getInternalParams();

        // Retrieve already processed values
        $first = array_slice($params, 0, $index);
        $last = array_slice($params, $index, count($params) - $index);

        // Include array as values splicing the params array
        array_splice($last, 0, 1, $last[0]);

        // Put all param values into a single index
        $this->execParams = array_merge($first, $last);
    }

    /**
     * Retrieves a specific DQL query part.
     *
     * @see    AbstractQuery::$dqlParts
     * <code>
     * var_dump($q->getDqlPart('where'));
     * // array(2) { [0] => string(8) 'name = ?' [1] => string(8) 'date > ?' }
     * </code>
     * @param  string $queryPart the name of the query part; can be:
     *                           array from, containing strings;
     *                           array select, containg string;
     *                           boolean forUpdate; boolean forShare;
     *                           boolean noWait; boolean skipLocked;
     *                           array set; array join;
     *                           array where; array groupby;
     *                           array having; array orderby,
     *                           containing strings such as 'id
     *                           ASC'; array limit, containing
     *                           numerics; array offset, containing
     *                           numerics;
     * @return array
     */
    public function getDqlPart($queryPart)
    {
        if (!isset($this->dqlParts[$queryPart])) {
            throw new Query\Exception("Unknown query part " . $queryPart);
        }

        return $this->dqlParts[$queryPart];
    }

    /**
     * Method to check if a arbitrary piece of dql exists
     *
     * @param  string $dql Arbitrary piece of dql to check for
     * @return boolean
     */
    public function contains($dql)
    {
        return stripos($this->getDql(), $dql) === false ? false : true;
    }

    /**
     * processPendingFields
     * the fields in SELECT clause cannot be parsed until the components
     * in FROM clause are parsed, hence this method is called everytime a
     * specific component is being parsed. For instance, the wildcard '*'
     * is expanded in the list of columns.
     *
     * @throws Query\Exception     if unknown component alias has been given
     * @param  string $componentAlias the alias of the component
     * @return string|null SQL code
     * @todo   Description: What is a 'pending field' (and are there non-pending fields, too)?
     *       What is 'processed'? (Meaning: What information is gathered & stored away)
     */
    public function processPendingFields($componentAlias)
    {
        $tableAlias = $this->getSqlTableAlias($componentAlias);
        $table = $this->queryComponents[$componentAlias]["table"];

        if (!isset($this->pendingFields[$componentAlias])) {
            if ($this->hydrator->getHydrationMode() != HydrationMode::None) {
                if (!$this->isSubquery && $componentAlias == $this->getRootAlias()) {
                    throw new Query\Exception("The root class of the query (alias $componentAlias) " . " must have at least one field selected.");
                }
            }
            return null;
        }

        // At this point we know the component is FETCHED (either it's the base class of
        // the query (FROM xyz) or its a "fetch join").

        // Check that the parent join (if there is one), is a "fetch join", too.
        if (!$this->isSubquery() && isset($this->queryComponents[$componentAlias]["parent"])) {
            $parentAlias = $this->queryComponents[$componentAlias]["parent"];
            if (
                is_string($parentAlias) &&
                !isset($this->pendingFields[$parentAlias]) &&
                $this->hydrator->getHydrationMode() != HydrationMode::None &&
                $this->hydrator->getHydrationMode() != HydrationMode::Scalar &&
                $this->hydrator->getHydrationMode() != HydrationMode::SingleScalar
            ) {
                throw new Query\Exception(
                    "The left side of the join between " .
                        "the aliases '$parentAlias' and '$componentAlias' must have at least" .
                        " the primary key field(s) selected."
                );
            }
        }

        $fields = $this->pendingFields[$componentAlias];

        // check for wildcards
        if (in_array("*", $fields)) {
            $fields = $table->getFieldNames();
        } elseif ($this->hydrator instanceof Hydrator) {
            $driverClassName = $this->hydrator->getHydratorDriverClassName();
            // only auto-add the primary key fields if this query object is not
            // a subquery of another query object or we're using a child of the Object Graph
            // hydrator
            if (!$this->isSubquery && is_subclass_of($driverClassName, Hydrator\Graph::class)) {
                $fields = array_unique(array_merge((array) $table->getIdentifier(), $fields));
            }
        }

        $sql = [];
        foreach ($fields as $fieldAlias => $fieldName) {
            $columnName = $table->getColumnName($fieldName);
            if (($owner = $table->getColumnOwner($columnName)) !== null && $owner !== $table->getComponentName()) {
                $parent = $this->connection->getTable($owner);
                $columnName = $parent->getColumnName($fieldName);
                $parentAlias = $this->getSqlTableAlias($componentAlias . "." . $parent->getComponentName());
                $sql[] =
                    $this->connection->quoteIdentifier($parentAlias) .
                    "." .
                    $this->connection->quoteIdentifier($columnName) .
                    " AS " .
                    $this->connection->quoteIdentifier($tableAlias . "__" . $columnName);
            } else {
                // Fix for http://www.doctrine-project.org/jira/browse/DC-585
                // Take the field alias if available
                if (isset($this->aggregateAliasMap[$fieldAlias])) {
                    $aliasSql = $this->aggregateAliasMap[$fieldAlias];
                } else {
                    $columnName = $table->getColumnName($fieldName);
                    $aliasSql = $this->connection->quoteIdentifier($tableAlias . "__" . $columnName);
                }
                $sql[] = $this->connection->quoteIdentifier($tableAlias) . "." . $this->connection->quoteIdentifier($columnName) . " AS " . $aliasSql;
            }
        }

        $this->neededTables[] = $tableAlias;

        return implode(", ", $sql);
    }

    /**
     * Parses a nested field
     * <code>
     * $q->parseSelectField('u.Phonenumber.value');
     * </code>
     *
     * @param  string $field
     * @throws Query\Exception     if unknown component alias has been given
     * @return string   SQL fragment
     * @todo   Description: Explain what this method does. Is there a relation to parseSelect()?
     *       This method is not used from any class or testcase in the Doctrine package.
     */
    public function parseSelectField($field)
    {
        $terms = explode(".", $field);

        if (isset($terms[1])) {
            $componentAlias = $terms[0];
            $field = $terms[1];
        } else {
            reset($this->queryComponents);
            $componentAlias = key($this->queryComponents);
            $fields = $terms[0];
        }

        if ($componentAlias === null) {
            throw new Query\Exception("Missing component alias");
        }

        $tableAlias = $this->getSqlTableAlias($componentAlias);
        $table = $this->queryComponents[$componentAlias]["table"];

        // check for wildcards
        if ($field === "*") {
            $sql = [];

            foreach ($table->getColumnNames() as $field) {
                $sql[] = $this->parseSelectField($componentAlias . "." . $field);
            }

            return implode(", ", $sql);
        } else {
            $name = $table->getColumnName($field);

            $this->neededTables[] = $tableAlias;

            return $this->connection->quoteIdentifier($tableAlias . "." . $name) . " AS " . $this->connection->quoteIdentifier($tableAlias . "__" . $name);
        }
    }

    /**
     * returns the component alias for owner of given expression
     *
     * @param  string $expr expression from which to get to owner from
     * @return string           the component alias
     * @todo   Description: What does it mean if a component is an 'owner' of an expression?
     *       What kind of 'expression' are we talking about here?
     */
    public function getExpressionOwner($expr)
    {
        if (strtoupper(substr(trim($expr, "( "), 0, 6)) !== "SELECT") {
            // Fix for http://www.doctrine-project.org/jira/browse/DC-754
            $expr = preg_replace('/([\'\"])[^\1]*\1/', "", $expr) ?? $expr;
            preg_match_all("/[a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*[\.[a-z0-9]+]*/i", $expr, $matches);

            $match = current($matches);

            if (isset($match[0])) {
                $terms = explode(".", $match[0]);

                return $terms[0];
            }
        }
        return $this->getRootAlias();
    }

    /**
     * parses the query select part and
     * adds selected fields to pendingFields array
     *
     * @param string $dql
     *
     * @todo Description: What information is extracted (and then stored)?
     *
     * @return void
     */
    public function parseSelect($dql)
    {
        $refs = $this->tokenizer->sqlExplode($dql, ",");
        $refs[0] = trim($refs[0]);

        $pos = strpos($refs[0], " ") ?: 0;
        $first = substr($refs[0], 0, $pos);

        // check for DISTINCT keyword
        if ($first === "DISTINCT") {
            $this->sqlParts["distinct"] = true;

            $refs[0] = substr($refs[0], ++$pos);
        }

        $parsedComponents = [];

        foreach ($refs as $reference) {
            $reference = trim($reference);

            if (empty($reference)) {
                continue;
            }

            $terms = $this->tokenizer->sqlExplode($reference, " ");
            $pos = strpos($terms[0], "(");

            if (count($terms) > 1 || $pos !== false) {
                $expression = array_shift($terms);
                assert(is_string($expression));
                $alias = array_pop($terms);

                if (!$alias) {
                    assert($pos !== false);
                    $alias = substr($expression, 0, $pos);
                }

                // Fix for http://www.doctrine-project.org/jira/browse/DC-706
                if ($pos !== false && substr($expression, 0, 1) !== "'" && substr($expression, 0, $pos) == "") {
                    $queryComponents = $this->queryComponents;
                    reset($queryComponents);
                    $componentAlias = key($queryComponents);
                } else {
                    $componentAlias = $this->getExpressionOwner($expression);
                }

                $expression = $this->parseClause($expression);

                if ($componentAlias === null) {
                    throw new Query\Exception("Missing component alias");
                }
                $tableAlias = $this->getSqlTableAlias($componentAlias);

                $index = count($this->aggregateAliasMap);

                $sqlAlias = $this->connection->quoteIdentifier("{$tableAlias}__$index");
                $this->sqlParts["select"][] = "$expression AS $sqlAlias";

                $this->aggregateAliasMap[$alias] = $sqlAlias;
                $this->expressionMap[$alias][0] = $expression;

                $this->queryComponents[$componentAlias]["agg"][$index] = $alias;

                $this->neededTables[] = $tableAlias;

                // Fix for http://www.doctrine-project.org/jira/browse/DC-585
                // Add selected columns to pending fields
                if (preg_match('/^[^\(]+\.[\'|`]?(.*?)[\'|`]?$/', $expression, $field)) {
                    $this->pendingFields[$componentAlias][$alias] = $field[1];
                }
            } else {
                $e = explode(".", $terms[0]);

                if (isset($e[1])) {
                    $componentAlias = $e[0];
                    $field = $e[1];
                } else {
                    reset($this->queryComponents);
                    $componentAlias = key($this->queryComponents);
                    $field = $e[0];
                }

                $this->pendingFields[$componentAlias][] = $field;
            }
        }
    }

    /**
     * parses given DQL clause
     *
     * this method handles five tasks:
     *
     * 1. Converts all DQL functions to their native SQL equivalents
     * 2. Converts all component references to their table alias equivalents
     * 3. Converts all field names to actual column names
     * 4. Quotes all identifiers
     * 5. Parses nested clauses and subqueries recursively
     *
     * @param  string $clause
     * @return string   SQL string
     * @todo   Description: What is a 'dql clause' (and what not)?
     *       Refactor: Too long & nesting level
     */
    public function parseClause($clause)
    {
        $clause = $this->connection->dataDict->parseBoolean(trim($clause));

        if (is_numeric($clause)) {
            return (string) $clause;
        }

        $terms = $this->tokenizer->clauseExplode($clause, [" ", "+", "-", "*", "/", "<", ">", "=", ">=", "<=", "&", "|"]);
        $str = "";

        foreach ($terms as $term) {
            $pos = strpos($term[0], "(");

            if ($pos !== false && substr($term[0], 0, 1) !== "'") {
                $name = substr($term[0], 0, $pos);

                $term[0] = $this->parseFunctionExpression($term[0]);
            } else {
                if (substr($term[0], 0, 1) !== "'" && substr($term[0], -1) !== "'") {
                    if (strpos($term[0], ".") !== false) {
                        if (!is_numeric($term[0])) {
                            $e = explode(".", $term[0]);

                            $field = array_pop($e);

                            if ($this->getType()->isSelect()) {
                                $componentAlias = implode(".", $e);

                                if (empty($componentAlias)) {
                                    $componentAlias = $this->getRootAlias();
                                }

                                $this->load($componentAlias);

                                // check the existence of the component alias
                                if (!isset($this->queryComponents[$componentAlias])) {
                                    throw new Query\Exception("Unknown component alias " . $componentAlias);
                                }

                                $table = $this->queryComponents[$componentAlias]["table"];

                                $def = $table->getDefinitionOf($field);

                                // get the actual field name from alias
                                $field = $table->getColumnName($field);

                                // check column existence
                                if (!$def) {
                                    throw new Query\Exception("Unknown column " . $field);
                                }

                                if ($def->owner !== null) {
                                    $componentAlias = "$componentAlias.{$def->owner}";
                                }

                                $tableAlias = $this->getSqlTableAlias($componentAlias);

                                // build sql expression
                                $term[0] = $this->connection->quoteIdentifier($tableAlias) . "." . $this->connection->quoteIdentifier($field);
                            } else {
                                // build sql expression
                                $field = $this->getRoot()->getColumnName($field);
                                $term[0] = $this->connection->quoteIdentifier($field);
                            }
                        }
                    } else {
                        if (
                            !empty($term[0]) &&
                            !in_array(strtoupper($term[0]), self::$keywords) &&
                            !is_numeric($term[0]) &&
                            $term[0] !== "?" &&
                            substr($term[0], 0, 1) !== ":"
                        ) {
                            $componentAlias = $this->getRootAlias();

                            $found = false;
                            $table = $this->queryComponents[$componentAlias]["table"];

                            // check column existence
                            if ($table->hasField($term[0])) {
                                $found = true;

                                $def = $table->getDefinitionOf($term[0]);

                                // get the actual column name from field name
                                $term[0] = $table->getColumnName($term[0]);

                                if ($def?->owner !== null) {
                                    $componentAlias = "$componentAlias.{$def->owner}";
                                }

                                $tableAlias = $this->getSqlTableAlias($componentAlias);

                                if ($this->getType()->isSelect()) {
                                    // build sql expression
                                    $term[0] = $this->connection->quoteIdentifier($tableAlias) . "." . $this->connection->quoteIdentifier($term[0]);
                                } else {
                                    // build sql expression
                                    $term[0] = $this->connection->quoteIdentifier($term[0]);
                                }
                            } else {
                                $found = false;
                            }

                            if (!$found) {
                                $term[0] = $this->getSqlAggregateAlias($term[0]);
                            }
                        }
                    }
                }
            }

            $str .= $term[0] . $term[1];
        }
        return $str;
    }

    /**
     * @param  string $expr
     * @return void
     */
    public function parseIdentifierReference($expr)
    {
    }

    /**
     * @param  string   $expr
     * @param  callable $parseCallback
     * @return string
     */
    public function parseFunctionExpression($expr, $parseCallback = null)
    {
        $pos = strpos($expr, "(");
        $name = $pos === false ? "" : substr($expr, 0, $pos);

        if ($name === "") {
            return $this->parseSubquery($expr);
        }

        $argStr = substr($expr, $pos + 1, -1);
        $args = [];
        // parse args

        foreach ($this->tokenizer->sqlExplode($argStr, ",") as $arg) {
            $args[] = $parseCallback ? call_user_func_array($parseCallback, [$arg]) : $this->parseClause($arg);
        }

        // convert DQL function to its RDBMS specific equivalent
        try {
            $callback = [$this->connection->expression, $name];
            if (!is_callable($callback)) {
                throw new Query\Exception("Unknown function " . $name . ".");
            }
            $expr = call_user_func_array($callback, $args);
        } catch (Expression\Exception $e) {
            throw new Query\Exception("Unknown function $name.", previous: $e);
        }

        return $expr;
    }

    /**
     * @param  string $subquery
     * @return string
     */
    public function parseSubquery($subquery)
    {
        $trimmed = trim($this->tokenizer->bracketTrim($subquery));

        // check for possible subqueries
        if (substr($trimmed, 0, 4) == "FROM" || substr($trimmed, 0, 6) == "SELECT") {
            // parse subquery
            $q = $this->createSubquery()->parseDqlQuery($trimmed);
            $trimmed = $q->getSqlQuery();
            $q->free();
        } elseif (substr($trimmed, 0, 4) == "SQL:") {
            $trimmed = substr($trimmed, 4);
        } else {
            $e = $this->tokenizer->sqlExplode($trimmed, ",");

            $value = [];
            $index = false;

            foreach ($e as $part) {
                $value[] = $this->parseClause($part);
            }

            $trimmed = implode(", ", $value);
        }

        return "(" . $trimmed . ")";
    }

    /**
     * processes pending subqueries
     *
     * subqueries can only be processed when the query is fully constructed
     * since some subqueries may be correlated
     *
     * @return void
     * @todo   Better description. i.e. What is a 'pending subquery'? What does 'processed' mean?
     *       (parsed? sql is constructed? some information is gathered?)
     */
    public function processPendingSubqueries()
    {
        foreach ($this->pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $subquery = $this->createSubquery();

            $sql = $subquery->parseDqlQuery($dql, false)->getSqlQuery();
            $subquery->free();

            reset($this->queryComponents);
            $componentAlias = key($this->queryComponents);
            if ($componentAlias === null) {
                throw new Query\Exception("Missing component alias");
            }
            $tableAlias = $this->getSqlTableAlias($componentAlias);

            $sqlAlias = $tableAlias . "__" . count($this->aggregateAliasMap);

            $this->sqlParts["select"][] = "(" . $sql . ") AS " . $this->connection->quoteIdentifier($sqlAlias);

            $this->aggregateAliasMap[$alias] = $sqlAlias;
            $this->queryComponents[$componentAlias]["agg"][] = $alias;
        }
        $this->pendingSubqueries = [];
    }

    /**
     * processes pending aggregate values for given component alias
     *
     * @return void
     * @todo   Better description. i.e. What is a 'pending aggregate'? What does 'processed' mean?
     */
    public function processPendingAggregates()
    {
        // iterate trhough all aggregates
        foreach ($this->pendingAggregates as $aggregate) {
            list($expression, $components, $alias) = $aggregate;

            $tableAliases = [];

            // iterate through the component references within the aggregate function
            if (!empty($components)) {
                foreach ($components as $component) {
                    if (is_numeric($component)) {
                        continue;
                    }

                    $e = explode(".", $component);

                    $field = array_pop($e);
                    $componentAlias = implode(".", $e);

                    // check the existence of the component alias
                    if (!isset($this->queryComponents[$componentAlias])) {
                        throw new Query\Exception("Unknown component alias " . $componentAlias);
                    }

                    $table = $this->queryComponents[$componentAlias]["table"];

                    $field = $table->getColumnName($field);

                    // check column existence
                    if (!$table->hasColumn($field)) {
                        throw new Query\Exception("Unknown column " . $field);
                    }

                    $sqlTableAlias = $this->getSqlTableAlias($componentAlias);

                    $tableAliases[$sqlTableAlias] = true;

                    // build sql expression

                    $identifier = $this->connection->quoteIdentifier($sqlTableAlias . "." . $field);
                    $expression = str_replace($component, $identifier, $expression);
                }
            }

            if (count($tableAliases) !== 1) {
                $componentAlias = reset($this->tableAliasMap);
                $tableAlias = key($this->tableAliasMap);
            }

            if (empty($componentAlias)) {
                throw new Query\Exception("Missing component alias");
            }

            if (empty($tableAlias)) {
                throw new Query\Exception("Missing table alias");
            }

            $index = count($this->aggregateAliasMap);
            $sqlAlias = $this->connection->quoteIdentifier($tableAlias . "__" . $index);

            $this->sqlParts["select"][] = $expression . " AS " . $sqlAlias;

            $this->aggregateAliasMap[$alias] = $sqlAlias;
            $this->expressionMap[$alias][0] = $expression;

            $this->queryComponents[$componentAlias]["agg"][$index] = $alias;

            $this->neededTables[] = $tableAlias;
        }
        // reset the state
        $this->pendingAggregates = [];
    }

    /**
     * returns the base of the generated sql query
     * On mysql driver special strategy has to be used for DELETE statements
     * (where is this special strategy??)
     *
     * @return string       the base of the generated sql query
     */
    protected function buildSqlQueryBase()
    {
        if ($this->type->isDelete()) {
            return "DELETE FROM ";
        }
        if ($this->type->isUpdate()) {
            return "UPDATE ";
        }
        if ($this->type->isSelect()) {
            $distinct = $this->sqlParts["distinct"] ? "DISTINCT " : "";
            return "SELECT " . $distinct . implode(", ", $this->sqlParts["select"]) . " FROM ";
        }
        return "";
    }

    /**
     * builds the from part of the query and returns it
     *
     * @param  bool $ignorePending
     * @return string   the query sql from part
     */
    protected function buildSqlFromPart($ignorePending = false)
    {
        $q = "";

        foreach ($this->sqlParts["from"] as $k => $part) {
            $e = explode(" ", $part);

            if ($k === 0) {
                if (!$ignorePending && $this->type->isSelect()) {
                    // We may still have pending conditions
                    $alias = count($e) > 1 ? $this->getComponentAlias($e[1]) : null;
                    $where = $this->processPendingJoinConditions($alias);

                    // apply inheritance to WHERE part
                    if (!empty($where)) {
                        if (count($this->sqlParts["where"]) > 0) {
                            $this->sqlParts["where"][] = "AND";
                        }

                        if (substr($where, 0, 1) === "(" && substr($where, -1) === ")") {
                            $this->sqlParts["where"][] = $where;
                        } else {
                            $this->sqlParts["where"][] = "(" . $where . ")";
                        }
                    }
                }

                $q .= $part;

                continue;
            }

            // preserve LEFT JOINs only if needed
            // Check if it's JOIN, if not add a comma separator instead of space
            if (!preg_match("/\bJOIN\b/i", $part) && !isset($this->pendingJoinConditions[$k])) {
                $q .= ", " . $part;
            } else {
                if (substr($part, 0, 9) === "LEFT JOIN") {
                    $aliases = array_merge($this->subqueryAliases, array_keys($this->neededTables));

                    if (!in_array($e[3], $aliases) && !in_array($e[2], $aliases) && !empty($this->pendingFields)) {
                        continue;
                    }
                }

                if (!$ignorePending && isset($this->pendingJoinConditions[$k])) {
                    if (strpos($part, " ON ") !== false) {
                        $part .= " AND ";
                    } else {
                        $part .= " ON ";
                    }

                    $part .= $this->processPendingJoinConditions($k);
                }

                $componentAlias = $this->getComponentAlias($e[3]);
                $string = $this->getInheritanceCondition($componentAlias);

                if ($string) {
                    $part = $part . " AND " . $string;
                }
                $q .= " " . $part;
            }

            $this->sqlParts["from"][$k] = $part;
        }
        return $q;
    }

    /**
     * Processes the pending join conditions, used for dynamically add conditions
     * to root component/joined components without interfering in the main dql
     * handling.
     *
     * @param  string|null $alias Component Alias
     * @return string Processed pending conditions
     */
    protected function processPendingJoinConditions(?string $alias)
    {
        $parts = [];

        if ($alias !== null && isset($this->pendingJoinConditions[$alias])) {
            $parser = new Query\JoinCondition($this, $this->tokenizer);

            foreach ($this->pendingJoinConditions[$alias] as $joinCondition) {
                $parts[] = $parser->parse($joinCondition);
            }
        }

        return count($parts) > 0 ? "(" . implode(") AND (", $parts) . ")" : "";
    }

    /**
     * builds the sql query from the given parameters and applies things such as
     * column aggregation inheritance and limit subqueries if needed
     *
     * @param  mixed[] $params        an array of prepared statement params (needed only in mysql driver
     *                                when limit subquery algorithm is used)
     * @param  bool    $limitSubquery Whether or not to try and apply the limit subquery algorithm
     */
    public function getSqlQuery(array $params = [], bool $limitSubquery = true): string
    {
        // Assign building/execution specific params
        $this->params["exec"] = $params;

        // Initialize prepared parameters array
        $this->execParams = $this->getFlattenedParams();

        if ($this->state !== State::Dirty) {
            $this->fixArrayParameterValues($this->getInternalParams());

            // Return compiled SQL
            return $this->sql;
        }
        return $this->buildSqlQuery($limitSubquery);
    }

    /**
     * Build the SQL query from the DQL
     *
     * @param  bool $limitSubquery Whether or not to try and apply the limit subquery algorithm
     */
    public function buildSqlQuery(bool $limitSubquery = true): string
    {
        // reset the state
        if (!$this->isSubquery()) {
            $this->queryComponents = [];
            $this->pendingAggregates = [];
            $this->aggregateAliasMap = [];
        }

        $this->reset();

        // invoke the preQuery hook
        $this->invokePreQuery();

        // process the DQL parts => generate the SQL parts.
        // this will also populate the $queryComponents.
        foreach ($this->dqlParts as $queryPartName => $queryParts) {
            // If we are parsing FROM clause, we'll need to diff the queryComponents later
            if ($queryPartName === "from") {
                // Pick queryComponents before processing
                $queryComponentsBefore = $this->getQueryComponents();
            }

            // FIX #1667: _sqlParts are cleaned inside _processDqlQueryPart.
            if (!in_array($queryPartName, ["forUpdate", "forShare", "noWait", "skipLocked"], true) && is_array($queryParts)) {
                $this->processDqlQueryPart($queryPartName, $queryParts);
            }

            // We need to define the root alias
            if ($queryPartName === "from") {
                // Pick queryComponents aftr processing
                $queryComponentsAfter = $this->getQueryComponents();

                // Root alias is the key of difference of query components
                $diffQueryComponents = array_diff_key($queryComponentsAfter, $queryComponentsBefore ?? []);
                if (empty($diffQueryComponents)) {
                    throw new Query\Exception("Missing root alias");
                }
                $this->rootAlias = key($diffQueryComponents);
            }
        }
        $this->state = State::Clean;

        // Proceed with the generated SQL
        if (empty($this->sqlParts["from"])) {
            throw new Query\Exception("Missing from part.");
        }

        $needsSubQuery = false;
        $subquery = "";
        $map = $this->getRootDeclaration();
        $table = $map["table"];
        $rootAlias = $this->getRootAlias();

        if (!empty($this->sqlParts["limit"]) && $this->needsSubquery && $table->getLimit() == Limit::Records) {
            // We do not need a limit-subquery if DISTINCT is used
            // and the selected fields are either from the root component or from a localKey relation (hasOne)
            // (i.e. DQL: SELECT DISTINCT u.id FROM User u LEFT JOIN u.phonenumbers LIMIT 5).
            if (!$this->sqlParts["distinct"]) {
                $this->isLimitSubqueryUsed = true;
                $needsSubQuery = true;
            } else {
                foreach (array_keys($this->pendingFields) as $alias) {
                    //no subquery for root fields
                    if ($alias == $this->getRootAlias()) {
                        continue;
                    }

                    //no subquery for ONE relations
                    if (isset($this->queryComponents[$alias]["relation"]) && $this->queryComponents[$alias]["relation"]->getType() == Relation::ONE) {
                        continue;
                    }

                    $this->isLimitSubqueryUsed = true;
                    $needsSubQuery = true;
                }
            }
        }

        $sql = [];

        if (!empty($this->pendingFields)) {
            foreach ($this->queryComponents as $alias => $map) {
                $fieldSql = $this->processPendingFields($alias);
                if (!empty($fieldSql)) {
                    $sql[] = $fieldSql;
                }
            }
        }

        if (!empty($sql)) {
            array_unshift($this->sqlParts["select"], implode(", ", $sql));
        }

        $this->pendingFields = [];

        // build the basic query
        $q = $this->buildSqlQueryBase();
        $q .= $this->buildSqlFromPart();

        if (!empty($this->sqlParts["set"])) {
            $q .= " SET " . implode(", ", $this->sqlParts["set"]);
        }

        $string = $this->getInheritanceCondition($this->getRootAlias());

        // apply inheritance to WHERE part
        if (!empty($string)) {
            if (count($this->sqlParts["where"]) > 0) {
                $this->sqlParts["where"][] = "AND";
            }

            if (substr($string, 0, 1) === "(" && substr($string, -1) === ")") {
                $this->sqlParts["where"][] = $string;
            } else {
                $this->sqlParts["where"][] = "($string)";
            }
        }

        $modifyLimit = true;
        $limitSubquerySql = "";

        if ((!empty($this->sqlParts["limit"]) || !empty($this->sqlParts["offset"])) && $needsSubQuery && $limitSubquery) {
            $subquery = $this->getLimitSubquery();

            // what about composite keys?
            $idColumnName = $table->getColumnName($table->getIdentifier());

            // pgsql/mysql need special nested LIMIT subquery
            if ($this->connection instanceof Mysql || $this->connection instanceof Pgsql) {
                $subqueryAlias = $this->connection->quoteIdentifier("doctrine_subquery_alias");
                $subquery = "SELECT $subqueryAlias.{$this->connection->quoteIdentifier($idColumnName)} FROM ($subquery) AS $subqueryAlias";
            }

            $field = $this->getSqlTableAlias($rootAlias) . "." . $idColumnName;

            // FIX #1868: If not ID under MySQL is found to be restricted, restrict pk column for null
            //            (which will lead to a return of 0 items)
            $limitSubquerySql = $this->connection->quoteIdentifier($field) . (empty($subquery) ? " IS NULL" : " IN ($subquery)");

            $modifyLimit = false;
        }

        // condition is already in the subquery, skip second where
        if (!empty($limitSubquerySql)) {
            $q .= " WHERE $limitSubquerySql";
        } elseif (!empty($this->sqlParts["where"]) && !empty($limitSubquery)) {
            $where = implode(" ", $this->sqlParts["where"]);
            if (!empty($where) && !(substr($where, 0, 1) === "(" && substr($where, -1) === ")")) {
                $where = "($where)";
            }
            $q .= " WHERE {$limitSubquerySql}{$where}";
        }

        // Fix the orderbys so we only have one orderby per value
        foreach ($this->sqlParts["orderby"] as $k => $orderBy) {
            $e = explode(", ", $orderBy);
            unset($this->sqlParts["orderby"][$k]);
            foreach ($e as $v) {
                $this->sqlParts["orderby"][] = $v;
            }
        }

        // Add the default orderBy statements defined in the relationships and table classes
        // Only do this for SELECT queries
        if ($this->type->isSelect()) {
            foreach ($this->queryComponents as $alias => $map) {
                $sqlAlias = $this->getSqlTableAlias($alias);
                if (isset($map["relation"])) {
                    $orderBy = $map["relation"]->getOrderByStatement($sqlAlias, true);
                    if ($orderBy == $map["relation"]["orderBy"]) {
                        if (isset($map["ref"]) && isset($map["relation"]["refTable"])) {
                            $orderBy = $map["relation"]["refTable"]->processOrderBy($sqlAlias, $map["relation"]["orderBy"] ?? [], true);
                        } else {
                            $orderBy = null;
                        }
                    }
                } else {
                    $orderBy = $map["table"]->getOrderByStatement($sqlAlias, true);
                }

                if ($orderBy) {
                    $e = explode(",", $orderBy);
                    $e = array_map("trim", $e);
                    foreach ($e as $v) {
                        if (!in_array($v, $this->sqlParts["orderby"])) {
                            $this->sqlParts["orderby"][] = $v;
                        }
                    }
                }
            }
        }

        $q .= empty($this->sqlParts["groupby"]) ? "" : " GROUP BY " . implode(", ", $this->sqlParts["groupby"]);
        $q .= empty($this->sqlParts["having"]) ? "" : " HAVING " . implode(" AND ", $this->sqlParts["having"]);
        $q .= empty($this->sqlParts["orderby"]) ? "" : " ORDER BY " . implode(", ", $this->sqlParts["orderby"]);

        if ($modifyLimit) {
            $q = $this->connection->modifyLimitQuery($q, $this->sqlParts["limit"], $this->sqlParts["offset"], false);
        }

        if ($this->sqlParts["forUpdate"]) {
            $q .= " FOR UPDATE";
        } elseif ($this->sqlParts["forShare"]) {
            $q .= " FOR SHARE";
        }

        if ($this->sqlParts["noWait"]) {
            $q .= " NOWAIT";
        } elseif ($this->sqlParts["skipLocked"]) {
            $q .= " SKIP LOCKED";
        }

        $this->sql = $q;

        $this->clear();

        return $q;
    }

    /**
     * this is method is used by the record limit algorithm
     *
     * when fetching one-to-many, many-to-many associated data with LIMIT clause
     * an additional subquery is needed for limiting the number of returned records instead
     * of limiting the number of sql result set rows
     *
     * @return string       the limit subquery
     * @todo   A little refactor to make the method easier to understand & maybe shorter?
     */
    public function getLimitSubquery()
    {
        $map = reset($this->queryComponents);
        if ($map === false) {
            throw new Query\Exception("Missing table component");
        }

        $table = $map["table"];
        $componentAlias = key($this->queryComponents);

        if ($componentAlias === null) {
            throw new Query\Exception("Missing component alias");
        }

        // get short alias
        $alias = $this->getSqlTableAlias($componentAlias);
        // what about composite keys?
        $primaryKey = $alias . "." . $table->getColumnName($table->getIdentifier());

        // initialize the base of the subquery
        $subquery = "SELECT DISTINCT " . $this->connection->quoteIdentifier($primaryKey);

        // pgsql need the order by fields to be preserved in select clause
        // Technically this isn't required for mysql <= 5.6, but mysql 5.7 with an sql_mode option enabled
        // (only_full_group_by, which is enabled by default in 5.7) will throw SQL errors if this isn't done,
        // so easier to just enable for all of mysql.
        if ($this->connection instanceof Mysql || $this->connection instanceof Pgsql) {
            foreach ($this->sqlParts["orderby"] as $part) {
                // Remove identifier quoting if it exists
                $e = $this->tokenizer->bracketExplode($part, " ");
                foreach ($e as $f) {
                    $partOriginal = str_replace(",", "", trim($f));
                    $part = trim(implode(".", array_map(fn($e) => trim($e, '[]`"'), explode(".", $partOriginal))));

                    if (strpos($part, ".") === false) {
                        continue;
                    }

                    // don't add functions
                    if (strpos($part, "(") !== false) {
                        continue;
                    }

                    // don't add primarykey column (its already in the select clause)
                    if ($part !== $primaryKey) {
                        $subquery .= ", " . $partOriginal;
                    }
                }
            }
        }

        $orderby = $this->sqlParts["orderby"];
        $having = $this->sqlParts["having"];
        if ($this->connection instanceof Mysql || $this->connection instanceof Pgsql) {
            foreach ($this->expressionMap as $dqlAlias => $expr) {
                if (isset($expr[1])) {
                    $subquery .= ", " . $expr[0] . " AS " . $this->aggregateAliasMap[$dqlAlias];
                }
            }
        } else {
            foreach ($this->expressionMap as $dqlAlias => $expr) {
                if (isset($expr[1])) {
                    foreach ($having as $k => $v) {
                        $having[$k] = str_replace($this->aggregateAliasMap[$dqlAlias], $expr[0], $v);
                    }
                    foreach ($orderby as $k => $v) {
                        $e = explode(" ", $v);
                        if ($e[0] == $this->aggregateAliasMap[$dqlAlias]) {
                            $orderby[$k] = $expr[0];
                        }
                    }
                }
            }
        }

        // Add having fields that got stripped out of select
        preg_match_all("/`[a-z0-9_]+`\.`[a-z0-9_]+`/i", implode(" ", $having), $matches, PREG_PATTERN_ORDER);
        if (count($matches[0]) > 0) {
            $subquery .= ", " . implode(", ", array_unique($matches[0]));
        }

        $subquery .= " FROM";

        foreach ($this->sqlParts["from"] as $part) {
            // preserve LEFT JOINs only if needed
            if (substr($part, 0, 9) === "LEFT JOIN") {
                $e = explode(" ", $part);
                // Fix for http://www.doctrine-project.org/jira/browse/DC-706
                // Fix for http://www.doctrine-project.org/jira/browse/DC-594
                if (
                    empty($this->sqlParts["orderby"]) &&
                    empty($this->sqlParts["where"]) &&
                    empty($this->sqlParts["having"]) &&
                    empty($this->sqlParts["groupby"])
                ) {
                    continue;
                }
            }

            $subquery .= " " . $part;
        }

        // all conditions are preserved in the subquery
        $subquery .= empty($this->sqlParts["where"]) ? "" : " WHERE " . implode(" ", $this->sqlParts["where"]);
        $subquery .= empty($this->sqlParts["groupby"]) ? "" : " GROUP BY " . implode(", ", $this->sqlParts["groupby"]);
        $subquery .= empty($having) ? "" : " HAVING " . implode(" AND ", $having);
        $subquery .= empty($orderby) ? "" : " ORDER BY " . implode(", ", $orderby);

        // add driver specific limit clause
        $subquery = $this->connection->modifyLimitSubquery($table, $subquery, $this->sqlParts["limit"], $this->sqlParts["offset"]);

        $parts = $this->tokenizer->quoteExplode($subquery, " ");

        foreach ($parts as $k => $part) {
            if (strpos($part, " ") !== false) {
                continue;
            }

            $part = str_replace(['"', "'", "`"], "", $part);

            // Fix DC-645, Table aliases ending with ')' where not replaced properly
            preg_match('/^(\(?)(.*?)(\)?)$/', $part, $matches);
            if ($this->hasSqlTableAlias($matches[2])) {
                $parts[$k] = $matches[1] . $this->connection->quoteIdentifier($this->generateNewSqlTableAlias($matches[2])) . $matches[3];
                continue;
            }

            if (strpos($part, ".") === false) {
                continue;
            }

            preg_match_all("/[a-zA-Z0-9_]+\.[a-z0-9_]+/i", $part, $m);

            foreach ($m[0] as $match) {
                $e = explode(".", $match);

                // Rebuild the original part without the newly generate alias and with quoting reapplied
                $e2 = [];
                foreach ($e as $k2 => $v2) {
                    $e2[$k2] = $this->connection->quoteIdentifier($v2);
                }
                $match = implode(".", $e2);

                // Generate new table alias
                $e[0] = $this->generateNewSqlTableAlias($e[0]);

                // Requote the part with the newly generated alias
                foreach ($e as $k2 => $v2) {
                    $e[$k2] = $this->connection->quoteIdentifier($v2);
                }

                $replace = implode(".", $e);

                // Replace the original part with the new part with new sql table alias
                $parts[$k] = str_replace($match, $replace, $parts[$k]);
            }
        }

        if ($this->connection instanceof Mysql || $this->connection instanceof Pgsql) {
            foreach ($parts as $k => $part) {
                if (strpos($part, "'") !== false) {
                    continue;
                }
                if (strpos($part, "__") == false) {
                    continue;
                }

                preg_match_all("/[a-zA-Z0-9_]+\_\_[a-z0-9_]+/i", $part, $m);

                foreach ($m[0] as $match) {
                    $e = explode("__", $match);
                    $e[0] = $this->generateNewSqlTableAlias($e[0]);

                    $parts[$k] = str_replace($match, implode("__", $e), $parts[$k]);
                }
            }
        }

        $subquery = implode(" ", $parts);
        return $subquery;
    }

    /**
     * DQL PARSER
     * parses a DQL query
     * first splits the query in parts and then uses individual
     * parsers for each part
     *
     * @param  string  $query DQL query
     * @param  boolean $clear whether or not to clear the aliases
     * @throws Query\Exception     if some generic parsing error occurs
     * @return $this
     */
    public function parseDqlQuery($query, $clear = true)
    {
        if ($clear) {
            $this->clear();
        }

        $query = trim($query);
        $query = str_replace("\r", "\n", str_replace("\r\n", "\n", $query));
        $query = str_replace("\n", " ", $query);

        $parts = $this->tokenizer->tokenizeQuery($query);

        foreach ($parts as $partName => $subParts) {
            $subParts = trim($subParts);
            $partName = strtolower($partName);
            switch ($partName) {
                case "create":
                    $this->type = Query\Type::CREATE();
                    break;
                case "insert":
                    $this->type = Query\Type::INSERT();
                    break;
                case "delete":
                    $this->type = Query\Type::DELETE();
                    break;
                case "select":
                    $this->type = Query\Type::SELECT();
                    $this->addDqlQueryPart($partName, $subParts);
                    break;
                case "update":
                    $this->type = Query\Type::UPDATE();
                    $partName = "from";
                // no break
                case "from":
                    $this->addDqlQueryPart($partName, $subParts);
                    break;
                case "set":
                    $this->addDqlQueryPart($partName, $subParts, true);
                    break;
                case "group":
                case "order":
                    $partName .= "by";
                // no break
                case "where":
                case "having":
                case "limit":
                case "offset":
                    $this->addDqlQueryPart($partName, $subParts);
                    break;
            }
        }

        return $this;
    }

    /**
     * @todo   Describe & refactor... too long and nested.
     * @param  string  $path       component alias
     * @param  boolean $loadFields
     * @return array
     */
    public function load($path, $loadFields = true)
    {
        if (isset($this->queryComponents[$path])) {
            return $this->queryComponents[$path];
        }

        $e = $this->tokenizer->quoteExplode($path, " INDEXBY ");

        $mapWith = null;
        if (count($e) > 1) {
            $mapWith = trim($e[1]);

            $path = $e[0];
        }

        // parse custom join conditions
        $e = explode(" ON ", str_ireplace(" on ", " ON ", $path));

        $joinCondition = "";

        if (count($e) > 1) {
            $joinCondition = substr($path, strlen($e[0]) + 4, strlen($e[1]));
            $path = substr($path, 0, strlen($e[0]));

            $overrideJoin = true;
        } else {
            $e = explode(" WITH ", str_ireplace(" with ", " WITH ", $path));

            if (count($e) > 1) {
                $joinCondition = substr($path, strlen($e[0]) + 6, strlen($e[1]));
                $path = substr($path, 0, strlen($e[0]));
            }

            $overrideJoin = false;
        }

        $tmp = explode(" ", $path);
        $componentAlias = $originalAlias = count($tmp) > 1 ? end($tmp) : null;

        $e = preg_split("/[.:]/", $tmp[0], -1) ?: [$tmp[0]];

        $fullPath = $tmp[0];
        $parent = "";
        $prevPath = "";
        $fullLength = strlen($fullPath);

        if (isset($this->queryComponents[$e[0]])) {
            $table = $this->queryComponents[$e[0]]["table"];
            $componentAlias = $e[0];

            $prevPath = $parent = array_shift($e);
        }

        foreach ($e as $key => $name) {
            // get length of the previous path
            $length = strlen($prevPath);

            // build the current component path
            $prevPath = $prevPath ? $prevPath . "." . $name : $name;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($prevPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $prevPath;
            }

            // if the current alias already exists, skip it
            if (isset($this->queryComponents[$componentAlias])) {
                throw new Query\Exception("Duplicate alias '$componentAlias' in query.");
            }

            if (!isset($table)) {
                // process the root of the path

                $table = $this->loadRoot($name, $componentAlias);
            } else {
                $join = $delimeter == ":" ? "INNER JOIN " : "LEFT JOIN ";

                $relation = $table->getRelation($name);
                $localTable = $table;

                $table = $relation->getTable();
                $this->queryComponents[$componentAlias] = [
                    "table" => $table,
                    "parent" => $parent,
                    "relation" => $relation,
                    "map" => null,
                ];
                // Fix for http://www.doctrine-project.org/jira/browse/DC-701
                if (!$relation->isOneToOne() && !$this->disableLimitSubquery) {
                    $this->needsSubquery = true;
                }

                $localAlias = $this->getSqlTableAlias($parent, $localTable->getTableName());
                $foreignAlias = $this->getSqlTableAlias($componentAlias, $relation->getTable()->getTableName());

                $foreignSql =
                    $this->connection->quoteIdentifier($relation->getTable()->getTableName()) . " " . $this->connection->quoteIdentifier($foreignAlias);

                $map = $relation->getTable()->inheritanceMap;

                if (!$loadFields || !empty($map) || $joinCondition) {
                    $this->subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof Relation\Association) {
                    $asf = $relation->getAssociationTable();

                    $assocTableName = $asf->getTableName();

                    if (!$loadFields || !empty($map) || $joinCondition) {
                        $this->subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . "." . $asf->getComponentName() . " " . $componentAlias;

                    $this->queryComponents[$assocPath] = [
                        "parent" => $prevPath,
                        "relation" => $relation,
                        "table" => $asf,
                        "ref" => true,
                    ];

                    $assocAlias = $this->getSqlTableAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $this->connection->quoteIdentifier($assocTableName) . " " . $this->connection->quoteIdentifier($assocAlias);

                    $queryPart .=
                        " ON (" .
                        $this->connection->quoteIdentifier($localAlias . "." . $localTable->getColumnName($localTable->getIdentifier())) . // what about composite keys?
                        " = " .
                        $this->connection->quoteIdentifier($assocAlias . "." . $relation->getLocalRefColumnName());

                    if ($relation->isEqual()) {
                        // equal nest relation needs additional condition
                        $queryPart .=
                            " OR " .
                            $this->connection->quoteIdentifier($localAlias . "." . $table->getColumnName($table->getIdentifier())) .
                            " = " .
                            $this->connection->quoteIdentifier($assocAlias . "." . $relation->getForeignRefColumnName());
                    }

                    $queryPart .= ")";

                    $this->sqlParts["from"][] = $queryPart;

                    $queryPart = $join . $foreignSql;

                    if (!$overrideJoin) {
                        $queryPart .= $this->buildAssociativeRelationSql($relation, $assocAlias, $foreignAlias, $localAlias);
                    }
                } else {
                    $queryPart = $this->buildSimpleRelationSql($relation, $foreignAlias, $localAlias, $overrideJoin, $join);
                }

                $this->sqlParts["from"][$componentAlias] = $queryPart;

                if (!empty($joinCondition)) {
                    $this->addPendingJoinCondition($componentAlias, $joinCondition);
                }
            }

            if ($loadFields) {
                $restoreState = false;

                // load fields if necessary
                if (empty($this->dqlParts["select"])) {
                    $this->pendingFields[$componentAlias] = ["*"];
                }
            }

            $parent = $prevPath;
        }

        if ($componentAlias === null) {
            throw new Query\Exception("Missing component alias");
        }

        $table = $this->queryComponents[$componentAlias]["table"];

        return $this->buildIndexBy($componentAlias, $mapWith);
    }

    /**
     * @param  string $foreignAlias
     * @param  string $localAlias
     * @param  bool   $overrideJoin
     * @param  string $join
     * @return string
     */
    protected function buildSimpleRelationSql(Relation $relation, $foreignAlias, $localAlias, $overrideJoin, $join)
    {
        $queryPart =
            $join . $this->connection->quoteIdentifier($relation->getTable()->getTableName()) . " " . $this->connection->quoteIdentifier($foreignAlias);

        if (!$overrideJoin) {
            $queryPart .=
                " ON " .
                $this->connection->quoteIdentifier($localAlias . "." . $relation->getLocalColumnName()) .
                " = " .
                $this->connection->quoteIdentifier($foreignAlias . "." . $relation->getForeignColumnName());
        }

        return $queryPart;
    }

    /**
     * @param  string $componentAlias
     * @param  string $mapWith
     * @return array
     */
    protected function buildIndexBy($componentAlias, $mapWith = null)
    {
        $table = $this->queryComponents[$componentAlias]["table"];

        $indexBy = null;
        $column = false;

        if (isset($mapWith)) {
            $terms = explode(".", $mapWith);

            if (count($terms) == 1) {
                $indexBy = $terms[0];
            } elseif (count($terms) == 2) {
                $column = true;
                $indexBy = $terms[1];
            }
        } elseif ($table->getBoundQueryPart("indexBy") !== null) {
            $indexBy = $table->getBoundQueryPart("indexBy");
        }

        if ($indexBy !== null) {
            if ($column && !$table->hasColumn($table->getColumnName($indexBy))) {
                throw new Query\Exception("Couldn't use key mapping. Column " . $indexBy . " does not exist.");
            }

            $this->queryComponents[$componentAlias]["map"] = $indexBy;
        }

        return $this->queryComponents[$componentAlias];
    }

    /**
     * @param Relation\Association $relation
     * @param string                        $assocAlias
     * @param string                        $foreignAlias
     * @param string                        $localAlias
     *
     * @return string
     */
    protected function buildAssociativeRelationSql(Relation $relation, $assocAlias, $foreignAlias, $localAlias)
    {
        $table = $relation->getTable();

        $queryPart = " ON ";

        if ($relation->isEqual()) {
            $queryPart .= "(";
        }

        $localIdentifier = $table->getColumnName($table->getIdentifier());

        $queryPart .=
            $this->connection->quoteIdentifier($foreignAlias . "." . $localIdentifier) .
            " = " .
            $this->connection->quoteIdentifier($assocAlias . "." . $relation->getForeignRefColumnName());

        if ($relation->isEqual()) {
            $queryPart .=
                " OR " .
                $this->connection->quoteIdentifier($foreignAlias . "." . $localIdentifier) .
                " = " .
                $this->connection->quoteIdentifier($assocAlias . "." . $relation->getLocalRefColumnName()) .
                ") AND " .
                $this->connection->quoteIdentifier($foreignAlias . "." . $localIdentifier) .
                " != " .
                $this->connection->quoteIdentifier($localAlias . "." . $localIdentifier);
        }

        return $queryPart;
    }

    /**
     * @param  string $name
     * @param  string $componentAlias
     * @return Table
     * @todo   DESCRIBE ME!
     * @todo   this method is called only in Query class. Shouldn't be private or protected?
     */
    public function loadRoot($name, $componentAlias)
    {
        // get the connection for the component
        $manager = Manager::getInstance();
        if (!$this->passedConn && $manager->hasConnectionForComponent($name)) {
            $this->connection = $manager->getConnectionForComponent($name);
        }

        $table = $this->connection->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->getSqlTableAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->connection->quoteIdentifier($tableName);

        if ($this->type->isSelect()) {
            $queryPart .= " " . $this->connection->quoteIdentifier($tableAlias);
        }

        $this->tableAliasMap[$tableAlias] = $componentAlias;

        $this->sqlParts["from"][] = $queryPart;

        $this->queryComponents[$componentAlias] = ["table" => $table, "map" => null];

        return $table;
    }

    /**
     * Get count sql query for this Query instance.
     *
     * This method is used in Query::count() for returning an integer
     * for the number of records which will be returned when executed.
     *
     * @return string $q
     */
    public function getCountSqlQuery()
    {
        // triggers dql parsing/processing
        $this->getSqlQuery([], false); // this is ugly

        // initialize temporary variables
        $where = $this->sqlParts["where"];
        $having = $this->sqlParts["having"];
        $groupby = $this->sqlParts["groupby"];

        $rootAlias = $this->getRootAlias();
        $tableAlias = $this->getSqlTableAlias($rootAlias);

        // Build the query base
        $q = "SELECT COUNT(*) AS " . $this->connection->quoteIdentifier("num_results") . " FROM ";

        // Build the from clause
        $from = $this->buildSqlFromPart(true);

        // Build the where clause
        $where = !empty($where) ? " WHERE " . implode(" ", $where) : "";

        // Build the group by clause
        $groupby = !empty($groupby) ? " GROUP BY " . implode(", ", $groupby) : "";

        // Build the having clause
        $having = !empty($having) ? " HAVING " . implode(" AND ", $having) : "";

        // Building the from clause and finishing query
        if (count($this->queryComponents) == 1 && empty($having)) {
            $q .= $from . $where . $groupby . $having;
        } else {
            // Subselect fields will contain only the pk of root entity
            $ta = $this->connection->quoteIdentifier($tableAlias);

            $map = $this->getRootDeclaration();
            $idColumnNames = $map["table"]->getIdentifierColumnNames();

            $pkFields = $ta . "." . implode(", " . $ta . ".", $this->connection->quoteMultipleIdentifier($idColumnNames));

            // We need to do some magic in select fields if the query contain anything in having clause
            $selectFields = $pkFields;

            if (!empty($having)) {
                // For each field defined in select clause
                foreach ($this->sqlParts["select"] as $field) {
                    // We only include aggregate expressions to count query
                    // This is needed because HAVING clause will use field aliases
                    if (strpos($field, "(") !== false) {
                        $selectFields .= ", " . $field;
                    }
                }
                // Add having fields that got stripped out of select
                preg_match_all("/`[a-z0-9_]+`\.`[a-z0-9_]+`/i", $having, $matches, PREG_PATTERN_ORDER);
                if (count($matches[0]) > 0) {
                    $selectFields .= ", " . implode(", ", array_unique($matches[0]));
                }
            }

            // If we do not have a custom group by, apply the default one
            if (empty($groupby)) {
                $groupby = " GROUP BY " . $pkFields;
            }

            $q .= "(SELECT " . $selectFields . " FROM " . $from . $where . $groupby . $having . ") " . $this->connection->quoteIdentifier("dctrn_count_query");
        }

        return $q;
    }

    /**
     * Fetches the count of the query.
     *
     * This method executes the main query without all the
     * selected fields, ORDER BY part, LIMIT part and OFFSET part.
     *
     * Example:
     * Main query:
     *      SELECT u.*, p.phonenumber FROM User u
     *          LEFT JOIN u.Phonenumber p
     *          WHERE p.phonenumber = '123 123' LIMIT 10
     *
     * The modified DQL query:
     *      SELECT COUNT(DISTINCT u.id) FROM User u
     *          LEFT JOIN u.Phonenumber p
     *          WHERE p.phonenumber = '123 123'
     *
     * @param  mixed[] $params an array of prepared statement parameters
     * @return integer             the count of this query
     */
    public function count($params = []): int
    {
        $q = $this->getCountSqlQuery();
        $params = $this->getCountQueryParams($params);
        $params = $this->connection->convertBooleans($params);
        assert(is_array($params));

        if ($this->resultCache) {
            $conn = $this->getConnection();
            $cacheDriver = $this->getResultCacheDriver();
            $hash = $this->getResultCacheHash($params) . "_count";
            $cached = $this->expireResultCache ? false : $cacheDriver->fetch($hash);

            if ($cached === false) {
                // cache miss
                $results = $this->getConnection()->fetchAll($q, $params);
                $cacheDriver->save($hash, serialize($results), $this->getResultCacheLifeSpan());
            } else {
                $results = unserialize($cached);
            }
        } else {
            $results = $this->getConnection()->fetchAll($q, $params);
        }

        if (count($results) > 1) {
            $count = count($results);
        } else {
            if (isset($results[0])) {
                $results[0] = array_change_key_case($results[0], CASE_LOWER);
                $count = $results[0]["num_results"];
            } else {
                $count = 0;
            }
        }

        return (int) $count;
    }

    /**
     * Queries the database with DQL (Doctrine Query Language).
     *
     * This methods parses a Dql query and builds the query parts.
     *
     * @param  string $query         Dql query
     * @param  array  $params        prepared statement parameters
     * @param  ?HydrationMode    $hydrationMode HydrationMode::Array or HydrationMode::Record
     * @see    PDO::FETCH_* constants
     * @return mixed
     */
    public function query($query, $params = [], ?HydrationMode $hydrationMode = null)
    {
        $this->parseDqlQuery($query);
        return $this->execute($params, $hydrationMode);
    }

    /**
     * Magic method called after cloning process.
     *
     * @return void
     */
    public function __clone()
    {
        $this->parsers = [];
        $this->hydrator = clone $this->hydrator;

        // Subqueries share some information from the parent so it can intermingle
        // with the dql of the main query. So when a subquery is cloned we need to
        // kill those references or it causes problems
        if ($this->isSubquery()) {
            $this->killReference("params");
            $this->killReference("tableAliasMap");
            $this->killReference("queryComponents");
        }
    }

    /**
     * Kill the reference for the passed class property.
     * This method simply copies the value to a temporary variable and then unsets
     * the reference and re-assigns the old value but not by reference
     *
     * @param string $key
     *
     * @return void
     */
    protected function killReference($key)
    {
        $tmp = $this->$key;
        unset($this->$key);
        $this->$key = $tmp;
    }

    /**
     * Frees the resources used by the query object. It especially breaks a
     * cyclic reference between the query object and it's parsers. This enables
     * PHP's current GC to reclaim the memory.
     * This method can therefore be used to reduce memory usage when creating
     * a lot of query objects during a request.
     */
    public function free(): void
    {
        $this->reset();
        $this->parsers = [];
        $this->dqlParts = [];
    }
}
