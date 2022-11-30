<?php

namespace Doctrine1\Relation;

class Parser
{
    /**
     * the table object this parser belongs to
     */
    protected \Doctrine1\Table $table;

    /**
     * @phpstan-var \Doctrine1\Relation[]
     * an array containing all the \Doctrine1\Relation objects for this table
     */
    protected array $relations = [];

    /**
     * relations waiting for parsing
     */
    protected array $pending = [];

    /**
     * @param \Doctrine1\Table $table the table object this parser belongs to
     */
    public function __construct(\Doctrine1\Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return \Doctrine1\Table   the table object this parser belongs to
     */
    public function getTable(): \Doctrine1\Table
    {
        return $this->table;
    }

    /**
     * @param  string $name
     * @return array            an array defining a pending relation
     */
    public function getPendingRelation(string $name): array
    {
        if (!isset($this->pending[$name])) {
            throw new \Doctrine1\Relation\Exception('Unknown pending relation ' . $name);
        }

        return $this->pending[$name];
    }

    /**
     * @return array            an array containing all the pending relations
     */
    public function getPendingRelations(): array
    {
        return $this->pending;
    }

    /**
     * Removes a relation. Warning: this only affects pending relations
     *
     * @param string $name relation to remove
     *
     * @return void
     */
    public function unsetPendingRelations($name)
    {
        unset($this->pending[$name]);
    }

    /**
     * Check if a relation alias exists
     *
     * @param  string $name
     * @return boolean $bool
     */
    public function hasRelation($name)
    {
        if (!isset($this->pending[$name]) && !isset($this->relations[$name])) {
            return false;
        }

        return true;
    }

    /**
     * binds a relation
     *
     * @param  string $name
     * @param  array  $options
     * @return array
     */
    public function bind($name, $options = [])
    {
        $e     = explode(' as ', $name);
        $e     = array_map('trim', $e);
        $name  = $e[0];
        $alias = isset($e[1]) ? $e[1] : $name;

        if (!isset($options['type'])) {
            throw new \Doctrine1\Relation\Exception('Relation type not set.');
        }

        if ($this->hasRelation($alias)) {
            unset($this->relations[$alias]);
            unset($this->pending[$alias]);
        }

        $this->pending[$alias] = array_merge($options, ['class' => $name, 'alias' => $alias]);

        return $this->pending[$alias];
    }

    /**
     * @param  string $alias     relation alias
     * @param  bool   $recursive
     * @return \Doctrine1\Relation
     */
    public function getRelation(string $alias, bool $recursive = true): \Doctrine1\Relation
    {
        if (isset($this->relations[$alias])) {
            return $this->relations[$alias];
        }

        if (isset($this->pending[$alias])) {
            /** @var array{alias: string, type: int, class: class-string<\Doctrine1\Record>, refClass?: class-string<\Doctrine1\Record>} */
            $def = $this->pending[$alias];
            $identifierColumnNames = $this->table->getIdentifierColumnNames();
            $idColumnName          = array_pop($identifierColumnNames);

            // check if reference class name exists
            // if it does we are dealing with association relation
            if (isset($def['refClass'])) {
                $def = $this->completeAssocDefinition($def);
                $localClasses = array_merge($this->table->parents, [$this->table->getComponentName()]);

                $backRefRelationName = isset($def['refClassRelationAlias']) ?
                        $def['refClassRelationAlias'] : $def['refClass'];
                if (!isset($this->pending[$backRefRelationName]) && !isset($this->relations[$backRefRelationName])) {
                    $parser = $def['refTable']->getRelationParser();

                    if (!$parser->hasRelation($this->table->getComponentName())) {
                        $parser->bind(
                            $this->table->getComponentName(),
                            [
                                'type' => \Doctrine1\Relation::ONE,
                                'local' => $def['local'],
                                'foreign' => $idColumnName,
                                'localKey' => true,
                            ]
                        );
                    }

                    if (!$this->hasRelation($backRefRelationName)) {
                        if (in_array($def['class'], $localClasses)) {
                            $this->bind(
                                $def['refClass'] . ' as ' . $backRefRelationName,
                                [
                                    'type' => \Doctrine1\Relation::MANY,
                                    'foreign' => $def['foreign'],
                                    'local' => $idColumnName,
                                ]
                            );
                        } else {
                            $this->bind(
                                $def['refClass'] . ' as ' . $backRefRelationName,
                                [
                                    'type' => \Doctrine1\Relation::MANY,
                                    'foreign' => $def['local'],
                                    'local' => $idColumnName,
                                ]
                            );
                        }
                    }
                }
                if (in_array($def['class'], $localClasses)) {
                    $rel = new \Doctrine1\Relation\Nest($def);
                } else {
                    $rel = new \Doctrine1\Relation\Association($def);
                }
            } else {
                // simple foreign key relation
                $def = $this->completeDefinition($def);

                if (isset($def['localKey']) && $def['localKey']) {
                    $rel = new \Doctrine1\Relation\LocalKey($def);

                    // Automatically index for foreign keys
                    $foreign = (array) $def['foreign'];

                    foreach ($foreign as $fk) {
                        // Check if its already not indexed (primary key)
                        if (!$rel['table']->isIdentifier($rel['table']->getFieldName($fk))) {
                            $rel['table']->addIndex($fk, ['fields' => [$fk]]);
                        }
                    }
                } else {
                    $rel = new \Doctrine1\Relation\ForeignKey($def);
                }
            }
            if (isset($rel)) {
                // unset pending relation
                unset($this->pending[$alias]);
                $this->relations[$alias] = $rel;
                return $rel;
            }
        }
        if ($recursive) {
            $this->getRelations();
            return $this->getRelation($alias, false);
        } else {
            throw new \Doctrine1\Table\Exception("Unknown relation alias $alias");
        }
    }

    /**
     * returns an array containing all relation objects
     *
     * @phpstan-return \Doctrine1\Relation[]
     * @return array        an array of \Doctrine1\Relation objects
     */
    public function getRelations(): array
    {
        foreach ($this->pending as $k => $v) {
            $this->getRelation($k);
        }

        return $this->relations;
    }

    /**
     * Completes the given association definition
     *
     * @param  array $def definition array to be completed
     * @return array        completed definition array
     *
     * @phpstan-param array{
     *   alias: string,
     *   type: int,
     *   class: class-string<\Doctrine1\Record>,
     *   refClass: class-string<\Doctrine1\Record>,
     *   refClassRelationAlias?: ?string,
     *   foreign?: string,
     *   local?: string,
     * } $def
     * @phpstan-return array{
     *   alias: string,
     *   type: int,
     *   table: \Doctrine1\Table,
     *   localTable: \Doctrine1\Table,
     *   class: class-string<\Doctrine1\Record>,
     *   refTable: \Doctrine1\Table,
     *   refClass: class-string<\Doctrine1\Record>,
     *   refClassRelationAlias?: ?string,
     *   foreign: string,
     *   local: string,
     * }
     */
    public function completeAssocDefinition(array $def): array
    {
        $conn              = $this->table->getConnection();
        $def['table']      = $conn->getTable($def['class']);
        $def['localTable'] = $this->table;
        $def['class']      = $def['table']->getComponentName();
        $def['refTable']   = $conn->getTable($def['refClass']);

        $id = $def['refTable']->getIdentifierColumnNames();

        if (count($id) > 1) {
            if (!isset($def['foreign'])) {
                // foreign key not set
                // try to guess the foreign key

                $def['foreign'] = (isset($def['local']) && $def['local'] === $id[0]) ? $id[1] : $id[0];
            }
            if (!isset($def['local'])) {
                // foreign key not set
                // try to guess the foreign key
                $def['local'] = ($def['foreign'] === $id[0]) ? $id[1] : $id[0];
            }
        } else {
            if (!isset($def['foreign'])) {
                // foreign key not set
                // try to guess the foreign key

                $columns = $this->getIdentifiers($def['table']);
                assert(is_string($columns));
                $def['foreign'] = $columns;
            }
            if (!isset($def['local'])) {
                // local key not set
                // try to guess the local key
                $columns = $this->getIdentifiers($this->table);
                assert(is_string($columns));
                $def['local'] = $columns;
            }
        }
        return $def;
    }

    /**
     * gives a list of identifiers from given table
     *
     * the identifiers are in format:
     * [componentName].[identifier]
     *
     * @param \Doctrine1\Table $table table object to retrieve identifiers from
     *
     * @return array|string
     */
    public function getIdentifiers(\Doctrine1\Table $table)
    {
        $componentNameToLower = strtolower($table->getComponentName());
        if (is_array($table->getIdentifier())) {
            $columns = [];
            foreach ((array) $table->getIdentifierColumnNames() as $identColName) {
                $columns[] = $componentNameToLower . '_' . $identColName;
            }
        } else {
            $columns = $componentNameToLower . '_' . $table->getColumnName(
                $table->getIdentifier()
            );
        }

        return $columns;
    }

    /**
     * @param  array          $classes      an array of class names
     * @param  \Doctrine1\Table $foreignTable foreign table object
     * @return array|string                            an array of column names
     */
    public function guessColumns(array $classes, \Doctrine1\Table $foreignTable)
    {
        $conn    = $this->table->getConnection();
        $found   = false;
        $columns = [];

        foreach ($classes as $class) {
            try {
                $table = $conn->getTable($class);
            } catch (\Doctrine1\Table\Exception $e) {
                continue;
            }
            $columns = $this->getIdentifiers($table);
            $found   = true;

            foreach ((array) $columns as $column) {
                if (!$foreignTable->hasColumn($column)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }

        if (!$found) {
            throw new \Doctrine1\Relation\Exception("Couldn't find columns.");
        }

        return $columns;
    }

    /**
     * Completes the given definition
     *
     * @param  array $def definition array to be completed
     * @return array        completed definition array
     *
     * @phpstan-param array{
     *   alias: string,
     *   type: int,
     *   class: class-string<\Doctrine1\Record>,
     *   local?: string,
     *   owningSide?: bool,
     *   foreign?: string,
     *   localKey?: bool,
     * } $def
     * @phpstan-return array{
     *   alias: string,
     *   type: int,
     *   table: \Doctrine1\Table,
     *   localTable: \Doctrine1\Table,
     *   class: class-string<\Doctrine1\Record>,
     *   foreign: string,
     *   local: string,
     *   localKey?: bool,
     *   owningSide?: bool,
     * }
     */
    public function completeDefinition(array $def): array
    {
        $conn              = $this->table->getConnection();
        $def['table']      = $conn->getTable($def['class']);
        $def['localTable'] = $this->table;
        $def['class']      = $def['table']->getComponentName();

        $foreignClasses = array_merge($def['table']->parents, [$def['class']]);
        $localClasses   = array_merge($this->table->parents, [$this->table->getComponentName()]);

        $localIdentifierColumnNames   = $this->table->getIdentifierColumnNames();
        $localIdentifierCount         = count($localIdentifierColumnNames);
        $localIdColumnName            = array_pop($localIdentifierColumnNames);
        assert(is_string($localIdColumnName));
        $foreignIdentifierColumnNames = $def['table']->getIdentifierColumnNames();
        $foreignIdColumnName          = array_pop($foreignIdentifierColumnNames);

        if (isset($def['local'])) {
            $def['local'] = $def['localTable']->getColumnName($def['local']);

            if (!isset($def['foreign'])) {
                // local key is set, but foreign key is not
                // try to guess the foreign key

                if ($def['local'] === $localIdColumnName) {
                    $def['foreign'] = $this->guessColumns($localClasses, $def['table']);
                } else {
                    // the foreign field is likely to be the
                    // identifier of the foreign class
                    $def['foreign']  = $foreignIdColumnName;
                    $def['localKey'] = true;
                }
            } else {
                $def['foreign'] = $def['table']->getColumnName($def['foreign']);

                if ($localIdentifierCount == 1) {
                    if ($def['local'] == $localIdColumnName && array_key_exists('owningSide', $def) && $def['owningSide'] === true) {
                        $def['localKey'] = true;
                    } elseif (($def['local'] !== $localIdColumnName && $def['type'] == \Doctrine1\Relation::ONE)) {
                        $def['localKey'] = true;
                    }
                } elseif ($localIdentifierCount > 1 && !isset($def['localKey'])) {
                    // It's a composite key and since 'foreign' can not point to a composite
                    // key currently, we know that 'local' must be the foreign key.
                    $def['localKey'] = true;
                }
            }
        } else {
            if (isset($def['foreign'])) {
                $def['foreign'] = $def['table']->getColumnName($def['foreign']);

                // local key not set, but foreign key is set
                // try to guess the local key
                if ($def['foreign'] === $foreignIdColumnName) {
                    $def['localKey'] = true;
                    try {
                        $def['local'] = $this->guessColumns($foreignClasses, $this->table);
                    } catch (\Doctrine1\Relation\Exception $e) {
                        $def['local'] = $localIdColumnName;
                    }
                } else {
                    $def['local'] = $localIdColumnName;
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys

                $conn = $this->table->getConnection();

                // the following loops are needed for covering inheritance
                foreach ($localClasses as $class) {
                    $table                 = $conn->getTable($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName          = array_pop($identifierColumnNames);
                    assert(is_string($idColumnName));
                    $column                = strtolower($table->getComponentName())
                            . '_' . $idColumnName;

                    foreach ($foreignClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign'] = $column;
                            $def['local']   = $idColumnName;
                            return $def;
                        }
                    }
                }

                foreach ($foreignClasses as $class) {
                    $table                 = $conn->getTable($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName          = array_pop($identifierColumnNames);
                    assert(is_string($idColumnName));
                    $column                = strtolower($table->getComponentName())
                            . '_' . $idColumnName;

                    foreach ($localClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign']  = $idColumnName;
                            $def['local']    = $column;
                            $def['localKey'] = true;
                            return $def;
                        }
                    }
                }

                // auto-add columns and auto-build relation
                $columns = [];
                foreach ((array) $this->table->getIdentifierColumnNames() as $id) {
                    // ?? should this not be $this->table->getComponentName() ??
                    $column = strtolower($table->getComponentName())
                            . '_' . $id;

                    $col    = $this->table->getColumnDefinition($id);
                    assert($col !== null);
                    $type   = $col['type'];
                    $length = $col['length'];

                    unset($col['type']);
                    unset($col['length']);
                    unset($col['autoincrement']);
                    unset($col['sequence']);
                    unset($col['primary']);

                    $def['table']->setColumn($column, $type, $length, $col);

                    $columns[] = $column;
                }
                if (count($columns) > 1) {
                    $def['foreign'] = $columns;
                } else {
                    $def['foreign'] = $columns[0];
                }
                $def['local'] = $localIdColumnName;
            }
        }
        return $def;
    }
}
