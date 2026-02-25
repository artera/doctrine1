<?php

namespace Doctrine1\PDO;

use PDO;

/**
 * This class extends native PDO one but allow nested transactions
 * by using the SQL statements `SAVEPOINT', 'RELEASE SAVEPOINT' AND 'ROLLBACK SAVEPOINT'
 */
class Sqlite extends PDO\Sqlite
{
    use PDOSavepoints;
}
