<?php

namespace Doctrine1\Export;

use Doctrine1\Column;
use PDO;
use PDOException;

/**
 * @phpstan-import-type ExportableOptions from \Doctrine1\Export
 */
class Sqlite extends \Doctrine1\Export
{
    /**
     * dropDatabase
     *
     * drop an existing database
     *
     * @param  string $databaseFile Path of the database that should be dropped
     * @throws \Doctrine1\Export\Exception    if the database file does not exist
     * @throws \Doctrine1\Export\Exception    if something failed during the removal of the database file
     * @return void
     */
    public function dropDatabase($databaseFile)
    {
        if (!@file_exists($databaseFile)) {
            throw new \Doctrine1\Export\Exception('database does not exist');
        }

        $result = @unlink($databaseFile);

        if (!$result) {
            throw new \Doctrine1\Export\Exception('could not remove the database file');
        }
    }

    public function createDatabase(string $databaseFile): void
    {
        new PDO('sqlite:' . $databaseFile);
    }

    /**
     * Get the stucture of a field into an array
     *
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
     *                            support() to determine whether the DBMS driver can manage indexes.
     *                            Example array( 'fields' => array( 'user_name' => array( 'sorting' =>
     *                            'ascending' ), 'last_login' => array() ) )
     * @throws PDOException
     * @return string
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $name = $this->conn->formatter->getIndexName($name);
        $name = $this->conn->quoteIdentifier($name);
        $type = '';

        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
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

    public function getIndexFieldDeclarationList(array $fields): string
    {
        $declFields = [];

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->conn->quoteIdentifier($fieldName);

            if (is_array($field)) {
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

        $autoinc = false;
        foreach ($fields as $field) {
            if ($field->autoincrement) {
                $autoinc = true;
                break;
            }
        }

        if (!$autoinc && isset($options['primary']) && !empty($options['primary'])) {
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map([$this->conn, 'quoteIdentifier'], $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $name = $this->conn->quoteIdentifier($name, true);
        $sql  = 'CREATE TABLE ' . $name . ' (' . $queryFields;

        if ($check = $this->getCheckDeclaration($fields)) {
            $sql .= ', ' . $check;
        }

        if (isset($options['checks']) && $check = $this->getCheckDeclaration($options['checks'])) {
            $sql .= ', ' . $check;
        }

        $sql .= ')';

        $query[] = $sql;

        if (isset($options['indexes']) && !empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $query[] = $this->createIndexSql($name, $index, $definition);
            }
        }

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
        if (isset($definition['feferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
    }

    /**
     * create sequence
     *
     * @param  string $seqName name of the sequence to be created
     * @param  int    $start   start value of the sequence; default is 1
     * @param  array  $options An associative array of table options:
     *                         array( 'comment' => 'Foo', 'charset'
     *                         => 'utf8', 'collate' =>
     *                         'utf8_unicode_ci', );
     */
    public function createSequence($seqName, $start = 1, array $options = []): bool
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getSequenceColumnName(), true);
        $query        = 'CREATE TABLE ' . $sequenceName . ' (' . $seqcolName . ' INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)';

        $this->conn->exec($query);

        if ($start == 1) {
            return true;
        }

        try {
            $this->conn->exec('INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')');
            return true;
        } catch (\Doctrine1\Connection\Exception $e) {
            // Handle error

            try {
                $result = $this->conn->exec('DROP TABLE ' . $sequenceName);
            } catch (\Doctrine1\Connection\Exception $e) {
                throw new \Doctrine1\Export\Exception('could not drop inconsistent sequence table', previous: $e);
            }
        }
        throw new \Doctrine1\Export\Exception('could not create sequence table');
    }

    public function dropSequenceSql(string $sequenceName): string
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName), true);

        return 'DROP TABLE ' . $sequenceName;
    }

    public function alterTableSql(string $name, array $changes): array
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

        $query = '';
        if (!empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name']);
            $query .= "RENAME TO $change_name";
        }

        if (!empty($changes['add'])) {
            foreach ($changes['add'] as $field) {
                if ($query) {
                    $query .= ', ';
                }
                $query .= "ADD {$this->getDeclaration($field)}";
            }
        }

        if (!empty($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->conn->quoteIdentifier($fieldName);
                $query .= "DROP $fieldName";
            }
        }

        $rename = [];
        if (!empty($changes['rename'])) {
            foreach ($changes['rename'] as $oldFieldName => $fieldName) {
                $rename[$fieldName] = $oldFieldName;
            }
        }

        if (!empty($rename)) {
            foreach ($rename as $oldFieldName => $fieldName) {
                if ($query) {
                    $query .= ', ';
                }
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $query .= "RENAME COLUMN $oldFieldName TO $fieldName";
            }
        }

        $name = $this->conn->quoteIdentifier($name, true);
        return ["ALTER TABLE $name $query"];
    }

    /**
     * createForeignKey
     *
     * Sqlite does not support foreign keys so we are not even going to do anything if this function is called
     * to avoid any sql errors if a user tries to use this on sqlite
     *
     * @param  string $table      name of the table on which the foreign key is to be created
     * @param  array  $definition associative array that defines properties of the foreign key to be created.
     */
    public function createForeignKey($table, array $definition): void
    {
    }
}
