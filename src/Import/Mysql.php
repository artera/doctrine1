<?php

namespace Doctrine1\Import;

use Doctrine1\Casing;
use Doctrine1\Column;
use Doctrine1\Column\Type;

/**
 * @template Connection of \Doctrine1\Connection\Mysql
 * @extends \Doctrine1\Import<Connection>
 */
class Mysql extends \Doctrine1\Import
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
        if ($this->conn->fieldCase && ($this->conn->getPortability() & \Doctrine1\Core::PORTABILITY_FIX_CASE)) {
            if ($this->conn->fieldCase === Casing::Lower) {
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

    public function listTableRelations(string $tableName): array
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

    public function listTableColumns(string $table): array
    {
        $sql    = 'SHOW FULL COLUMNS FROM ' . $this->conn->quoteIdentifier($table, true);
        $result = $this->conn->fetchAssoc($sql);
        /** @var \Doctrine1\DataDict\Mysql $dataDict */
        $dataDict = $this->conn->dataDict;

        $columns     = [];
        foreach ($result as $val) {
            $val = array_change_key_case($val, CASE_LOWER);

            $decl     = $dataDict->getPortableDeclaration($val);
            $meta = json_decode($val['comment'], true);

            $columns[] = new Column(
                $val['field'],
                Type::fromNative($decl['type'][0]),
                // alltypes: $decl['type'],
                length: $decl['length'],
                fixed: (bool) $decl['fixed'],
                unsigned: (bool) $decl['unsigned'],
                values: $decl['values'] ?? [],
                primary: strtolower($val['key']) === 'pri',
                default: $val['default'] === 'CURRENT_TIMESTAMP' ? null : $val['default'],
                notnull: $val['null'] !== 'YES',
                autoincrement: strpos($val['extra'], 'auto_increment') !== false,
                virtual: strpos($val['extra'], 'VIRTUAL') !== false,
                scale: $decl['scale'] ?? 0,
                meta: is_array($meta) ? $meta : [],
                comment: is_array($meta) ? '' : $val['comment'],
            );
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
        if ($this->conn->fieldCase && ($this->conn->getPortability() & \Doctrine1\Core::PORTABILITY_FIX_CASE)) {
            if ($this->conn->fieldCase === Casing::Lower) {
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
