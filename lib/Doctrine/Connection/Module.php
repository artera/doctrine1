<?php

/**
 * @template T of Doctrine_Connection
 */
class Doctrine_Connection_Module
{
    /** @phpstan-var T */
    protected Doctrine_Connection $conn;

    /** @phpstan-param T|null $conn */
    public function __construct(?Doctrine_Connection $conn = null)
    {
        if ($conn === null) {
            /** @phpstan-var T */
            $conn = Doctrine_Manager::getInstance()->getCurrentConnection();
        }
        $this->conn = $conn;
    }

    /** @phpstan-return T */
    public function getConnection(): Doctrine_Connection
    {
        return $this->conn;
    }
}
