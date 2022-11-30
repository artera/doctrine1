<?php

namespace Doctrine1\Transaction;

use PDOException;

class Mysql extends \Doctrine1\Transaction
{
    /**
     * Set the transacton isolation level.
     *
     * @param string $isolation standard isolation level
     *                          READ UNCOMMITTED (allows
     *                          dirty reads) READ
     *                          COMMITTED (prevents
     *                          dirty reads) REPEATABLE
     *                          READ (prevents
     *                          nonrepeatable reads)
     *                          SERIALIZABLE (prevents
     *                          phantom reads)
     *
     * @throws \Doctrine1\Transaction\Exception           if using unknown isolation level
     * @throws PDOException                             if something fails at the PDO level
     */
    public function setIsolation($isolation): \Doctrine1\Connection\Statement
    {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                break;
            default:
                throw new \Doctrine1\Transaction\Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $isolation;

        return $this->conn->execute($query);
    }

    /**
     * getTransactionIsolation
     *
     * @return string               returns the current session transaction isolation level
     */
    public function getIsolation()
    {
        return $this->conn->fetchOne('SELECT @@tx_isolation');
    }
}
