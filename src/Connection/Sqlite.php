<?php

namespace Doctrine1\Connection;

use PDO;

class Sqlite extends \Doctrine1\Connection
{
    public function __construct(\Doctrine1\Manager $manager, PDO|array $adapter)
    {
        $this->supported = [
            "sequences" => "emulated",
            "indexes" => true,
            "affected_rows" => true,
            "summary_functions" => true,
            "order_by_text" => true,
            "current_id" => "emulated",
            "limit_queries" => true,
            "LOBs" => true,
            "replace" => true,
            "transactions" => true,
            "savepoints" => false,
            "sub_selects" => true,
            "auto_increment" => true,
            "primary_key" => true,
            "result_introspection" => false, // not implemented
            "prepared_statements" => "emulated",
            "identifier_quoting" => true,
            "pattern_escaping" => false,
        ];
        parent::__construct($manager, $adapter);

        if ($this->dbh !== null) {
            $this->dbh->sqliteCreateFunction("mod", ["\Doctrine1\Expression\Sqlite", "modImpl"], 2);
            $this->dbh->sqliteCreateFunction("concat", ["\Doctrine1\Expression\Sqlite", "concatImpl"]);
            $this->dbh->sqliteCreateFunction("md5", "md5", 1);
            $this->dbh->sqliteCreateFunction("now", ["\Doctrine1\Expression\Sqlite", "nowImpl"], 0);
        }
    }

    public function connect(): bool
    {
        if ($this->isConnected()) {
            return false;
        }

        if (parent::connect()) {
            assert($this->dbh !== null);
            $this->dbh->sqliteCreateFunction("mod", ["\Doctrine1\Expression\Sqlite", "modImpl"], 2);
            $this->dbh->sqliteCreateFunction("concat", ["\Doctrine1\Expression\Sqlite", "concatImpl"]);
            $this->dbh->sqliteCreateFunction("md5", "md5", 1);
            $this->dbh->sqliteCreateFunction("now", ["\Doctrine1\Expression\Sqlite", "nowImpl"], 0);
            return true;
        }

        return false;
    }

    public function createDatabase(): void
    {
        if (!($dsn = $this->getOption("dsn"))) {
            throw new \Doctrine1\Exception(
                "You must create your \Doctrine1\Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality"
            );
        }

        $info = $this->getManager()->parseDsn($dsn);

        $this->export->createDatabase($info["database"]);
    }

    public function dropDatabase(): void
    {
        if (!($dsn = $this->getOption("dsn"))) {
            throw new \Doctrine1\Exception(
                "You must create your \Doctrine1\Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality"
            );
        }

        $info = $this->getManager()->parseDsn($dsn);

        $this->export->dropDatabase($info["database"]);
    }
}
