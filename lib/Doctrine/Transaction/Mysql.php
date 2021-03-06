<?php

class Doctrine_Transaction_Mysql extends Doctrine_Transaction
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
     * @throws Doctrine_Transaction_Exception           if using unknown isolation level
     * @throws PDOException                             if something fails at the PDO level
     */
    public function setIsolation($isolation): Doctrine_Connection_Statement
    {
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                break;
            default:
                throw new Doctrine_Transaction_Exception('Isolation level ' . $isolation . ' is not supported.');
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
