<?php

/**
 * @template Connection of Doctrine_Connection_Mysql
 * @extends Doctrine_Import<Connection>
 */
class Doctrine_Import_Mysql extends Doctrine_Import
{
    /**
     * @var array
     */
    protected $sql = [
                            'listDatabases'   => 'SHOW DATABASES',
                            'listTableFields' => 'SHOW FULL COLUMNS FROM %s',
                            'listSequences'   => 'SHOW TABLES',
                            'listTables'      => 'SHOW TABLES',
                            'listUsers'       => 'SELECT DISTINCT USER FROM USER',
                            'listViews'       => "SHOW FULL TABLES %s WHERE Table_type = 'VIEW'",
                            ];

    /**
     * lists all database sequences
     *
     * @param  string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        $query = 'SHOW TABLES';
        if ($database !== null) {
            $query .= ' FROM ' . $database;
        }
        $tableNames = $this->conn->fetchColumn($query);

        return array_map([$this->conn->formatter, 'fixSequenceName'], $tableNames);
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $keyName   = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) && ($this->conn->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_FIX_CASE)) {
            if ($this->conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName   = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName   = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table   = $this->conn->quoteIdentifier($table, true);
        $query   = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->conn->fetchAssoc($query);

        $result = [];
        foreach ($indexes as $indexData) {
            if (!$indexData[$nonUnique]) {
                if ($indexData[$keyName] !== 'PRIMARY') {
                    $index = $this->conn->formatter->fixIndexName($indexData[$keyName]);
                } else {
                    $index = 'PRIMARY';
                }
                if (!empty($index)) {
                    $result[] = $index;
                }
            }
        }
        return $result;
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
     * @param  string $tableName database table name
     * @return array
     */
    public function listTableRelations($tableName)
    {
        $tableName = $this->conn->quote($tableName);
        $dbName = $this->conn->quote($this->conn->getDatabaseName());

        $relations = [];
        $results = $this->conn->fetchAssoc(
            "SELECT column_name AS column_name,
                    referenced_table_name AS referenced_table_name,
                    referenced_column_name AS referenced_column_name
            FROM information_schema.key_column_usage
            WHERE table_name = $tableName
            AND table_schema = $dbName
            AND referenced_column_name IS NOT NULL"
        );
        foreach ($results as $result) {
            $relations[] = [
                'table'   => $result['referenced_table_name'],
                'local'   => $result['column_name'],
                'foreign' => $result['referenced_column_name'],
            ];
        }
        return $relations;
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $sql    = 'SHOW FULL COLUMNS FROM ' . $this->conn->quoteIdentifier($table, true);
        $result = $this->conn->fetchAssoc($sql);

        $description = [];
        $columns     = [];
        foreach ($result as $val) {
            $val = array_change_key_case($val, CASE_LOWER);

            $decl = $this->conn->dataDict->getPortableDeclaration($val);

            $val['default'] = $val['default'] === 'CURRENT_TIMESTAMP' ? null : $val['default'];

            $description = [
                'name'          => $val['field'],
                'type'          => $decl['type'][0],
                'alltypes'      => $decl['type'],
                'ntype'         => $val['type'],
                'length'        => $decl['length'],
                'fixed'         => (bool) $decl['fixed'],
                'unsigned'      => (bool) $decl['unsigned'],
                'values'        => $decl['values'] ?? [],
                'primary'       => strtolower($val['key']) === 'pri',
                'default'       => $val['default'],
                'notnull'       => $val['null'] !== 'YES',
                'autoincrement' => strpos($val['extra'], 'auto_increment') !== false,
                'virtual'       => strpos($val['extra'], 'VIRTUAL') !== false,
                'comment'       => $val['comment'],
            ];
            if (isset($decl['scale'])) {
                $description['scale'] = $decl['scale'];
            }

            $meta = json_decode($description['comment'], true);
            if (is_array($meta)) {
                $description['comment'] = '';
            } else {
                $meta = [];
            }
            $description['meta'] = $meta;

            $columns[$val['field']] = $description;
        }

        return $columns;
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        $keyName   = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) && ($this->conn->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_FIX_CASE)) {
            if ($this->conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName   = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName   = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table   = $this->conn->quoteIdentifier($table, true);
        $query   = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->conn->fetchAssoc($query);


        $result = [];
        foreach ($indexes as $indexData) {
            if ($indexData[$nonUnique] && ($index = $this->conn->formatter->fixIndexName($indexData[$keyName]))) {
                $result[] = $index;
            }
        }
        return $result;
    }

    /**
     * lists tables
     *
     * @param  string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        return $this->conn->fetchColumn($this->sql['listTables']);
    }

    /**
     * lists database views
     *
     * @param  string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        if ($database === null) {
            $query = 'SELECT table_name FROM information_schema.VIEWS';
        } else {
            $query = sprintf($this->sql['listViews'], ' FROM ' . $database);
        }

        return $this->conn->fetchColumn($query);
    }
}
