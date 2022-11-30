<?php

namespace Doctrine1\Transaction;

use PDOException;

class Sqlite extends \Doctrine1\Transaction
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
     * @throws PDOException                         if something fails at the PDO level
     * @throws \Doctrine1\Transaction\Exception       if using unknown isolation level
     */
    public function setIsolation($isolation): \Doctrine1\Connection\Statement
    {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
                $isolation = 0;
                break;
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                $isolation = 1;
                break;
            default:
                throw new \Doctrine1\Transaction\Exception('Isolation level ' . $isolation . 'is not supported.');
        }

        $query = 'PRAGMA read_uncommitted = ' . $isolation;

        return $this->conn->execute($query);
    }
}
