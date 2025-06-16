<?php

namespace Doctrine1\Connection;

use Doctrine1\Manager;
use Doctrine1\MySQLEngine;
use PDO;
use UnexpectedValueException;

/**
 * @property \Doctrine1\DataDict\Mysql $dataDict
 */
class Mysql extends \Doctrine1\Connection
{
    /**
     * @param Manager $manager the manager object
     * @param PDO|array<string, string> $adapter database driver
     * @param null|(callable(): (PDO|array<string, string>)) $initiator
     */
    public function __construct(Manager $manager, PDO|array $adapter, ?callable $initiator = null)
    {
        $this->setDefaultMySQLEngine(MySQLEngine::InnoDB);
        $this->supported = [
            "sequences" => "emulated",
            "indexes" => true,
            "affected_rows" => true,
            "transactions" => true,
            "savepoints" => false,
            "summary_functions" => true,
            "order_by_text" => true,
            "current_id" => "emulated",
            "limit_queries" => true,
            "LOBs" => true,
            "replace" => true,
            "sub_selects" => true,
            "auto_increment" => true,
            "primary_key" => true,
            "result_introspection" => true,
            "prepared_statements" => true,
            "identifier_quoting" => true,
            "pattern_escaping" => true,
        ];

        $this->properties["string_quoting"] = [
            "start" => "'",
            "end" => "'",
            "escape" => "\\",
            "escape_pattern" => "\\",
        ];

        $this->properties["identifier_quoting"] = [
            "start" => "`",
            "end" => "`",
            "escape" => "`",
        ];

        $this->properties["sql_comments"] = [
            ["start" => "-- ", "end" => "\n", "escape" => false],
            ["start" => "#", "end" => "\n", "escape" => false],
            ["start" => "/*", "end" => "*/", "escape" => false],
        ];

        $this->properties["varchar_max_length"] = 255;

        parent::__construct($manager, $adapter, $initiator);
    }

    protected function illuminateGrammar(): \Illuminate\Database\Query\Grammars\Grammar
    {
        return new \Illuminate\Database\Query\Grammars\MySqlGrammar();
    }

    public function connect(): bool
    {
        $connected = parent::connect();
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        return $connected;
    }

    public function getDatabaseName(): string
    {
        return $this->fetchOne("SELECT DATABASE()");
    }

    public function setCharset(?string $charset): void
    {
        if ($charset !== null) {
            $query = "SET NAMES " . $this->quote($charset);
            $this->exec($query);
        }
        parent::setCharset($charset);
    }

    public function replace(\Doctrine1\Table $table, array $fields, array $keys): int
    {
        if (empty($keys)) {
            throw new UnexpectedValueException("Not specified which fields are keys");
        }

        $columns = [];
        $values = [];
        $params = [];
        foreach ($fields as $fieldName => $value) {
            $columns[] = $table->getColumnName($fieldName);
            $values[] = "?";
            $params[] = $value;
        }

        $query = "REPLACE INTO " . $this->quoteIdentifier($table->getTableName()) . " (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";

        return $this->exec($query, $params);
    }
}
