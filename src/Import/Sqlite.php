<?php

namespace Doctrine1\Import;

use Doctrine1\Casing;

/**
 * @template Connection of \Doctrine1\Connection\Sqlite
 * @extends \Doctrine1\Import<Connection>
 */
class Sqlite extends \Doctrine1\Import
{
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        return [];
    }

    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions()
    {
        return [];
    }

    /**
     * lists all database triggers
     *
     * @param  string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {
        return [];
    }

    /**
     * lists all database sequences
     *
     * @param  string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        $query      = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $tableNames = $this->conn->fetchColumn($query);

        $result = [];
        foreach ($tableNames as $tableName) {
            if ($sqn = $this->conn->formatter->fixSequenceName($tableName)) {
                $result[] = $sqn;
            }
        }
        if ($this->conn->fieldCase && ($this->conn->getPortability() & \Doctrine1\Core::PORTABILITY_FIX_CASE)) {
            $result = array_map(($this->conn->fieldCase === Casing::Lower ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $table = $this->conn->quote($table, 'text');

        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";

        if ($this->conn->fieldCase && ($this->conn->getPortability() & \Doctrine1\Core::PORTABILITY_FIX_CASE)) {
            $query .= 'LOWER(tbl_name) = ' . strtolower($table);
        } else {
            $query .= 'tbl_name = ' . $table;
        }
        $query .= ' AND sql NOT NULL ORDER BY name';
        $indexes = $this->conn->fetchColumn($query);

        $result = [];
        foreach ($indexes as $sql) {
            if (preg_match('/^create unique index ([^ ]+) on /i', $sql, $tmp)) {
                $index = $this->conn->formatter->fixIndexName($tmp[1]);
                if (!empty($index)) {
                    $result[$index] = true;
                }
            }
        }

        if ($this->conn->fieldCase && ($this->conn->getPortability() & \Doctrine1\Core::PORTABILITY_FIX_CASE)) {
            $result = array_change_key_case($result, $this->conn->fieldCase->value);
        }
        return array_keys($result);
    }

    /**
     * lists table constraints
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $sql    = 'PRAGMA table_info(' . $table . ')';
        $result = $this->conn->fetchAll($sql);

        $description = [];
        $columns     = [];
        foreach ($result as $key => $val) {
            $val = array_change_key_case($val, CASE_LOWER);
            /** @var \Doctrine1\DataDict\Sqlite $dataDict */
            $dataDict = $this->conn->dataDict;
            $decl     = $dataDict->getPortableDeclaration($val);

            $description = [
                    'name'          => $val['name'],
                    'type'          => $decl['type'][0],
                    'alltypes'      => $decl['type'],
                    'notnull'       => (bool) $val['notnull'],
                    'default'       => $val['dflt_value'],
                    'primary'       => (bool) $val['pk'],
                    'length'        => null,
                    'scale'         => null,
                    'precision'     => null,
                    'unsigned'      => null,
                    'autoincrement' => ($val['pk'] == 1 && $decl['type'][0] == 'integer'),
                    ];
            $columns[$val['name']] = $description;
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
        $sql = 'PRAGMA index_list(' . $table . ')';
        return $this->conn->fetchColumn($sql);
    }
    /**
     * lists tables
     *
     * @param  string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        $sql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name != 'sqlite_sequence' "
             . 'UNION ALL SELECT name FROM sqlite_temp_master '
             . "WHERE type = 'table' ORDER BY name";

        return $this->conn->fetchColumn($sql);
    }

    /**
     * lists table triggers
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        return [];
    }

    /**
     * lists table views
     *
     * @param  string $table database table name
     * @return array
     */
    public function listTableViews($table)
    {
        $query = "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
        $views = $this->conn->fetchAll($query);

        $result = [];
        foreach ($views as $row) {
            if (preg_match("/^create view .* \bfrom\b\s+\b{$table}\b /i", $row['sql'])) {
                if (!empty($row['name'])) {
                    $result[$row['name']] = true;
                }
            }
        }
        return $result;
    }

    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers()
    {
        return [];
    }

    /**
     * lists database views
     *
     * @param  string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        $query = "SELECT name FROM sqlite_master WHERE type='view' AND sql NOT NULL";

        return $this->conn->fetchColumn($query);
    }
}
