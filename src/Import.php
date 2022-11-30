<?php

namespace Doctrine1;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 */
class Import extends Connection\Module
{
    /**
     * @var array
     */
    protected $sql = [];

    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        if (!isset($this->sql['listDatabases'])) {
            throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listDatabases']);
    }

    /**
     * lists all available database functions
     *
     * @return array
     */
    public function listFunctions()
    {
        if (!isset($this->sql['listFunctions'])) {
            throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listFunctions']);
    }

    /**
     * lists all database triggers
     *
     * @param  string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists all database sequences
     *
     * @param  string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        if (!isset($this->sql['listSequences'])) {
            throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listSequences']);
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table relations
     *
     * Expects an array of this format to be returned with all the relationships in it where the key is
     * the name of the foreign table, and the value is an array containing the local and foreign column
     * name
     *
     * Array
     * (
     *   [groups] => Array
     *     (
     *        [local] => group_id
     *        [foreign] => id
     *     )
     * )
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableRelations($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists tables
     *
     * @param  string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table triggers
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table views
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableViews($table)
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers()
    {
        if (!isset($this->sql['listUsers'])) {
            throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listUsers']);
    }

    /**
     * lists database views
     *
     * @param  string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        if (!isset($this->sql['listViews'])) {
            throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
        }

        return $this->conn->fetchColumn($this->sql['listViews']);
    }

    /**
     * checks if a database exists
     *
     * @param  string $database
     * @return boolean
     */
    public function databaseExists($database)
    {
        return in_array($database, $this->listDatabases());
    }

    /**
     * checks if a function exists
     *
     * @param  string $function
     * @return boolean
     */
    public function functionExists($function)
    {
        return in_array($function, $this->listFunctions());
    }

    /**
     * checks if a trigger exists
     *
     * @param  string      $trigger
     * @param  string|null $database
     * @return boolean
     */
    public function triggerExists($trigger, $database = null)
    {
        return in_array($trigger, $this->listTriggers($database));
    }

    /**
     * checks if a sequence exists
     *
     * @param  string      $sequence
     * @param  string|null $database
     * @return boolean
     */
    public function sequenceExists($sequence, $database = null)
    {
        return in_array($sequence, $this->listSequences($database));
    }

    /**
     * checks if a table constraint exists
     *
     * @param  string $constraint
     * @param  string $table      database table name
     * @return boolean
     */
    public function tableConstraintExists($constraint, $table)
    {
        return in_array($constraint, $this->listTableConstraints($table));
    }

    /**
     * checks if a table column exists
     *
     * @param  string $column
     * @param  string $table  database table name
     * @return boolean
     */
    public function tableColumnExists($column, $table)
    {
        return in_array($column, $this->listTableColumns($table));
    }

    /**
     * checks if a table index exists
     *
     * @param  string $index
     * @param  string $table database table name
     * @return boolean
     */
    public function tableIndexExists($index, $table)
    {
        return in_array($index, $this->listTableIndexes($table));
    }

    /**
     * checks if a table exists
     *
     * @param  string      $table
     * @param  string|null $database
     * @return boolean
     */
    public function tableExists($table, $database = null)
    {
        return in_array($table, $this->listTables($database));
    }

    /**
     * checks if a table trigger exists
     *
     * @param  string $trigger
     * @param  string $table   database table name
     * @return boolean
     */
    public function tableTriggerExists($trigger, $table)
    {
        return in_array($trigger, $this->listTableTriggers($table));
    }

    /**
     * checks if a table view exists
     *
     * @param  string $view
     * @param  string $table database table name
     * @return boolean
     */
    public function tableViewExists($view, $table)
    {
        return in_array($view, $this->listTableViews($table));
    }

    /**
     * checks if a user exists
     *
     * @param  string $user
     * @return boolean
     */
    public function userExists($user)
    {
        return in_array($user, $this->listUsers());
    }

    /**
     * checks if a view exists
     *
     * @param  string      $view
     * @param  string|null $database
     * @return boolean
     */
    public function viewExists($view, $database = null)
    {
        return in_array($view, $this->listViews($database));
    }

    /**
     * method for importing existing schema to Record classes
     *
     * @param  string $directory
     * @param  array $connections Array of connection names to generate models for
     * @return array the names of the imported classes
     */
    public function importSchema(string $directory, array $connections = [], array $options = []): array
    {
        $classes = [];

        $manager = Manager::getInstance();
        foreach ($manager as $name => $connection) {
            // Limit the databases to the ones specified by $connections.
            // Check only happens if array is not empty
            if (!empty($connections) && !in_array($name, $connections)) {
                continue;
            }

            $builder = new Import\Builder();
            $builder->setTargetPath($directory);
            $builder->setOptions($options);

            $definitions = [];

            foreach ($connection->import->listTables() as $table) {
                $definition = [];
                $definition['tableName'] = $table;
                $definition['className'] = Inflector::classify(Inflector::tableize($table));
                $definition['columns'] = $connection->import->listTableColumns($table);
                $definition['connection'] = $connection->getName();
                $definition['connectionClassName'] = $definition['className'];
                $definition['relations'] = [];

                try {
                    $unsortedRelations = $connection->import->listTableRelations($table);
                } catch (Import\Exception $e) {
                    $unsortedRelations = [];
                }

                $relations = [];
                foreach ($definition['columns'] as $columnName => $column) {
                    foreach ($unsortedRelations as $relation) {
                        if ($relation['local'] === $columnName) {
                            $relations[] = $relation;
                            break;
                        }
                    }
                }
                unset($unsortedRelations);

                foreach ($relations as $relation) {
                    $table = $relation['table'];
                    $class = Inflector::classify(Inflector::tableize($table));

                    $columnDefinition = $definition['columns'][$relation['local']];
                    if (!empty($columnDefinition['meta']['relation_alias'])) {
                        $alias = $columnDefinition['meta']['relation_alias'];
                        if (isset($definition['relations'][$alias])) {
                            throw new Import\Exception('The alias name requested via database meta-comments is already taken by another relation');
                        }
                    } else {
                        $aliasNum = 1;
                        $alias = $class;
                        while (isset($definition['relations'][$alias])) {
                            if (substr($relation['local'], 0, 3) === 'id_') {
                                $alias = Inflector::classify(Inflector::tableize(substr($relation['local'], 3)));
                            } else {
                                $aliasNum++;
                                $alias = "$class{$aliasNum}";
                            }
                        }
                    }

                    $definition['relations'][$alias] = [
                        'alias'   => $alias,
                        'class'   => $class,
                        'local'   => $relation['local'],
                        'foreign' => $relation['foreign']
                    ];
                }

                $definitionId = strtolower($definition['className']);
                $definitions[$definitionId] = $definition;
                $classes[] = $definition['className'];
            }

            // Build opposite end of relationships
            foreach ($definitions as $definition) {
                $className = $definition['className'];

                foreach ($definition['relations'] as $relation) {
                    $definitionId = strtolower($relation['class']);

                    $columnDefinition = $definition['columns'][$relation['local']];
                    if (!empty($columnDefinition['meta']['inverse_relation_alias'])) {
                        $alias = $columnDefinition['meta']['inverse_relation_alias'];
                        if (isset($definitions[$definitionId]['relations'][$alias])) {
                            throw new Import\Exception('The alias name requested via database meta-comments is already taken by another relation');
                        }
                    } else {
                        $aliasNum = 1;
                        $alias = $className;
                        while (isset($definitions[$definitionId]['relations'][$alias])) {
                            $aliasNum++;
                            $alias = "$className{$aliasNum}";
                        }
                    }

                    $definitions[$definitionId]['relations'][$alias] = [
                        'type'    => Relation::MANY,
                        'alias'   => $alias,
                        'class'   => $className,
                        'local'   => $relation['foreign'],
                        'foreign' => $relation['local']
                    ];
                }
            }

            // Build records
            foreach ($definitions as $definition) {
                $builder->buildRecord($definition);
            }
        }

        return $classes;
    }
}
