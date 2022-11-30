<?php

namespace Doctrine1\Connection;

class Mock extends \Doctrine1\Connection
{
    protected string $driverName = 'Mock';

    public function __construct(\Doctrine1\Manager $manager, \PDO|array $adapter)
    {
    }
}
