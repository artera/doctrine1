<?php

namespace Doctrine1\PDO;

use PDO;

/**
 * This class extends native PDO one but allow nested transactions
 * by using the SQL statements `SAVEPOINT', 'RELEASE SAVEPOINT' AND 'ROLLBACK SAVEPOINT'
 */
class PDOExtended extends PDO
{
    use PDOSavepoints;

    public static function extendedConnect(
        string $dsn,
        ?string $username = null,
        #[\SensitiveParameter] ?string $password = null,
        ?array $options = null,
    ): static | Mysql | Pgsql | Sqlite
    {
        $driver = explode(":", $dsn, 2)[0];

        return match ($driver) {
            "sqlite" => new Sqlite($dsn, $username, $password, $options),
            "pgsql" => new Pgsql($dsn, $username, $password, $options),
            "mysql" => new Mysql($dsn, $username, $password, $options),
            default => parent::connect($dsn, $username, $password, $options),
        };
    }
}
