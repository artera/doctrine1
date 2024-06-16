<?php

namespace Doctrine1\Connection;

class Mock extends \Doctrine1\Connection
{
    public function __construct(\Doctrine1\Manager $manager, \PDO|array $adapter)
    {
    }
}
