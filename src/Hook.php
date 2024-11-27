<?php

namespace Doctrine1;

class Hook
{
    /**
     * @var Query $query           the base query
     */
    protected $query;

    /**
     * @var array $joins                    the optional joins of the base query
     */
    protected $joins;

    /**
     * @var array $hooks                    hooks array
     */
    protected $hooks = [
                             'where',
                             'orderby',
                             'limit',
                             'offset'
                              ];

    /**
     * @var array $fieldParsers             custom field parsers array
     *                                      keys as field names in the format componentAlias.FieldName
     *                                      values as parser names / objects
     */
    protected $fieldParsers = [];

    /**
     * @var array $typeParsers              type parsers array
     *                                      keys as type names and values as parser names / objects
     * @phpstan-var array<string, class-string<Hook\Parser>|Hook\Parser>
     */
    protected $typeParsers = [
        'string'  => Hook\WordLike::class,
        'integer' => Hook\Integer::class,
        'enum'    => Hook\Integer::class,
    ];

    /**
     * @param Query|string $query the base query
     */
    public function __construct($query)
    {
        if (is_string($query)) {
            $this->query = Query::create();
            $this->query->parseDqlQuery($query);
        } elseif ($query instanceof Query) {
            $this->query = $query;
        } else {
            throw new Exception('Constructor argument should be either Query object or valid DQL query');
        }

        $this->query->getSqlQuery();
    }

    /**
     * getQuery
     *
     * @return Query       returns the query object associated with this hook
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * setTypeParser
     *
     * @param string        $type   type name
     * @param string|object $parser parser name or custom parser object
     * @phpstan-param class-string<Hook\Parser>|Hook\Parser $parser
     *
     * @return void
     */
    public function setTypeParser(string $type, $parser)
    {
        $this->typeParsers[$type] = $parser;
    }

    /**
     * setFieldParser
     *
     * @param string        $field  field name
     * @param string|object $parser parser name or custom parser object
     *
     * @return void
     */
    public function setFieldParser($field, $parser)
    {
        $this->fieldParsers[$field] = $parser;
    }

    /**
     * hookWhere
     * builds DQL query where part from given parameter array
     *
     * @param  array $params an associative array containing field
     *                       names and their values
     * @return bool whether or not the hooking was
     */
    public function hookWhere(array $params): bool
    {
        foreach ($params as $name => $value) {
            if ($value === '' || $value === '-') {
                continue;
            }
            $e = explode('.', $name);

            if (count($e) == 2) {
                list($alias, $column) = $e;

                $map   = $this->query->getQueryComponent($alias);
                $table = $map['table'];

                if ($def = $table->getDefinitionOf($column)) {
                    if (isset($this->typeParsers[$def->type->value])) {
                        $name   = $this->typeParsers[$def->type->value];
                        $parser = new $name();

                        $parser->parse($alias, $column, $value);

                        $this->query->addWhere($parser->getCondition(), $parser->getParams());
                    }
                }
            }
        }

        return true;
    }

    /**
     * hookOrderBy
     * builds DQL query orderby part from given parameter array
     *
     * @param  array $params an array containing all fields which the built query
     *                       should be ordered by
     */
    public function hookOrderby(array $params): void
    {
        foreach ($params as $name) {
            $e = explode(' ', $name);

            $order = 'ASC';

            if (count($e) > 1) {
                $order = ($e[1] == 'DESC') ? 'DESC' : 'ASC';
            }

            $e = explode('.', $e[0]);

            if (count($e) == 2) {
                list($alias, $column) = $e;

                $map   = $this->query->getQueryComponent($alias);
                $table = $map['table'];

                if ($def = $table->getDefinitionOf($column)) {
                    $this->query->addOrderBy($alias . '.' . $column . ' ' . $order);
                }
            }
        }
    }

    /**
     * set the hook limit
     *
     * @param  integer $limit
     * @return void
     */
    public function hookLimit($limit)
    {
        $this->query->limit((int) $limit);
    }

    /**
     * set the hook offset
     *
     * @param integer $offset
     *
     * @return void
     */
    public function hookOffset($offset)
    {
        $this->query->offset((int) $offset);
    }
}
