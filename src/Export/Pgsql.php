<?php

namespace Doctrine1\Export;

use Doctrine1\Column;
use PDOException;

/**
 * @template Connection of \Doctrine1\Connection\Pgsql
 * @extends \Doctrine1\Export<Connection>
 * @phpstan-import-type ExportableOptions from \Doctrine1\Export
 */
class Pgsql extends \Doctrine1\Export
{
    /**
     * @var string
     */
    public $tmpConnectionDatabase = 'postgres';

    /**
     * createDatabaseSql
     *
     * @param  string $name
     * @return string
     */
    public function createDatabaseSql(string $name): string
    {
        $query = 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name);

        return $query;
    }

    /**
     * drop an existing database
     *
     * @param  string $name name of the database that should be dropped
     * @throws PDOException
     * @access public
     * @return string|string[]
     */
    public function dropDatabaseSql(string $name): string|array
    {
        $query = 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);

        return $query;
    }

    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param  array $definition foreign key definition
     * @return string
     * @access protected
     */
    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if (isset($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if (isset($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $definition['onUpdate'];
        }
        if (isset($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $definition['onDelete'];
        }
        if (isset($definition['deferrable'])) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if (isset($definition['deferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
    }

    public function alterTableSql(string $name, array $changes): array
    {
        $qName = $this->conn->quoteIdentifier($name, true);

        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'name':
                case 'rename':
                    break;
                default:
                    throw new \Doctrine1\Export\Exception('change type "' . $changeName . '\" not yet supported');
            }
        }

        $sql = [];

        if (isset($changes['add'])) {
            foreach ($changes['add'] as $field) {
                $sql[] = "ALTER TABLE $qName ADD {$this->getDeclaration($field)}";
            }
        }

        if (isset($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $sql[] = "ALTER TABLE $qName DROP $fieldName";
            }
        }

        if (isset($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);

                $sql[] = "ALTER TABLE $qName ALTER $fieldName TYPE {$this->conn->dataDict->getNativeDeclaration($field)}";

                if ($field->hasDefault()) {
                    $sql[] = "ALTER TABLE $qName ALTER $fieldName SET DEFAULT {$this->conn->quote($field->default, $field->type->value)}";
                }

                $query = "ALTER $fieldName " . ($field->notnull ? 'SET' : 'DROP') . ' NOT NULL';
                $sql[] = "ALTER TABLE $qName $query";
            }
        }

        if (isset($changes['rename'])) {
            foreach ($changes['rename'] as $oldFieldName => $fieldName) {
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
                $sql[]     = "ALTER TABLE $qName RENAME COLUMN $oldFieldName TO {$this->conn->quoteIdentifier($fieldName, true)}";
            }
        }

        if (isset($changes['name'])) {
            $changeName = $this->conn->quoteIdentifier($changes['name'], true);
            $sql[]      = "ALTER TABLE $qName RENAME TO $changeName";
        }

        return $sql;
    }

    /**
     * return RDBMS specific create sequence statement
     *
     * @throws \Doctrine1\Connection\Exception     if something fails at database level
     * @param  string     $sequenceName name of the sequence to be created
     * @param  string|int $start        start value of the sequence; default is 1
     * @param  array      $options      An associative array of table options:
     *                                  array( 'comment' => 'Foo', 'charset'
     *                                  => 'utf8', 'collate' =>
     *                                  'utf8_unicode_ci', );
     * @return string
     */
    public function createSequenceSql($sequenceName, $start = 1, array $options = [])
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName), true);
        return 'CREATE SEQUENCE ' . $sequenceName . ' INCREMENT 1' .
                    ($start < 1 ? ' MINVALUE ' . $start : '') . ' START ' . $start;
    }

    public function dropSequenceSql(string $sequenceName): string
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName), true);
        return 'DROP SEQUENCE ' . $sequenceName;
    }

    /**
     * create a new table
     *
     * @param string $name    Name of the database that should be created
     * @param Column[] $fields
     * @param array|null $options An associative array of table options:
     * @phpstan-param ?ExportableOptions $options
     * @return array
     */
    public function createTableSql(string $name, array $fields, ?array $options = null): array
    {
        if (!$name) {
            throw new \Doctrine1\Export\Exception('no valid table name specified');
        }

        if (empty($fields)) {
            throw new \Doctrine1\Export\Exception('no fields specified for table ' . $name);
        }

        $queryFields = $this->getFieldDeclarationList($fields);


        if (isset($options['primary']) && !empty($options['primary'])) {
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map([$this->conn, 'quoteIdentifier'], $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields;

        if ($check = $this->getCheckDeclaration($fields)) {
            $query .= ', ' . $check;
        }

        if (isset($options['checks']) && $check = $this->getCheckDeclaration($options['checks'])) {
            $query .= ', ' . $check;
        }

        $query .= ')';

        $sql[] = $query;

        if (isset($options['indexes']) && !empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $sql[] = $this->createIndexSql($name, $index, $definition);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
        }
        if (isset($options['sequenceName'])) {
            $sql[] = $this->createSequenceSql($options['sequenceName']);
        }
        return $sql;
    }

    /**
     * Get the stucture of a field into an array.
     *
     * @param  string $table      name of the table on which the index is to be created
     * @param  string $name       name of the index to be created
     * @param  array  $definition associative array that defines properties of the index to be created.
     * @see    \Doctrine1\Export::createIndex()
     * @return string
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $query = parent::createIndexSql($table, $name, $definition);
        if (isset($definition['where'])) {
            return $query . ' WHERE ' . $definition['where'];
        }
        return $query;
    }
}
