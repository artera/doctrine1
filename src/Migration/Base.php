<?php

namespace Doctrine1\Migration;

use Doctrine1\Column;

abstract class Base
{
    /**
     * The default options for tables created using \Doctrine1\Migration\Base::createTable()
     */
    private static array $defaultTableOptions = [];

    protected array $changes = [];

    protected static array $opposites = [
        'created_table'       => 'dropped_table',
        'dropped_table'       => 'created_table',
        'created_constraint'  => 'dropped_constraint',
        'dropped_constraint'  => 'created_constraint',
        'created_foreign_key' => 'dropped_foreign_key',
        'dropped_foreign_key' => 'created_foreign_key',
        'created_column'      => 'dropped_column',
        'dropped_column'      => 'created_column',
        'created_index'       => 'dropped_index',
        'dropped_index'       => 'created_index',
    ];

    /**
     * Get the changes that have been added on this migration class instance
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getNumChanges(): int
    {
        return count($this->changes);
    }

    /**
     * Add a change to the stack of changes to execute
     *
     * @param  string $type   The type of change
     * @param  array  $change The array of information for the change
     */
    protected function addChange(string $type, array $change = []): array
    {
        if (isset($change['upDown']) && $change['upDown'] !== null && isset(self::$opposites[$type])) {
            $upDown = $change['upDown'];
            unset($change['upDown']);
            if ($upDown == 'down') {
                $opposite                = self::$opposites[$type];
                return $this->changes[] = [$opposite, $change];
            }
        }
        return $this->changes[] = [$type, $change];
    }

    /**
     * Sets the default options for tables created using \Doctrine1\Migration\Base::createTable()
     *
     * @param array $options
     */
    public static function setDefaultTableOptions(array $options): void
    {
        self::$defaultTableOptions = $options;
    }

    /**
     * Returns the default options for tables created using \Doctrine1\Migration\Base::createTable()
     *
     * @return array
     */
    public static function getDefaultTableOptions()
    {
        return self::$defaultTableOptions;
    }

    /**
     * Add a create or drop table change.
     *
     * @param  string $upDown    Whether to add the up(create) or down(drop) table change.
     * @param  string $tableName Name of the table
     * @param  array  $fields    Array of fields for table
     * @param  array  $options   Array of options for the table
     */
    public function table(string $upDown, string $tableName, array $fields = [], array $options = []): void
    {
        $this->addChange('created_table', [
            'upDown' => $upDown,
            'tableName' => $tableName,
            'fields' => $fields,
            'options' => $options,
        ]);
    }

    /**
     * Add a create table change.
     *
     * @param  string $tableName Name of the table
     * @phpstan-param Column[] $fields
     * @param  array  $fields    Array of fields for table
     * @param  array  $options   Array of options for the table
     */
    public function createTable(string $tableName, array $fields = [], array $options = []): void
    {
        $this->table('up', $tableName, $fields, array_merge(self::getDefaultTableOptions(), $options));
    }

    /**
     * Add a drop table change.
     *
     * @param  string $tableName Name of the table
     */
    public function dropTable(string $tableName): void
    {
        $this->table('down', $tableName);
    }

    /**
     * Add a rename table change
     *
     * @param  string $oldTableName Name of the table to change
     * @param  string $newTableName Name to change the table to
     */
    public function renameTable(string $oldTableName, string $newTableName): void
    {
        $options = get_defined_vars();

        $this->addChange('renamed_table', $options);
    }

    /**
     * Add a create or drop constraint change.
     *
     * @param  string      $upDown         Whether to add the up(create) or down(drop) create change.
     * @param  string      $tableName      Name of the table.
     * @param  string|null $constraintName Name of the constraint.
     * @param  array       $definition     Array for the constraint definition.
     */
    public function constraint(string $upDown, string $tableName, ?string $constraintName, array $definition): void
    {
        $options = get_defined_vars();

        $this->addChange('created_constraint', $options);
    }

    /**
     * Add a create constraint change.
     *
     * @param  string      $tableName      Name of the table.
     * @param  string|null $constraintName Name of the constraint.
     * @param  array       $definition     Array for the constraint definition.
     */
    public function createConstraint(string $tableName, ?string $constraintName, array $definition): void
    {
        $this->constraint('up', $tableName, $constraintName, $definition);
    }

