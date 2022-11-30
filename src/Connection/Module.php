<?php

namespace Doctrine1\Connection;

/**
 * @template T of \Doctrine1\Connection
 */
class Module
{
    /** @phpstan-var T */
    protected \Doctrine1\Connection $conn;

    /** @phpstan-param T|null $conn */
    public function __construct(?\Doctrine1\Connection $conn = null)
    {
        if ($conn === null) {
            /** @phpstan-var T */
            $conn = \Doctrine1\Manager::getInstance()->getCurrentConnection();
        }
        $this->conn = $conn;
    }

    /** @phpstan-return T */
    public function getConnection(): \Doctrine1\Connection
    {
        return $this->conn;
    }
}
