<?php

class Doctrine_Relation_Parser
{
    /**
     * @var Doctrine_Table $_table          the table object this parser belongs to
     */
    protected $_table;

    /**
     * @var array $_relations               an array containing all the Doctrine_Relation objects for this table
     */
    protected $_relations = [];

    /**
     * @var array $_pending                 relations waiting for parsing
     */
    protected $_pending = [];

    /**
     * constructor
     *
     * @param Doctrine_Table $table the table object this parser belongs to
     */
    public function __construct(Doctrine_Table $table)
    {
        $this->_table = $table;
    }

    /**
     * getTable
     *
     * @return Doctrine_Table   the table object this parser belongs to
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * getPendingRelation
     *
     * @param  string $name
     * @return array            an array defining a pending relation
     */
    public function getPendingRelation($name)
    {
        if (!isset($this->_pending[$name])) {
            throw new Doctrine_Relation_Exception('Unknown pending relation ' . $name);
        }

        return $this->_pending[$name];
    }

    /**
     * getPendingRelations
     *
     * @return array            an array containing all the pending relations
     */
    public function getPendingRelations()
    {
        return $this->_pending;
    }

    /**
     * unsetPendingRelations
     * Removes a relation. Warning: this only affects pending relations
     *
     * @param string $name relation to remove
     *
     * @return void
     */
    public function unsetPendingRelations($name)
    {
        unset($this->_pending[$name]);
    }

    /**
     * Check if a relation alias exists
     *
     * @param  string $name
     * @return boolean $bool
     */
    public function hasRelation($name)
    {
        if (!isset($this->_pending[$name]) && !isset($this->_relations[$name])) {
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
            throw new Doctrine_Relation_Exception('Relation type not set.');
        }

        if ($this->hasRelation($alias)) {
            unset($this->_relations[$alias]);
            unset($this->_pending[$alias]);
        }

        $this->_pending[$alias] = array_merge($options, ['class' => $name, 'alias' => $alias]);

        return $this->_pending[$alias];
    }

