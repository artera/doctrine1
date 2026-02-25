<?php

namespace Doctrine1\PDO;

use PDO;

/**
 * This class extends native PDO one but allow nested transactions
 * by using the SQL statements `SAVEPOINT', 'RELEASE SAVEPOINT' AND 'ROLLBACK SAVEPOINT'
 */
class Mysql extends PDO\Mysql
{
    use PDOSavepoints;
}
