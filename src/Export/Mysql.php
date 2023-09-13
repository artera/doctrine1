<?php

namespace Doctrine1\Export;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Doctrine1\Core;
use PDOException;

/**
 * @template Connection of \Doctrine1\Connection\Mysql
 * @extends \Doctrine1\Export<Connection>
 * @phpstan-import-type ExportableOptions from \Doctrine1\Export
 */
class Mysql extends \Doctrine1\Export
{
    /**
     * drop existing constraint
     *
     * @param  string $table   name of table that should be used in method
     * @param  string $name    name of the constraint to be dropped
     * @param  bool   $primary hint if the constraint is primary
     * @return int
     */
    public function dropConstraint($table, $name, $primary = false)
    {
        $table = $this->conn->quoteIdentifier($table);

        if (!$primary) {
            $name = 'CONSTRAINT ' . $this->conn->quoteIdentifier($name);
        } else {
            $name = 'PRIMARY KEY';
        }

        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP ' . $name);
    }

    /**
     * createDatabaseSql
     *
     * @param  string $name
     * @return string
     */
    public function createDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name, true);
    }

    /**
     * drop an existing database
     *
     * @param  string $name name of the database that should be dropped
     * @return array
     */
    public function dropDatabaseSql($name)
    {
        return [
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP DATABASE ' . $this->conn->quoteIdentifier($name),
            'SET FOREIGN_KEY_CHECKS = 1'
        ];
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
            throw new \Doctrine1\Export\Exception('no fields specified for table "' . $name . '"');
        }
        $queryFields = $this->getFieldDeclarationList($fields);

        // build indexes for all foreign key fields (needed in MySQL!!)
        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $fk) {
                $local = $fk['local'];
                $found = false;
                if (isset($options['indexes'])) {
                    foreach ($options['indexes'] as $definition) {
                        if (isset($definition['fields']) && in_array($local, $definition['fields']) && count($definition['fields']) === 1) {
                            // Index already exists on the column
                            $found = true;
                        }
                    }
                }
                if (isset($options['primary']) && !empty($options['primary'])
                    && in_array($local, $options['primary'])
                ) {
                    // field is part of the PK and therefore already indexed
                    $found = true;
                }

                if (!$found) {
                    if (is_array($local)) {
                        foreach ($local as $localidx) {
                            $options['indexes'][$localidx] = ['fields' => [$localidx => []]];
                        }
                    } else {
                        $options['indexes'][$local] = ['fields' => [$local => []]];
                    }
                }
            }
        }

        // add all indexes
        if (isset($options['indexes']) && !empty($options['indexes'])) {
            // Case Insensitive checking for duplicate indexes...
            $dupes = [];
            foreach ($options['indexes'] as $key => $index) {
                if (in_array(strtolower($key), $dupes)) {
                    unset($options['indexes'][$key]);
                } else {
                    $dupes[] = strtolower($key);
                }
            }
            unset($dupes);

            foreach ($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && !empty($options['primary'])) {
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map([$this->conn, 'quoteIdentifier'], $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

        $optionStrings = [];

        if (isset($options['comment'])) {
            $optionStrings['comment'] = 'COMMENT = ' . $this->conn->quote($options['comment'], 'text');
        }
        if (isset($options['charset'])) {
            $optionStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];
        }
        if (isset($options['collate'])) {
            $optionStrings['collate'] = 'COLLATE ' . $options['collate'];
        }

        $type = false;

        // get the type of the table
        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->conn->getDefaultMySQLEngine();
        }

        if ($type) {
            $optionStrings[] = 'ENGINE = ' . $type;
        }

        if (!empty($optionStrings)) {
            $query .= ' ' . implode(' ', $optionStrings);
        }
        $sql[] = $query;

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
        }
        return $sql;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statements like CREATE TABLE.
     *
     * @return string DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getDeclaration(Column $field): string
    {
        $default = $this->getDefaultFieldDeclaration($field);
        $charset = $field->charset ? ' ' . $this->getCharsetFieldDeclaration($field->charset) : '';
        $collation = $field->collation ? ' ' . $this->getCollationFieldDeclaration($field->collation) : '';
        $notnull = $this->getNotNullFieldDeclaration($field);
        $unique = $field->unique ? ' ' . $this->getUniqueFieldDeclaration() : '';
        $check = $field->check ? ' ' . $field->check : '';
        $comment = $field->comment ? " COMMENT {$this->conn->quote($field->comment, 'text')}" : '';

        $method = 'get' . $field->type->value . 'Declaration';

        try {
            if (method_exists($this->conn->dataDict, $method)) {
                return $this->conn->dataDict->$method($field);
            } else {
                $dec = $this->conn->dataDict->getNativeDeclaration($field);
            }

            return $this->conn->quoteIdentifier($field->name, true)
                 . ' ' . $dec . $charset . $default . $notnull . $comment . $unique . $check . $collation;
        } catch (\Throwable $e) {
            throw new Exception("Around field $field->name: {$e->getMessage()}\n\n{$e->getTraceAsString()}\n\n", previous: $e);
        }
    }

    /**
     * alter an existing table
     *
     * @param string  $name    name of the table that is intended to be changed.
     * @phpstan-param array{
     *   add?: Column[],
     *   remove?: string[],
     *   change?: array<string, Column>,
     *   rename?: array<string, string>,
     *   name?: string,
     * } $changes
     * @param  boolean $check   indicates whether the function should just check if the DBMS driver
     *                          can perform the requested table alterations if the value is true or
     *                          actually perform them otherwise.
     * @return boolean|string
     */
    public function alterTableSql($name, array $changes, $check = false)
    {
        if (!$name) {
            throw new \Doctrine1\Export\Exception('no valid table name specified');
        }
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw new \Doctrine1\Export\Exception('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if (!empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name']);
            $query .= "RENAME TO $change_name";
        }

        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field) {
                if ($query) {
                    $query .= ', ';
                }
                $query .= "ADD {$this->getDeclaration($field)}";
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->conn->quoteIdentifier($fieldName);
                $query .= "DROP $fieldName";
            }
        }

        $rename = [];
        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $oldFieldName => $fieldName) {
                $rename[$fieldName] = $oldFieldName;
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                if (isset($rename[$fieldName])) {
                    $oldFieldName = $rename[$fieldName];
                    unset($rename[$fieldName]);
                } else {
                    $oldFieldName = $fieldName;
                }
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
                $query .= "CHANGE $oldFieldName {$this->getDeclaration($field)}";
            }
        }

        if (!empty($rename) && is_array($rename)) {
            foreach ($rename as $oldFieldName => $fieldName) {
                if ($query) {
                    $query .= ', ';
                }
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $query .= "RENAME COLUMN $oldFieldName TO $fieldName";
            }
        }

        if (!$query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);

        return "ALTER TABLE $name $query";
    }

    /**
     * create sequence
     *
     * @param  string $sequenceName name of the sequence to be created
     * @param  int    $start        start value of the sequence; default is 1
     * @param  array  $options      An associative array of table options:
     *                              array( 'comment' => 'Foo', 'charset'
     *                              => 'utf8', 'collate' =>
     *                              'utf8_unicode_ci', 'type'    =>
     *                              'innodb', );
     * @phpstan-param array{
     *   comment?: string,
     *   charset?: string,
     *   collate?: string,
     *   type?: string,
     * } $options
     */
    public function createSequence($sequenceName, $start = 1, array $options = []): bool
    {
        $sequenceName = $this->conn->quoteIdentifier($sequenceName, true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getSequenceColumnName(), true);

        $optionsStrings = [];

        if (isset($options['comment']) && !empty($options['comment'])) {
            $optionsStrings['comment'] = 'COMMENT = ' . $this->conn->quote($options['comment'], 'string');
        }

        if (isset($options['charset']) && !empty($options['charset'])) {
            $optionsStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];

            if (isset($options['collate'])) {
                $optionsStrings['charset'] .= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->conn->getDefaultMySQLEngine();
        }
        if ($type) {
            $optionsStrings[] = 'ENGINE = ' . $type;
        }


        try {
            $query = 'CREATE TABLE ' . $sequenceName
                    . ' (' . $seqcolName . ' BIGINT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                    . $seqcolName . ')) ' . implode(' ', $optionsStrings);

            $res = $this->conn->exec($query);
        } catch (\Doctrine1\Connection\Exception $e) {
            throw new \Doctrine1\Export\Exception('could not create sequence table', previous: $e);
        }

        if ($start == 1 && $res == 1) {
            return true;
        }

        $query = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')';

        $res = $this->conn->exec($query);

        if ($res == 1) {
            return true;
        }

        // Handle error
        try {
            $result = $this->conn->exec('DROP TABLE ' . $sequenceName);
        } catch (\Doctrine1\Connection\Exception $e) {
            throw new \Doctrine1\Export\Exception('could not drop inconsistent sequence table', previous: $e);
        }

        return false;
    }

    /**
     * Get the stucture of a field into an array
     *
     * @author Leoncx
     * @param  string $table      name of the table on which the index is to be created
     * @param  string $name       name of the index to be created
     * @param  array  $definition associative array that defines properties of the index to be created.
     *                            Currently, only one property named FIELDS is supported. This property
     *                            is also an associative with the names of the index fields as array
     *                            indexes. Each entry of this array is set to another type of
     *                            associative array that specifies properties of the index that are
     *                            specific to each field. Currently, only the sorting property is
     *                            supported. It should be used to define the sorting direction of the
     *                            index. It may be set to either ascending or descending. Not all DBMS
     *                            support index sorting direction configuration. The DBMS drivers of
     *                            those that do not support it ignore this property. Use the function
     *                            supports() to determine whether the DBMS driver can manage indexes.
     *                            Example array( 'fields' => array( 'user_name' => array( 'sorting' =>
     *                            'ASC' 'length' => 10 ), 'last_login' => array() ) )
     * @throws PDOException
     * @return string
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table = $table;
        $table = $this->conn->quoteIdentifier($table, true);

        $name = $this->conn->formatter->getIndexName($name);
        $name = $this->conn->quoteIdentifier($name);
        $type = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                    break;
                default:
                    throw new \Doctrine1\Export\Exception(
                        'Unknown type ' . $definition['type'] . ' for index ' . $name . ' in table ' . $table
                    );
            }
        }
        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultFieldDeclaration(Column $field): string
    {
        $default = '';

        if ($field->hasDefault() && (!isset($field->length) || $field->length <= 255)) {
            $default = $field->default;

            if (empty($default)) {
                $default = $field->notnull
                    ? $field->type->default()
                    : null;

                if (empty($default) && ($this->conn->getPortability() & Core::PORTABILITY_EMPTY_TO_NULL)) {
                    $default = null;
                }
            }

            // Proposed patch:
            if ($field->type == Type::Enum && $this->conn->getUseNativeEnum()) {
                $fieldType = 'varchar';
            } else {
                $fieldType = $field->type->value;
            }

            $default = ' DEFAULT ' . (
                $default === null
                    ? 'NULL'
                    : $this->conn->quote($default, $fieldType)
            );
        }

        return $default;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name       name of the index
     * @param array $definition index definition
     * @phpstan-param array{type?: string, fields?: array<string, string | mixed[]>} $definition
     * @return string DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclaration(string $name, array $definition): string
    {
        $name = $this->conn->formatter->getIndexName($name);
        $type = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                    break;
                default:
                    throw new \Doctrine1\Export\Exception(
                        'Unknown type ' . $definition['type'] . ' for index ' . $name
                    );
            }
        }

        if (!isset($definition['fields'])) {
            throw new \Doctrine1\Export\Exception('No columns given for index ' . $name);
        }

        $query = $type . 'INDEX ' . $this->conn->quoteIdentifier($name);

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    public function getIndexFieldDeclarationList(array $fields): string
    {
        $declFields = [];

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->conn->quoteIdentifier($fieldName);

            if (is_array($field)) {
                if (isset($field['length'])) {
                    $fieldString .= '(' . $field['length'] . ')';
                }

                if (isset($field['sorting'])) {
                    $sort = strtoupper($field['sorting']);
                    switch ($sort) {
                        case 'ASC':
                        case 'DESC':
                            $fieldString .= ' ' . $sort;
                            break;
                        default:
                            throw new \Doctrine1\Export\Exception('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $this->conn->quoteIdentifier($field);
            }
            $declFields[] = $fieldString;
        }
        return implode(', ', $declFields);
    }

    /**
     * Returns a character set declaration.
     *
     * @param string $charset A character set
     *
     * @return string A character set declaration
     */
    public function getCharsetFieldDeclaration(string $charset): string
    {
        return $this->conn->dataDict->getCharsetFieldDeclaration($charset);
    }

    /**
     * Returns a collation declaration.
     *
     * @param string $collation A collation
     *
     * @return string A collation declaration
     */
    public function getCollationFieldDeclaration(string $collation): string
    {
        return $this->conn->dataDict->getCollationFieldDeclaration($collation);
    }

    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param  array $definition
     * @return string
     */
    public function getAdvancedForeignKeyOptions(array $definition): string
    {
        $query = '';
        if (!empty($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if (!empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if (!empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }

    /**
     * drop existing index
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the index to be dropped
     * @return string
     */
    public function dropIndexSql($table, $name): string
    {
        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name), true);
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }

    /**
     * dropTable
     *
     * @param  string $table name of table that should be dropped from the database
     * @throws PDOException
     * @return string
     */
    public function dropTableSql($table)
    {
        $table = $this->conn->quoteIdentifier($table, true);
        return 'DROP TABLE ' . $table;
    }

    /**
     * drop existing foreign key
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the foreign key to be dropped
     * @return int
     */
    public function dropForeignKey($table, $name)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($this->conn->formatter->getForeignKeyName($name));

        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name);
    }
}
