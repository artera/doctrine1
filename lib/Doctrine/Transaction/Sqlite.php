<?php

class Doctrine_Transaction_Sqlite extends Doctrine_Transaction
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
     * @throws Doctrine_Transaction_Exception       if using unknown isolation level
     */
    public function setIsolation($isolation): Doctrine_Connection_Statement
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
                throw new Doctrine_Transaction_Exception('Isolation level ' . $isolation . 'is not supported.');
        }

        $query = 'PRAGMA read_uncommitted = ' . $isolation;

        return $this->conn->execute($query);
    }
}