    /**
     * Add a drop constraint change.
     *
     * @param  string      $tableName      Name of the table.
     * @param  string|null $constraintName Name of the constraint.
     * @param  bool        $primary
     */
    public function dropConstraint(string $tableName, ?string $constraintName, bool $primary = false): void
    {
        $this->constraint('down', $tableName, $constraintName, ['primary' => $primary]);
    }

    /**
     * Convenience method for creating or dropping primary keys.
     *
     * @param  string $direction
     * @param  string $tableName   Name of the table
     * @param  array  $columnNames Array of column names and column definitions
     */
    public function primaryKey(string $direction, string $tableName, $columnNames): void
    {
        if ($direction == 'up') {
            $this->createPrimaryKey($tableName, $columnNames);
        } else {
            $this->dropPrimaryKey($tableName, $columnNames);
        }
    }

    /**
     * Convenience method for creating primary keys
     *
     *     [php]
     *     $columns = array(
     *         'id' => array(
     *             'type' => 'integer
     *             'autoincrement' => true
     *          )
     *     );
     *     $this->createPrimaryKey('my_table', $columns);
     *
     * Equivalent to doing:
     *
     *  * Add new columns (addColumn())
     *  * Create primary constraint on columns (createConstraint())
     *  * Change autoincrement = true field to be autoincrement
     *
     * @param  string $tableName   Name of the table
     * @param  array  $columnNames Array of column names and column definitions
     */
    public function createPrimaryKey(string $tableName, $columnNames): void
    {
        $autoincrement = false;
        $fields        = [];

        // Add the columns
        foreach ($columnNames as $columnName => $def) {
            $type    = $def['type'];
            $length  = isset($def['length']) ? $def['length'] : null;
            $options = isset($def['options']) ? $def['options'] : [];

            $this->addColumn($tableName, $columnName, $type, $length, $options);

            $fields[$columnName] = [];

            if (isset($def['autoincrement'])) {
                $autoincrement                         = true;
                $autoincrementColumn                   = $columnName;
                $autoincrementType                     = $type;
                $autoincrementLength                   = $length;
                $autoincrementOptions                  = $options;
                $autoincrementOptions['autoincrement'] = true;
            }
        }

        // Create the primary constraint for the columns
        $this->createConstraint(
            $tableName,
            null,
            [
            'primary' => true,
            'fields'  => $fields
            ]
        );

        // If auto increment change the column to be so
        if ($autoincrement) {
            $this->changeColumn($tableName, $autoincrementColumn, $autoincrementType, $autoincrementLength, $autoincrementOptions);
        }
    }

    /**
     * Convenience method for dropping primary keys.
     *
     *     [php]
     *     $columns = array(
     *         'id' => array(
     *             'type' => 'integer
     *             'autoincrement' => true
     *          )
     *     );
     *     $this->dropPrimaryKey('my_table', $columns);
     *
     * Equivalent to doing:
     *
     *  * Change autoincrement column so it's not (changeColumn())
     *  * Remove primary constraint (dropConstraint())
     *  * Removing columns (removeColumn())
     *
     * @param  string $tableName   Name of the table
     * @param  array  $columnNames Array of column names and column definitions
     */
    public function dropPrimaryKey(string $tableName, $columnNames): void
    {
        // un-autoincrement
        foreach ((array) $columnNames as $columnName => $def) {
            if (isset($def['autoincrement'])) {
                $changeDef = $def;
                unset($changeDef['autoincrement']);
                $this->changeColumn($tableName, $columnName, $changeDef['type'], $changeDef['length'], $changeDef);
            }
        }

        // Remove primary constraint
        $this->dropConstraint($tableName, null, true);

        // Remove columns
        foreach (array_keys((array) $columnNames) as $columnName) {
            $this->removeColumn($tableName, $columnName);
        }
    }

    /**
     * Add a create or drop foreign key change.
     *
     * @param  string $upDown     Whether to add the up(create) or down(drop) foreign key change.
     * @param  string $tableName  Name of the table.
     * @param  string $name       Name of the foreign key.
     * @param  array  $definition Array for the foreign key definition
     */
    public function foreignKey(string $upDown, string $tableName, string $name, array $definition = []): void
    {
        $definition['name'] = $name;
        $options            = get_defined_vars();

        $this->addChange('created_foreign_key', $options);
    }

