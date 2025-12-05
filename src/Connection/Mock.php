<?php

namespace Doctrine1\Connection;

use Doctrine1\Manager;
use PDO;

class Mock extends \Doctrine1\Connection
{
    /**
     * @param Manager $manager the manager object
     * @param PDO|array<string, string|null> $adapter database driver
     * @param null|(callable(): (PDO|array<string, string|null>)) $initiator
     */
    public function __construct(Manager $manager, PDO|array $adapter, ?callable $initiator = null)
    {
    }
}
