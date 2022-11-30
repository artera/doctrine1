<?php

namespace Doctrine1\Transaction;

class Pgsql extends \Doctrine1\Transaction
{
    /**
     * Set the transacton isolation level.
     *
     * @param  string $isolation standard isolation level
     *                           READ UNCOMMITTED (allows
     *                           dirty reads) READ
     *                           COMMITTED (prevents
     *                           dirty reads) REPEATABLE
     *                           READ (prevents
     *                           nonrepeatable reads)
     *                           SERIALIZABLE (prevents
     *                           phantom reads)
     * @throws \PDOException                         if something fails at the PDO level
     * @throws Exception       if using unknown isolation level or unknown wait option
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
                throw new Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL ' . $isolation;
        return $this->conn->execute($query);
    }
}
