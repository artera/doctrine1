<?php

namespace Doctrine1;

use Doctrine1\Import\Definition;

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
     * @phpstan-return list<array{table: string, local: string, foreign: string}>
     */
    public function listTableRelations(string $table): array
    {
        throw new Import\Exception(__FUNCTION__ . ' not supported by this driver.');
    }

    /**
     * lists table columns
     *
     * @param  string $table database table name
     * @phpstan-return list<Column>
     */
    public function listTableColumns(string $table): array
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
    public function listTableTriggers(?string $table): array
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

            $manager->setCurrentConnection($name);

            $builder = new Import\Builder();
            $builder->setTargetPath($directory);
            $builder->setOptions($options);

            /** @phpstan-var array<string, Definition\Table> */
            $definitions = [];

            foreach ($connection->import->listTables() as $table) {
                $tableClassName = Inflector::classify(Inflector::tableize($table));
                $definition = new Definition\Table(
                    name: $table,
                    className: $tableClassName,
                    columns: $connection->import->listTableColumns($table),
                );

                $relations = [];
                try {
                    $unsortedRelations = $connection->import->listTableRelations($table);

                    foreach ($definition->columns as $column) {
                        foreach ($unsortedRelations as $relation) {
                            if ($relation['local'] === $column->name) {
                                $relations[] = $relation;
                                break;
                            }
                        }
                    }
                } catch (Import\Exception) {
                }

                foreach ($relations as $relation) {
                    $table = $relation['table'];
                    $class = Inflector::classify(Inflector::tableize($table));

                    $column = $definition->getColumn($relation['local']);
                    if (!empty($column?->meta['relation_alias'])) {
                        $alias = $column->meta['relation_alias'];
                        if ($definition->getRelationByAlias($alias) !== null) {
                            throw new Import\Exception('The alias name requested via database meta-comments is already taken by another relation');
                        }
                    } else {
                        $aliasNum = 1;
                        $alias = $class;
                        while ($definition->getRelationByAlias($alias) !== null) {
                            if (substr($relation['local'], 0, 3) === 'id_') {
                                $alias = Inflector::classify(Inflector::tableize(substr($relation['local'], 3)));
                            } else {
                                $aliasNum++;
                                $alias = "$class{$aliasNum}";
                            }
                        }
                    }

                    $definition->relations[] = new Definition\Relation(
                        $alias,
                        $class,
                        $relation['local'],
                        $relation['foreign']
                    );
                }

                $definitions[$definition->className] = $definition;
                $classes[] = $definition->className;
            }

            // Build opposite end of relationships
            foreach ($definitions as $definition) {
                foreach ($definition->relations as $relation) {
                    if ($relation->many) {
                        continue;
                    }

                    $column = $definition->getColumn($relation->local);
                    if (!empty($column?->meta['inverse_relation_alias'])) {
                        $alias = $column->meta['inverse_relation_alias'];
                        if ($definitions[$relation->class]->getRelationByAlias($alias) !== null) {
                            throw new Import\Exception('The alias name requested via database meta-comments is already taken by another relation');
                        }
                    } else {
                        $aliasNum = 1;
                        $alias = $definition->className;
                        while ($definitions[$relation->class]->getRelationByAlias($alias) !== null) {
                            $aliasNum++;
                            $alias = "{$definition->className}{$aliasNum}";
                        }
                    }

                    $definitions[$relation->class]->relations[] = new Definition\Relation(
                        $alias,
                        $definition->className,
                        $relation->foreign,
                        $relation->local,
                        true,
                    );
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
