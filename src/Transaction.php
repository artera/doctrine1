<?php

namespace Doctrine1;

use Doctrine1\Transaction\SavePoint;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 */
class Transaction extends Connection\Module
{
    /**
     * @var SavePoint[] $savePoints               an array containing all savepoints
     */
    protected array $savePoints = [];

    /**
     * returns the state of this transaction module.
     */
    public function getState(): Transaction\State
    {
        switch (count($this->savePoints)) {
            case 0:
                return Transaction\State::SLEEP;
            case 1:
                return Transaction\State::ACTIVE;
            default:
                return Transaction\State::BUSY;
        }
    }

    /**
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
     *
     * This method should only be used by userland-code to initiate transactions.
     * To initiate a transaction from inside Doctrine use {@link beginInternalTransaction()}.
     *
     * Listeners: onPreTransactionBegin, onTransactionBegin
     *
     * @param  string|null $savepoint name of a savepoint to set
     * @throws Transaction\Exception   if the transaction fails at database level
     */
    public function beginTransaction(?string $savepoint = null, bool $internal = false): SavePoint
    {
        $this->conn->connect();

        $listener = $this->conn->getListener();

        if (count($this->savePoints)) {
            // TODO: use stronger algorithm to avoid name collisions
            $savepoint ??= "DOCTRINE_TRANSACTION_LEVEL_" . count($this->savePoints);
        }

        $savepoint = new SavePoint($this, $savepoint, $internal);
        $this->savePoints[] = $savepoint;
        return $savepoint;
    }

    protected function getSavePoint(string|SavePoint|null $savepoint = null): SavePoint
    {
        if (is_string($savepoint)) {
            $name = $savepoint;
            $savepoint = null;
            foreach ($this->savePoints as $s) {
                if ($s->name() === $name) {
                    $savepoint = $s;
                    break;
                }
            }
            if ($savepoint === null) {
                throw new Transaction\Exception("Commit failed. There is no active transaction named $name.");
            }
        } elseif (!empty($this->savePoints)) {
            $savepoint = $this->savePoints[count($this->savePoints) - 1];
        }

        if ($savepoint === null) {
            throw new Transaction\Exception('Commit failed. There is no active transaction.');
        }

        return $savepoint;
    }

    /** @return SavePoint[] */
    protected function extractSavePointStack(SavePoint $savepoint): array
    {
        $idx = array_search($savepoint, $this->savePoints);
        if (!is_int($idx)) {
            return [];
        }
        return array_splice($this->savePoints, $idx);
    }

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: preTransactionCommit, postTransactionCommit
     *
     * @param  string|SavePoint|null $savepoint savepoint to release
     * @throws Transaction\Exception   if the transaction fails at database level
     * @throws Validator\Exception     if the transaction fails due to record validations
     */
    public function commit(string|SavePoint|null $savepoint = null): void
    {
        $this->getSavePoint($savepoint)->commit();
    }

    public function commitSavePointStack(SavePoint $savepoint): void
    {
        $savepoints = $this->extractSavePointStack($savepoint);
        for ($x = count($savepoints) - 1; $x > 0; $x--) {
            $savepoints[$x]->setInactive();
        }
        $this->conn->connect();

        $invalids = [];
        foreach ($savepoints as $savepoint) {
            $invalids = array_merge($savepoint->getInvalids(), $invalids);
        }
        $invalids = array_values(array_unique($invalids));

        if (!empty($invalids)) {
            throw new Validator\Exception($invalids);
        }

        if ($savepoint->name() === null) {
            foreach ($savepoints as $savepoint) {
                $savepoint->takeSnapshots();
            }
        }
    }

    /**
     * rollback
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param  string|SavePoint|null $savepoint savepoint to rollback to
     * @throws Transaction\Exception   if the rollback operation fails at database level
     * @todo   Shouldnt this method only commit a rollback if the transactionLevel is 1
     *       (STATE_ACTIVE)? Explanation: Otherwise a rollback that is triggered from inside doctrine
     *       in an (emulated) nested transaction would lead to a complete database level
     *       rollback even though the client code did not yet want to do that.
     *       In other words: if the user starts a transaction doctrine shouldnt roll it back.
     *       Doctrine should only roll back transactions started by doctrine. Thoughts?
     */
    public function rollback(string|SavePoint|null $savepoint = null, bool $all = false): void
    {
        if ($savepoint === null && $all) {
            if (empty($this->savePoints)) {
                throw new Transaction\Exception('Commit failed. There is no active transaction.');
            }
            $this->savePoints[0]->rollback();
            return;
        }

        $this->getSavePoint($savepoint)->rollback();
    }

    public function rollbackSavePointStack(SavePoint $savepoint): void
    {
        $savepoints = $this->extractSavePointStack($savepoint);
        for ($x = count($savepoints) - 1; $x > 0; $x--) {
            $savepoints[$x]->setInactive();
        }
    }

    /**
     * Set the transacton isolation level.
     * (implemented by the connection drivers)
     *
     * example:
     *
     * <code>
     * $tx->setIsolation('READ UNCOMMITTED');
     * </code>
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
     * @throws Transaction\Exception           if the feature is not supported by the driver
     * @throws \PDOException                             if something fails at the PDO level
     */
    public function setIsolation($isolation): Connection\Statement
    {
        throw new Transaction\Exception('Transaction isolation levels not supported by this driver.');
    }

    /**
     * getTransactionIsolation
     *
     * fetches the current session transaction isolation level
     *
     * note: some drivers may support setting the transaction isolation level
     * but not fetching it
     *
     * @throws Transaction\Exception           if the feature is not supported by the driver
     * @throws \PDOException                             if something fails at the PDO level
     * @return string                                   returns the current session transaction isolation level
     */
    public function getIsolation()
    {
        throw new Transaction\Exception('Fetching transaction isolation level not supported by this driver.');
    }
}
