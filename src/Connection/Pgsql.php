<?php

namespace Doctrine1\Connection;

use PDO;

class Pgsql extends \Doctrine1\Connection
{
    public function __construct(\Doctrine1\Manager $manager, PDO|array $adapter)
    {
        // initialize all driver options
        $this->supported = [
            "sequences" => true,
            "indexes" => true,
            "affected_rows" => true,
            "summary_functions" => true,
            "order_by_text" => true,
            "transactions" => true,
            "savepoints" => true,
            "current_id" => true,
            "limit_queries" => true,
            "LOBs" => true,
            "replace" => "emulated",
            "sub_selects" => true,
            "auto_increment" => "emulated",
            "primary_key" => true,
            "result_introspection" => true,
            "prepared_statements" => true,
            "identifier_quoting" => true,
            "pattern_escaping" => true,
        ];

        $this->properties["string_quoting"] = [
            "start" => "'",
            "end" => "'",
            "escape" => "'",
            "escape_pattern" => "\\",
        ];

        $this->properties["identifier_quoting"] = [
            "start" => '"',
            "end" => '"',
            "escape" => '"',
        ];
        parent::__construct($manager, $adapter);
    }

    public function setCharset(?string $charset): void
    {
        if ($charset !== null) {
            $query = "SET NAMES " . $this->quote($charset);
            $this->exec($query);
        }
        parent::setCharset($charset);
    }

    public function convertBooleans(array|string|bool|int|float|null $item): array|string|bool|int|float|null
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_bool($value)) {
                    $item[$key] = $value ? "true" : "false";
                }
            }
        } else {
            if (is_bool($item) || is_numeric($item)) {
                $item = $item ? "true" : "false";
            }
        }
        return $item;
    }

    public function modifyLimitQuery(string $query, ?int $limit = null, ?int $offset = null, bool $isManip = false): string
    {
        if ($limit > 0) {
            $query = rtrim($query);

            if (substr($query, -1) == ";") {
                $query = substr($query, 0, -1);
            }

            if ($isManip) {
                $manip = preg_replace('/^(DELETE FROM|UPDATE).*$/', '\\1', $query);
                // $match was previously undefined, setting as an empty array for static analysis
                // as PHP implicitly makes the empty array when accessed below. Behavior here probably isn't
                // working as originally expected
                $match = [];
                $from = $match[2];
                $where = $match[3];
                $query = "$manip $from WHERE ctid=(SELECT ctid FROM $from $where LIMIT $limit)";
            } else {
                $query .= " LIMIT $limit";
                if (!empty($offset)) {
                    $query .= " OFFSET $offset";
                }
            }
        }
        return $query;
    }

    /**
     * return version information about the server
     *
     * @param  bool $native determines if the raw version string should be returned
     * @return array|string an array or string with version information
     */
    public function getServerVersion(bool $native = false): array|string
    {
        $query = "SHOW SERVER_VERSION";

        $serverInfo = $this->fetchOne($query);

        if (!$native) {
            $tmp = explode(".", $serverInfo, 3);

            if (empty($tmp[2]) && isset($tmp[1]) && preg_match("/(\d+)(.*)/", $tmp[1], $tmp2)) {
                $serverInfo = [
                    "major" => $tmp[0],
                    "minor" => $tmp2[1],
                    "patch" => null,
                    "extra" => $tmp2[2],
                    "native" => $serverInfo,
                ];
            } else {
                $serverInfo = [
                    "major" => isset($tmp[0]) ? $tmp[0] : null,
                    "minor" => isset($tmp[1]) ? $tmp[1] : null,
                    "patch" => isset($tmp[2]) ? $tmp[2] : null,
                    "extra" => null,
                    "native" => $serverInfo,
                ];
            }
        }
        return $serverInfo;
    }

    public function insert(\Doctrine1\Table $table, array $fields): int
    {
        $tableName = $table->getTableName();

        // column names are specified as array keys
        $cols = [];
        // the query VALUES will contain either expresions (eg 'NOW()') or ?
        $a = [];

        foreach ($fields as $fieldName => $value) {
            if ($table->isIdentifier($fieldName) && $table->isIdentifierAutoincrement() && $value == null) {
                // Autoincrement fields should not be added to the insert statement
                // if their value is null
                unset($fields[$fieldName]);
                continue;
            }
            $cols[] = $this->quoteIdentifier($table->getColumnName($fieldName));
            if ($value instanceof \Doctrine1\Expression) {
                $a[] = $value->getSql();
                unset($fields[$fieldName]);
            } else {
                $a[] = "?";
            }
        }

        if (count($fields) == 0) {
            // Real fix #1786 and #2327 (default values when table is just 'id' as PK)
            return $this->exec("INSERT INTO " . $this->quoteIdentifier($tableName) . " " . " VALUES (DEFAULT)");
        }

        // build the statement
        $query = "INSERT INTO " . $this->quoteIdentifier($tableName) . " (" . implode(", ", $cols) . ")" . " VALUES (" . implode(", ", $a) . ")";

        return $this->exec($query, array_values($fields));
    }
}
