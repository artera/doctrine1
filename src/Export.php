<?php

namespace Doctrine1;

use Doctrine1\Column\Type;
use Doctrine1\Table;
use Throwable;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 * @phpstan-type ExportableOptions = array{
 *   name?: string,
 *   tableName?: string,
 *   sequenceName?: ?string,
 *   inheritanceMap?: array,
 *   enumMap?: array,
 *   type?: ?string,
 *   charset?: ?string,
 *   collate?: ?string,
 *   treeImpl?: mixed,
 *   treeOptions?: array,
 *   indexes?: array{type?: string, fields?: string[]}[],
 *   parents?: array,
 *   queryParts?: array,
 *   subclasses?: array,
 *   orderBy?: mixed,
 *   checks?: array,
 *   primary?: string[],
 *   foreignKeys?: mixed[],
 * }
 */
class Export extends Connection\Module
{
    /**
     * drop an existing database
     * (this method is implemented by the drivers)
     *
     * @param  string $database name of the database that should be dropped
     * @return void
     */
    public function dropDatabase($database)
    {
        foreach ((array) $this->dropDatabaseSql($database) as $query) {
            $this->conn->execute($query);
        }
    }

    /**
     * drop an existing database
     * (this method is implemented by the drivers)
     *
     * @param  string $database name of the database that should be dropped
     * @return string|array
     */
    public function dropDatabaseSql($database)
    {
        throw new Export\Exception('Drop database not supported by this driver.');
    }

    /**
     * dropTableSql
     * drop an existing table
     *
     * @param  string $table name of table that should be dropped from the database
     * @return string
     */
    public function dropTableSql($table)
    {
        return 'DROP TABLE ' . $this->conn->quoteIdentifier($table);
    }

    /**
     * dropTable
     * drop an existing table
     *
     * @param  string $table name of table that should be dropped from the database
     * @return void
     */
    public function dropTable($table)
    {
        $this->conn->execute($this->dropTableSql($table));
    }

    /**
     * drop existing index
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the index to be dropped
     * @return int
     */
    public function dropIndex($table, $name)
    {
        return $this->conn->exec($this->dropIndexSql($table, $name));
    }