    /**
     * Add a create foreign key change.
     *
     * @param  string $tableName  Name of the table.
     * @param  string $name       Name of the foreign key.
     * @param  array  $definition Array for the foreign key definition
     */
    public function createForeignKey($tableName, $name, array $definition): void
    {
        $this->foreignKey('up', $tableName, $name, $definition);
    }

    /**
     * Add a drop foreign key change.
     *
     * @param  string $tableName Name of the table.
     * @param  string $name      Name of the foreign key.
     */
    public function dropForeignKey(string $tableName, string $name): void
    {
        $this->foreignKey('down', $tableName, $name);
    }

    /**
     * Add a add or remove column change.
     *
     * @param  string $upDown     Whether to add the up(add) or down(remove) column change.
     * @param  string $tableName  Name of the table
     * @param  string $columnName Name of the column
     * @param  string $type       Type of the column
     * @param  string $length     Length of the column
     * @param  array  $options    Array of options for the column
     */
    public function column(string $upDown, string $tableName, string $columnName, $type = null, $length = null, array $options = []): void
    {
        $options = get_defined_vars();
        if (!isset($options['options']['length'])) {
            $options['options']['length'] = $length;
        }
        $options = array_merge($options, $options['options']);
        unset($options['options']);

        $this->addChange('created_column', $options);
    }

    /**
     * Add a add column change.
     *
     * @param  string $tableName  Name of the table
     * @param  string $columnName Name of the column
     * @param  string $type       Type of the column
     * @param  string $length     Length of the column
     * @param  array  $options    Array of options for the column
     */
    public function addColumn(string $tableName, string $columnName, string $type, $length = null, array $options = []): void
    {
        $this->column('up', $tableName, $columnName, $type, $length, $options);
    }

    /**
     * Add a remove column change.
     *
     * @param  string $tableName  Name of the table
     * @param  string $columnName Name of the column
     */
    public function removeColumn(string $tableName, string $columnName): void
    {
        $this->column('down', $tableName, $columnName);
    }

    /**
     * Add a rename column change
     *
     * @param  string $tableName     Name of the table to rename the column on
     * @param  string $oldColumnName The old column name
     * @param  string $newColumnName The new column name
     */
    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): void
    {
        $options = get_defined_vars();

        $this->addChange('renamed_column', $options);
    }

    /**
     * Add a change column change
     *
     * @param  string $tableName  Name of the table to change the column on
     * @param  string $columnName Name of the column to change
     * @param  string $type       New type of column
     * @param  string $length     The length of the column
     * @param  array  $options    New options for the column
     */
    public function changeColumn(string $tableName, string $columnName, $type = null, $length = null, array $options = []): void
    {
        $options                      = get_defined_vars();
        $options['options']['length'] = $length;

        $this->addChange('changed_column', $options);
    }

    /**
     * Add a add or remove index change.
     *
     * @param  string $upDown     Whether to add the up(add) or down(remove) index change.
     * @param  string $tableName  Name of the table
     * @param  string $indexName  Name of the index
     * @param  array  $definition Array for the index definition
     */
    public function index(string $upDown, string $tableName, string $indexName, array $definition = []): void
    {
        $options = get_defined_vars();

        $this->addChange('created_index', $options);
    }

    /**
     * Add a add index change.
     *
     * @param  string $tableName  Name of the table
     * @param  string $indexName  Name of the index
     * @param  array  $definition Array for the index definition
     */
    public function addIndex(string $tableName, string $indexName, array $definition): void
    {
        $this->index('up', $tableName, $indexName, $definition);
    }

    /**
     * Add a remove index change.
     *
     * @param  string $tableName Name of the table
     * @param  string $indexName Name of the index
     */
    public function removeIndex(string $tableName, string $indexName): void
    {
        $this->index('down', $tableName, $indexName);
    }

    public function preUp(): void
    {
    }

    public function postUp(): void
    {
    }

    public function preDown(): void
    {
    }

    public function postDown(): void
    {
    }
}