    /**
     * getRelation
     *
     * @param  string $alias     relation alias
     * @param  bool   $recursive
     * @return Doctrine_Relation
     */
    public function getRelation($alias, $recursive = true)
    {
        if (isset($this->_relations[$alias])) {
            return $this->_relations[$alias];
        }

        if (isset($this->_pending[$alias])) {
            /** @var array{alias: string, type: int, class: class-string<Doctrine_Record>, refClass?: class-string<Doctrine_Record>} */
            $def = $this->_pending[$alias];
            $identifierColumnNames = $this->_table->getIdentifierColumnNames();
            $idColumnName          = array_pop($identifierColumnNames);

            // check if reference class name exists
            // if it does we are dealing with association relation
            if (isset($def['refClass'])) {
                $def = $this->completeAssocDefinition($def);
                $localClasses = array_merge($this->_table->parents, [$this->_table->getComponentName()]);

                $backRefRelationName = isset($def['refClassRelationAlias']) ?
                        $def['refClassRelationAlias'] : $def['refClass'];
                if (!isset($this->_pending[$backRefRelationName]) && !isset($this->_relations[$backRefRelationName])) {
                    $parser = $def['refTable']->getRelationParser();

                    if (!$parser->hasRelation($this->_table->getComponentName())) {
                        $parser->bind(
                            $this->_table->getComponentName(),
                            [
                                'type' => Doctrine_Relation::ONE,
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
                                    'type' => Doctrine_Relation::MANY,
                                    'foreign' => $def['foreign'],
                                    'local' => $idColumnName,
                                ]
                            );
                        } else {
                            $this->bind(
                                $def['refClass'] . ' as ' . $backRefRelationName,
                                [
                                    'type' => Doctrine_Relation::MANY,
                                    'foreign' => $def['local'],
                                    'local' => $idColumnName,
                                ]
                            );
                        }
                    }
                }
                if (in_array($def['class'], $localClasses)) {
                    $rel = new Doctrine_Relation_Nest($def);
                } else {
                    $rel = new Doctrine_Relation_Association($def);
                }
            } else {
                // simple foreign key relation
                $def = $this->completeDefinition($def);

                if (isset($def['localKey']) && $def['localKey']) {
                    $rel = new Doctrine_Relation_LocalKey($def);

                    // Automatically index for foreign keys
                    $foreign = (array) $def['foreign'];

                    foreach ($foreign as $fk) {
                        // Check if its already not indexed (primary key)
                        if (!$rel['table']->isIdentifier($rel['table']->getFieldName($fk))) {
                            $rel['table']->addIndex($fk, ['fields' => [$fk]]);
                        }
                    }
                } else {
                    $rel = new Doctrine_Relation_ForeignKey($def);
                }
            }
            if (isset($rel)) {
                // unset pending relation
                unset($this->_pending[$alias]);
                $this->_relations[$alias] = $rel;
                return $rel;
            }
        }
        if ($recursive) {
            $this->getRelations();
            return $this->getRelation($alias, false);
        } else {
            throw new Doctrine_Table_Exception("Unknown relation alias $alias");
        }
    }

    /**
     * getRelations
     * returns an array containing all relation objects
     *
     * @return array        an array of Doctrine_Relation objects
     */
    public function getRelations()
    {
        foreach ($this->_pending as $k => $v) {
            $this->getRelation($k);
        }

        return $this->_relations;
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
     *   class: class-string<Doctrine_Record>,
     *   refClass: class-string<Doctrine_Record>,
     *   local?: string,
     * } $def
     * @phpstan-return array{
     *   alias: string,
     *   type: int,
     *   table: Doctrine_Table,
     *   localTable: Doctrine_Table,
     *   class: class-string<Doctrine_Record>,
     *   refTable: Doctrine_Table,
     *   refClass: class-string<Doctrine_Record>,
     *   foreign: string,
     *   local: string,
     * }
     */
    public function completeAssocDefinition(array $def): array
    {
        $conn              = $this->_table->getConnection();
        $def['table']      = $conn->getTable($def['class']);
        $def['localTable'] = $this->_table;
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
                $columns = $this->getIdentifiers($this->_table);
                assert(is_string($columns));
                $def['local'] = $columns;
            }
        }
        return $def;
    }

    /**
     * getIdentifiers
     * gives a list of identifiers from given table
     *
     * the identifiers are in format:
     * [componentName].[identifier]
     *
     * @param Doctrine_Table $table table object to retrieve identifiers from
     *
     * @return array|string
     */
    public function getIdentifiers(Doctrine_Table $table)
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
     * guessColumns
     *
     * @param  array          $classes      an array of class names
     * @param  Doctrine_Table $foreignTable foreign table object
     * @return array|string                            an array of column names
     */
    public function guessColumns(array $classes, Doctrine_Table $foreignTable)
    {
        $conn    = $this->_table->getConnection();
        $found   = false;
        $columns = [];

        foreach ($classes as $class) {
            try {
                $table = $conn->getTable($class);
            } catch (Doctrine_Table_Exception $e) {
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
            throw new Doctrine_Relation_Exception("Couldn't find columns.");
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
     *   class: class-string<Doctrine_Record>,
     *   local?: string,
     *   owningSide?: bool,
     * } $def
     * @phpstan-return array{
     *   alias: string,
     *   type: int,
     *   table: Doctrine_Table,
     *   localTable: Doctrine_Table,
     *   class: class-string<Doctrine_Record>,
     *   foreign: string,
     *   local: string,
     *   localKey?: bool,
     *   owningSide?: bool,
     * }
     */
    public function completeDefinition(array $def): array
    {
        $conn              = $this->_table->getConnection();
        $def['table']      = $conn->getTable($def['class']);
        $def['localTable'] = $this->_table;
        $def['class']      = $def['table']->getComponentName();

        $foreignClasses = array_merge($def['table']->parents, [$def['class']]);
        $localClasses   = array_merge($this->_table->parents, [$this->_table->getComponentName()]);

        $localIdentifierColumnNames   = $this->_table->getIdentifierColumnNames();
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
                    } elseif (($def['local'] !== $localIdColumnName && $def['type'] == Doctrine_Relation::ONE)) {
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
                        $def['local'] = $this->guessColumns($foreignClasses, $this->_table);
                    } catch (Doctrine_Relation_Exception $e) {
                        $def['local'] = $localIdColumnName;
                    }
                } else {
                    $def['local'] = $localIdColumnName;
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys

                $conn = $this->_table->getConnection();

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
                foreach ((array) $this->_table->getIdentifierColumnNames() as $id) {
                    // ?? should this not be $this->_table->getComponentName() ??
                    $column = strtolower($table->getComponentName())
                            . '_' . $id;

                    $col    = $this->_table->getColumnDefinition($id);
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