    /**
     * dropIndexSql
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the index to be dropped
     * @return string                 SQL that is used for dropping an index
     */
    public function dropIndexSql($table, $name)
    {
        $name = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name));

        return 'DROP INDEX ' . $name;
    }

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
        $name  = $this->conn->quoteIdentifier($name);

        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name);
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
        return $this->dropConstraint($table, $this->conn->formatter->getForeignKeyName($name));
    }

    /**
     * dropSequenceSql
     * drop existing sequence
     * (this method is implemented by the drivers)
     *
     * @throws Connection\Exception     if something fails at database level
     * @param  string $sequenceName name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($sequenceName)
    {
        $this->conn->exec($this->dropSequenceSql($sequenceName));
    }

    /**
     * dropSequenceSql
     * drop existing sequence
     *
     * @throws Connection\Exception     if something fails at database level
     * @param  string $sequenceName name of the sequence to be dropped
     * @return string
     */
    public function dropSequenceSql($sequenceName)
    {
        throw new Export\Exception('Drop sequence not supported by this driver.');
    }

    /**
     * create a new database
     * (this method is implemented by the drivers)
     *
     * @param  string $database name of the database that should be created
     * @return void
     */
    public function createDatabase($database)
    {
        $this->conn->execute($this->createDatabaseSql($database));
    }

    /**
     * create a new database
     * (this method is implemented by the drivers)
     *
     * @param  string $database name of the database that should be created
     * @return string
     */
    public function createDatabaseSql($database)
    {
        throw new Export\Exception('Create database not supported by this driver.');
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
            throw new Export\Exception('no valid table name specified');
        }

        if (empty($fields)) {
            throw new Export\Exception('no fields specified for table ' . $name);
        }

        $queryFields = $this->getFieldDeclarationList($fields);


        if (!empty($options['primary'])) {
            $primaryKeys = array_map([$this->conn, 'quoteIdentifier'], array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $primaryKeys) . ')';
        }

        if (!empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $indexDeclaration = $this->getIndexDeclaration($index, $definition);
                $queryFields .= ', ' . $indexDeclaration;
            }
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields;

        $check = $this->getCheckDeclaration($fields);

        if (!empty($check)) {
            $query .= ', ' . $check;
        }

        $query .= ')';

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
     * create a new table
     *
     * @param string $name    Name of the database that should be created
     * @param Column[] $fields  Associative array that contains the definition of each field of the new table
     * @param array|null $options An associative array of table options:
     * @phpstan-param ?ExportableOptions $options
     * @see   Export::createTableSql()
     * @return void
     */
    public function createTable($name, array $fields, ?array $options = null)
    {
        // Build array of the primary keys if any of the individual field definitions
        // specify primary => true
        $count = 0;
        foreach ($fields as $field) {
            if (is_array($field)) { // @phpstan-ignore-line
                throw new \InvalidArgumentException('Fields should be of class \Doctrine1\Column');
            }

            if ($field->primary) {
                if ($count == 0) {
                    $options['primary'] = [];
                }
                $count++;
                $options['primary'][] = $field->name;
            }
        }

        $sql = $this->createTableSql($name, $fields, $options);

        foreach ($sql as $query) {
            $this->conn->execute($query);
        }
    }

    /**
     * create sequence
     *
     * @throws Connection\Exception     if something fails at database level
     * @param  string $seqName name of the sequence to be created
     * @param  int    $start   start value of the sequence; default is 1
     * @param  array  $options An associative array of table options:
     *                         array( 'comment' => 'Foo', 'charset'
     *                         => 'utf8', 'collate' =>
     *                         'utf8_unicode_ci', );
     */
    public function createSequence($seqName, $start = 1, array $options = []): bool
    {
        $this->conn->execute($this->createSequenceSql($seqName, $start = 1, $options));
        return true;
    }

    /**
     * return RDBMS specific create sequence statement
     * (this method is implemented by the drivers)
     *
     * @throws Connection\Exception     if something fails at database level
     * @param  string $seqName name of the sequence to be created
     * @param  int    $start   start value of the sequence; default is 1
     * @param  array  $options An associative array of table options:
     *                         array( 'comment' => 'Foo', 'charset'
     *                         => 'utf8', 'collate' =>
     *                         'utf8_unicode_ci', );
     * @return string
     */
    public function createSequenceSql($seqName, $start = 1, array $options = [])
    {
        throw new Export\Exception('Create sequence not supported by this driver.');
    }

    /**
     * create a constraint on a table
     *
     * @param  string $table      name of the table on which the constraint is to be created
     * @param  string $name       name of the constraint to be created
     * @param  array  $definition associative array that defines properties of the constraint to be created.
     *                            Currently, only one property named FIELDS is supported. This property is
     *                            also an associative with the names of the constraint fields as array
     *                            constraints. Each entry of this array is set to another type of
     *                            associative array that specifies properties of the constraint that are
     *                            specific to each field. Example array( 'fields' => array( 'user_name' =>
     *                            array(), 'last_login' => array() ) )
     * @return int
     */
    public function createConstraint($table, $name, $definition)
    {
        $sql = $this->createConstraintSql($table, $name, $definition);

        return $this->conn->exec($sql);
    }

    /**
     * create a constraint on a table
     *
     * @param  string $table      name of the table on which the constraint is to be created
     * @param  string $name       name of the constraint to be created
     * @param  array  $definition associative array that defines properties of the constraint to be created.
     *                            Currently, only one property named FIELDS is supported. This property is
     *                            also an associative with the names of the constraint fields as array
     *                            constraints. Each entry of this array is set to another type of
     *                            associative array that specifies properties of the constraint that are
     *                            specific to each field. Example array( 'fields' => array( 'user_name' =>
     *                            array(), 'last_login' => array() ) )
     * @return string
     */
    public function createConstraintSql($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name));
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $name;

        if (isset($definition['primary']) && $definition['primary']) {
            $query .= ' PRIMARY KEY';
        } elseif (isset($definition['unique']) && $definition['unique']) {
            $query .= ' UNIQUE';
        }

        $fields = [];
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier((string) $field, true);
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
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
     *                            supports() to determine whether the DBMS driver can manage indexes.
     *                            Example array( 'fields' => array( 'user_name' => array( 'sorting' =>
     *                            'ascending' ), 'last_login' => array() ) )
     */
    public function createIndex($table, $name, array $definition): Connection\Statement
    {
        return $this->conn->execute($this->createIndexSql($table, $name, $definition));
    }

    /**
     * Get the stucture of a field into an array
     *
     * @param  string $table      name of the table on which the index is to be created
     * @param  string $name       name of the index to be created
     * @param  array  $definition associative array that defines properties of the index to be created.
     * @see    Export::createIndex()
     * @return string
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $name  = $this->conn->quoteIdentifier($name);
        $type  = '';

        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                    break;
                default:
                    throw new Export\Exception(
                        'Unknown type ' . $definition['type'] . ' for index ' . $name . ' in table ' . $table
                    );
            }
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $fields = [];
        foreach ($definition['fields'] as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
    }
    /**
     * createForeignKeySql
     *
     * @param  string $table      name of the table on which the foreign key is to be created
     * @param  array  $definition associative array that defines properties of the foreign key to be created.
     * @return string
     */
    public function createForeignKeySql($table, array $definition)
    {
        $table = $this->conn->quoteIdentifier($table);
        $query = 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclaration($definition);

        return $query;
    }

    /**
     * createForeignKey
     *
     * @param  string $table      name of the table on which the foreign key is to be created
     * @param  array  $definition associative array that defines properties of the foreign key to be created.
     */
    public function createForeignKey($table, array $definition): void
    {
        $sql = $this->createForeignKeySql($table, $definition);
        $this->conn->execute($sql);
    }

    /**
     * alter an existing table
     * (this method is implemented by the drivers)
     *
     * @param string  $name    name of the table that is intended to be changed.
     * @param array   $changes associative array that contains the details of each type
     *                         of change that is intended to be performed. The types of
     *                         changes that are currently supported are defined as
     *                         follows: name New name for the table. add Associative
     *                         array with the names of fields to be added as indexes of
     *                         the array. The value of each entry of the array should
     *                         be set to another associative array with the properties
     *                         of the fields to be added. The properties of the fields
     *                         should be the same as defined by the MDB2 parser. remove
     *                         Associative array with the names of fields to be removed
     *                         as indexes of the array. Currently the values assigned
     *                         to each entry are ignored. An empty array should be used
     *                         for future compatibility. rename Associative array with
     *                         the names of fields to be renamed as indexes of the
     *                         array. The value of each entry of the array should be
     *                         set to another associative array with the entry named
     *                         name with the new field name and the entry named
     *                         Declaration that is expected to contain the portion of
     *                         the field declaration already in DBMS specific SQL code
     *                         as it is used in the CREATE TABLE statement. change
     *                         Associative array with the names of the fields to be
     *                         changed as indexes of the array. Keep in mind that if it
     *                         is intended to change either the name of a field and any
     *                         other properties, the change array entries should have
     *                         the new names of the fields as array indexes. The value
     *                         of each entry of the array should be set to another
     *                         associative array with the properties of the fields to
     *                         that are meant to be changed as array entries. These
     *                         entries should be assigned to the new values of the
     *                         respective properties. The properties of the fields
     *                         should be the same as defined by the MDB2 parser.
     *                         Example array( 'name' => 'userlist', 'add' => array(
     *                         'quota' => array( 'type' => 'integer', 'unsigned' => 1 )
     *                         ), 'remove' => array( 'file_limit' => array(),
     *                         'time_limit' => array() ), 'change' => array( 'name' =>
     *                         array( 'length' => '20', 'definition' => array( 'type'
     *                         => 'text', 'length' => 20, ), ) ), 'rename' => array(
     *                         'sex' => array( 'name' => 'gender', 'definition' =>
     *                         array( 'type' => 'text', 'length' => 1, 'default' =>
     *                         'M', ), ) ) )
     *
     * @param  boolean $check   indicates whether the function should just check if the DBMS driver
     *                          can perform the requested table alterations if the value is true or
     *                          actually perform them otherwise.
     * @return void
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->alterTableSql($name, $changes, $check);

        if (is_string($sql) && $sql) {
            $this->conn->execute($sql);
        }
    }

    /**
     * generates the sql for altering an existing table
     * (this method is implemented by the drivers)
     *
     * @param  string  $name    name of the table that is intended to be changed.
     * @param  array   $changes associative array that contains the details of each type      *
     * @param  boolean $check   indicates whether the function should just check if the DBMS driver
     *                          can perform the requested table alterations if the value is true or
     *                          actually perform them otherwise.
     * @see    Export::alterTable()
     * @return mixed
     */
    public function alterTableSql($name, array $changes, $check = false)
    {
        throw new Export\Exception('Alter table not supported by this driver.');
    }

    /**
     * Get declaration of a number of field in bulk
     *
     * @param Column[] $fields
     * @return string
     */
    public function getFieldDeclarationList(array $fields): string
    {
        $queryFields = [];

        foreach ($fields as $field) {
            $query = $this->getDeclaration($field);

            $queryFields[] = $query;
        }
        return implode(', ', $queryFields);
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

        $method = 'get' . $field->type->value . 'Declaration';

        try {
            if (method_exists($this->conn->dataDict, $method)) {
                return $this->conn->dataDict->$method($field);
            } else {
                $dec = $this->conn->dataDict->getNativeDeclaration($field);
            }

            return $this->conn->quoteIdentifier($field->name, true)
                 . ' ' . $dec . $charset . $default . $notnull . $unique . $check . $collation;
        } catch (Throwable $e) {
            throw new Exception('Around field ' . $field->name . ': ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * getDefaultDeclaration
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultFieldDeclaration(Column $field): string
    {
        $default = '';

        if ($field->hasDefault()) {
            $default = $field->default;

            if (empty($default)) {
                $default = $field->notnull
                    ? $field->type->default()
                    : null;

                if (empty($default) && ($this->conn->getPortability() & Core::PORTABILITY_EMPTY_TO_NULL)) {
                    $default = null;
                }
            }

            if ($field->type === Type::Boolean) {
                $default = $this->conn->convertBooleans($default);
            }

            $default = ' DEFAULT ' . (
                $default === null
                    ? 'NULL'
                    : $this->conn->quote($default, $field->type->value)
            );
        }

        return $default;
    }


    /**
     * getNotNullFieldDeclaration
     * Obtain DBMS specific SQL code portion needed to set a NOT NULL
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string DBMS specific SQL code portion needed to set a default value
     */
    public function getNotNullFieldDeclaration(Column $column)
    {
        return $column->notnull ? ' NOT NULL' : '';
    }


    /**
     * Obtain DBMS specific SQL code portion needed to set a CHECK constraint
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition check definition
     * @phpstan-param (string|Column)[] $definition
     * @return string DBMS specific SQL code portion needed to set a CHECK constraint
     */
    public function getCheckDeclaration(array $definition)
    {
        $constraints = [];
        foreach ($definition as $field => $def) {
            if (is_string($def)) {
                $constraints[] = "CHECK ($def)";
            } else {
                if ($def->min !== null) {
                    $constraints[] = "CHECK ($field >= {$def->min})";
                }

                if ($def->max !== null) {
                    $constraints[] = "CHECK ($field <= {$def->max})";
                }
            }
        }

        return implode(', ', $constraints);
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param  string $name       name of the index
     * @param  array $definition index definition
     * @phpstan-param array{type?: string, fields?: array<string, string | mixed[]>} $definition
     * @return string DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclaration(string $name, array $definition): string
    {
        $name = $this->conn->quoteIdentifier($name);
        $type = '';

        if (isset($definition['type'])) {
            if (strtolower($definition['type']) == 'unique') {
                $type = strtoupper($definition['type']) . ' ';
            } else {
                throw new Export\Exception(
                    'Unknown type ' . $definition['type'] . ' for index ' . $name
                );
            }
        }

        if (!isset($definition['fields']) || !is_array($definition['fields'])) {
            throw new Export\Exception('No columns given for index ' . $name);
        }

        $query = $type . 'INDEX ' . $name;

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array<string, string | mixed[]> $fields
     * @return string
     */
    public function getIndexFieldDeclarationList(array $fields)
    {
        $ret = [];
        foreach ($fields as $field => $definition) {
            if (is_array($definition)) {
                $ret[] = $this->conn->quoteIdentifier($field);
            } else {
                $ret[] = $this->conn->quoteIdentifier($definition);
            }
        }
        return implode(', ', $ret);
    }

    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * Should be overridden in driver classes to return the correct string for the
     * specific database type.
     *
     * The default is to return the string "TEMPORARY" - this will result in a
     * SQL error for any database that does not support temporary tables, or that
     * requires a different SQL command from "CREATE TEMPORARY TABLE".
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableQuery()
    {
        return 'TEMPORARY';
    }

    /**
     * getForeignKeyDeclaration
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition an associative array with the following structure:
     *                          name                    optional constraint name
     *                          local                   the local field(s) foreign
     *                          the foreign reference field(s)
     *                          foreignTable            the name of the foreign
     *                          table onDelete                referential delete
     *                          action onUpdate                referential update
     *                          action deferred                deferred constraint
     *                          checking The onDelete and onUpdate keys accept the
     *                          following values: CASCADE: Delete or update the
     *                          row from the parent table and automatically delete
     *                          or update the matching rows in the child table.
     *                          Both ON DELETE CASCADE and ON UPDATE CASCADE are
     *                          supported. Between two tables, you should not
     *                          define several ON UPDATE CASCADE clauses that act
     *                          on the same column in the parent table or in the
     *                          child table. SET NULL: Delete or update the row
     *                          from the parent table and set the foreign key
     *                          column or columns in the child table to NULL. This
     *                          is valid only if the foreign key columns do not
     *                          have the NOT NULL qualifier specified. Both ON
     *                          DELETE SET NULL and ON UPDATE SET NULL clauses are
     *                          supported. NO ACTION: In standard SQL, NO ACTION
     *                          means no action in the sense that an attempt to
     *                          delete or update a primary key value is not
     *                          allowed to proceed if there is a related foreign
     *                          key value in the referenced table. RESTRICT:
     *                          Rejects the delete or update operation for the
     *                          parent table. NO ACTION and RESTRICT are the same
     *                          as omitting the ON DELETE or ON UPDATE clause. SET
     *                          DEFAULT
     *
     * @return string  DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     *                 of a field declaration.
     */
    public function getForeignKeyDeclaration(array $definition)
    {
        $sql = $this->getForeignKeyBaseDeclaration($definition);
        $sql .= $this->getAdvancedForeignKeyOptions($definition);

        return $sql;
    }

    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param  array $definition foreign key definition
     * @return string
     */
    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if (!empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if (!empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }

    /**
     * getForeignKeyReferentialAction
     *
     * returns given referential action in uppercase if valid, otherwise throws
     * an exception
     *
     * @throws Export\Exception     if unknown referential action given
     *
     * @param string $action foreign key referential action
     *
     * @return string
     */
    public function getForeignKeyReferentialAction($action)
    {
        $upper = strtoupper($action);
        switch ($upper) {
            case 'CASCADE':
            case 'SET NULL':
            case 'NO ACTION':
            case 'RESTRICT':
            case 'SET DEFAULT':
                return $upper;
            default:
                throw new Export\Exception('Unknown foreign key referential action \'' . $upper . '\' given.');
        }
    }

    /**
     * getForeignKeyBaseDeclaration
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param  array $definition
     * @return string
     */
    public function getForeignKeyBaseDeclaration(array $definition)
    {
        $sql = '';
        if (isset($definition['name'])) {
            $sql .= 'CONSTRAINT ' . $this->conn->quoteIdentifier($this->conn->formatter->getForeignKeyName($definition['name'])) . ' ';
        }
        $sql .= 'FOREIGN KEY (';

        if (!isset($definition['local'])) {
            throw new Export\Exception('Local reference field missing from definition.');
        }
        if (!isset($definition['foreign'])) {
            throw new Export\Exception('Foreign reference field missing from definition.');
        }
        if (!isset($definition['foreignTable'])) {
            throw new Export\Exception('Foreign reference table missing from definition.');
        }

        if (!is_array($definition['local'])) {
            $definition['local'] = [$definition['local']];
        }
        if (!is_array($definition['foreign'])) {
            $definition['foreign'] = [$definition['foreign']];
        }

        $sql .= implode(', ', array_map([$this->conn, 'quoteIdentifier'], $definition['local']))
              . ') REFERENCES '
              . $this->conn->quoteIdentifier($definition['foreignTable']) . '('
              . implode(', ', array_map([$this->conn, 'quoteIdentifier'], $definition['foreign'])) . ')';

        return $sql;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the UNIQUE constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion needed to set the UNIQUE constraint
     *                 of a field declaration.
     */
    public function getUniqueFieldDeclaration()
    {
        return 'UNIQUE';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param  string $charset name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration(string $charset): string
    {
        return '';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param  string $collation name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration(string $collation): string
    {
        return '';
    }

    /**
     * exportSchema
     * method for exporting Record classes to a schema
     *
     * if the directory parameter is given this method first iterates
     * recursively trhough the given directory in order to find any model classes
     *
     * Then it iterates through all declared classes and creates tables for the ones
     * that extend Record and are not abstract classes
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param  string $directory optional directory parameter
     * @return void
     */
    public function exportSchema($directory = null)
    {
        if ($directory !== null) {
            $models = Core::filterInvalidModels(Core::loadModels($directory));
        } else {
            $models = Core::getLoadedModels();
        }

        $this->exportClasses($models);
    }

    /**
     * @param  array $classes
     * @param  bool  $groupByConnection
     * @return array
     */
    public function exportSortedClassesSql($classes, $groupByConnection = true)
    {
        $connections = [];
        foreach ($classes as $class) {
            $connection     = Manager::getInstance()->getConnectionForComponent($class);
            $connectionName = $connection->getName();

            if (!isset($connections[$connectionName])) {
                $connections[$connectionName] = [
                     'create_tables'    => [],
                     'create_sequences' => [],
                     'create_indexes'   => [],
                     'alters'           => [],
                     'create_triggers'  => [],
                 ];
            }

            $sql = $connection->export->exportClassesSql([$class]);

            // Build array of all the creates
            // We need these to happen first
            foreach ($sql as $key => $query) {
                // If create table statement
                if (substr($query, 0, strlen('CREATE TABLE')) == 'CREATE TABLE') {
                    $connections[$connectionName]['create_tables'][] = $query;

                    unset($sql[$key]);
                    continue;
                }

                // If create sequence statement
                if (substr($query, 0, strlen('CREATE SEQUENCE')) == 'CREATE SEQUENCE') {
                    $connections[$connectionName]['create_sequences'][] = $query;

                    unset($sql[$key]);
                    continue;
                }

                // If create index statement
                if (preg_grep('/CREATE ([^ ]* )?INDEX/', [$query])) {
                    $connections[$connectionName]['create_indexes'][] = $query;

                    unset($sql[$key]);
                    continue;
                }

                // If alter table statement
                if (substr($query, 0, strlen('ALTER TABLE')) == 'ALTER TABLE'
                    || substr($query, 0, strlen('DECLARE')) == 'DECLARE'
                ) {
                    $connections[$connectionName]['alters'][] = $query;

                    unset($sql[$key]);
                    continue;
                }

                // If create trigger statement
                if (substr($query, 0, strlen('CREATE TRIGGER')) == 'CREATE TRIGGER') {
                    $connections[$connectionName]['create_triggers'][] = $query;

                    unset($sql[$key]);
                    continue;
                }

                // If comment statement
                if (substr($query, 0, strlen('COMMENT ON')) == 'COMMENT ON') {
                    $connections[$connectionName]['comments'][] = $query;

                    unset($sql[$key]);
                    continue;
                }
            }
        }

        // Loop over all the sql again to merge everything together so it is in the correct order
        $build = [];
        foreach ($connections as $connectionName => $sql) {
            $build[$connectionName] = array_unique(array_merge($sql['create_tables'], $sql['create_sequences'], $sql['create_indexes'], $sql['alters'], $sql['create_triggers']));
        }

        if (!$groupByConnection) {
            $new = [];
            foreach ($build as $connectionname => $sql) {
                $new = array_unique(array_merge($new, $sql));
            }
            $build = $new;
        }
        return $build;
    }

    /**
     * exportClasses
     * method for exporting Record classes to a schema
     *
     * FIXME: This function has ugly hacks in it to make sure sql is inserted in the correct order.
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param  array $classes
     * @return void
     */
    public function exportClasses(array $classes)
    {
        $queries = $this->exportSortedClassesSql($classes);

        foreach ($queries as $connectionName => $sql) {
            $connection = Manager::getInstance()->getConnection($connectionName);

            $savepoint = $connection->beginTransaction();

            foreach ($sql as $query) {
                try {
                    $connection->exec($query);
                } catch (Connection\Exception $e) {
                    // we only want to silence table already exists errors
                    if ($e->getPortableCode() !== Core::ERR_ALREADY_EXISTS) {
                        $savepoint->rollback();
                        throw new Export\Exception("{$e->getMessage()}. Failing Query: $query", previous: $e);
                    }
                }
            }

            $savepoint->commit();
        }
    }

    /**
     * exportClassesSql
     * method for exporting Record classes to a schema
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param  array $classes
     * @return array
     */
    public function exportClassesSql(array $classes)
    {
        $models = Core::filterInvalidModels($classes);

        $sql = [];

        foreach ($models as $name) {
            $record  = new $name();
            $table   = $record->getTable();

            // Don't export the tables with attribute EXPORT_NONE'
            if ($table->getExportFlags() === Core::EXPORT_NONE) {
                continue;
            }

            $data = $table->getExportableFormat();

            $query = $this->conn->export->createTableSql($data['tableName'], $data['columns'], $data['options']);
            $sql = array_merge($sql, $query);

            // DC-474: Remove dummy $record from repository to not pollute it during export
            $repo = $table->getRepository();
            if ($repo !== null) {
                $repo->evict($record->getOid());
            }
            unset($record);
        }

        $sql = array_unique($sql);

        rsort($sql);

        return $sql;
    }

    /**
     * exportSql
     * returns the sql for exporting Record classes to a schema
     *
     * if the directory parameter is given this method first iterates
     * recursively trhough the given directory in order to find any model classes
     *
     * Then it iterates through all declared classes and creates tables for the ones
     * that extend Record and are not abstract classes
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @param  string $directory optional directory parameter
     * @return array
     */
    public function exportSql($directory = null)
    {
        if ($directory !== null) {
            $models = Core::filterInvalidModels(Core::loadModels($directory));
        } else {
            $models = Core::getLoadedModels();
        }

        return $this->exportSortedClassesSql($models, false);
    }

    /**
     * exportTable
     * exports given table into database based on column and option definitions
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @return void
     */
    public function exportTable(Table $table)
    {
        try {
            $data = $table->getExportableFormat();
            $this->conn->export->createTable($data['tableName'], $data['columns'], $data['options']);
        } catch (Connection\Exception $e) {
            // we only want to silence table already exists errors
            if ($e->getPortableCode() !== Core::ERR_ALREADY_EXISTS) {
                throw $e;
            }
        }
    }
}
