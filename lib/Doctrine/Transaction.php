<?php

/**
 * @template Connection of Doctrine_Connection
 * @extends Doctrine_Connection_Module<Connection>
 */
class Doctrine_Transaction extends Doctrine_Connection_Module
{
    /**
     * The current nesting level of this transaction.
     * A nesting level of 0 means there is currently no active
     * transaction.
     */
    protected int $nestingLevel = 0;

    /**
     * The current internal nesting level of this transaction.
     * "Internal" means transactions started by Doctrine itself.
     * Therefore the internal nesting level is always
     * lower or equal to the overall nesting level.
     * A level of 0 means there is currently no active
     * transaction that was initiated by Doctrine itself.
     */
    protected int $internalNestingLevel = 0;

    /**
     * @var  array $invalid                  an array containing all invalid records within this transaction
     * @todo What about a more verbose name? $invalidRecords?
     */
    protected $invalid = [];

    /**
     * @var array $savePoints               an array containing all savepoints
     */
    protected $savePoints = [];

    /**
     * @var array $_collections             an array of Doctrine_Collection objects that were affected during the Transaction
     */
    protected $_collections = [];

    /**
     * addCollection
     * adds a collection in the internal array of collections
     *
     * at the end of each commit this array is looped over and
     * of every collection Doctrine then takes a snapshot in order
     * to keep the collections up to date with the database
     *
     * @param  Doctrine_Collection $coll a collection to be added
     * @return $this         this object
     */
    public function addCollection(Doctrine_Collection $coll)
    {
        $this->_collections[] = $coll;

        return $this;
    }

    /**
     * returns the state of this transaction module.
     */
    public function getState(): Doctrine_Transaction_State
    {
        switch ($this->nestingLevel) {
            case 0:
                return Doctrine_Transaction_State::SLEEP();
            case 1:
                return Doctrine_Transaction_State::ACTIVE();
            default:
                return Doctrine_Transaction_State::BUSY();
        }
    }

    /**
     * addInvalid
     * adds record into invalid records list
     *
     * @param  Doctrine_Record $record
     * @return boolean        false if record already existed in invalid records list,
     *                        otherwise true
     */
    public function addInvalid(Doctrine_Record $record)
    {
        if (in_array($record, $this->invalid, true)) {
            return false;
        }
        $this->invalid[] = $record;
        return true;
    }


    /**
     * Return the invalid records
     *
     * @return array An array of invalid records
     */
    public function getInvalid()
    {
        return $this->invalid;
    }

    /**
     * get the current transaction nesting level
     */
    public function getTransactionLevel(): int
    {
        return $this->nestingLevel;
    }

    public function getInternalTransactionLevel(): int
    {
        return $this->internalNestingLevel;
    }

    /**
     * beginTransaction
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
     * @param  string $savepoint name of a savepoint to set
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level
     * @return integer                          current transaction nesting level
     */
    public function beginTransaction($savepoint = null)
    {
        $this->conn->connect();

        $listener = $this->conn->getAttribute(Doctrine_Core::ATTR_LISTENER);

        if (!is_null($savepoint)) {
            $this->savePoints[] = $savepoint;

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_CREATE);
            $listener->preSavepointCreate($event);
            $this->createSavePoint($savepoint);
            $listener->postSavepointCreate($event);
        } else {
            if ($this->nestingLevel == 0) {
                $event = new Doctrine_Event($this, Doctrine_Event::TX_BEGIN);
                $listener->preTransactionBegin($event);
                try {
                    $this->_doBeginTransaction();
                } catch (Exception $e) {
                    throw new Doctrine_Transaction_Exception($e->getMessage());
                }
                $listener->postTransactionBegin($event);
            }
        }

        $level = ++$this->nestingLevel;

        return $level;
    }

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: preTransactionCommit, postTransactionCommit
     *
     * @param  string $savepoint name of a savepoint to release
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level
     * @throws Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return boolean                          false if commit couldn't be performed, true otherwise
     */
    public function commit($savepoint = null)
    {
        if ($this->nestingLevel == 0) {
            throw new Doctrine_Transaction_Exception('Commit failed. There is no active transaction.');
        }

        $this->conn->connect();

        $listener = $this->conn->getAttribute(Doctrine_Core::ATTR_LISTENER);

        if (!is_null($savepoint)) {
            $this->nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_COMMIT);
            $listener->preSavepointCommit($event);
            $this->releaseSavePoint($savepoint);
            $listener->postSavepointCommit($event);
        } else {
            if ($this->nestingLevel == 1 || $this->internalNestingLevel == 1) {
                if (!empty($this->invalid)) {
                    if ($this->internalNestingLevel == 1) {
                        $tmp           = $this->invalid;
                        $this->invalid = [];
                        throw new Doctrine_Validator_Exception($tmp);
                    }
                }
                if ($this->nestingLevel == 1) {
                    // take snapshots of all collections used within this transaction
                    foreach ($this->_collections as $coll) {
                        $coll->takeSnapshot();
                    }
                    $this->_collections = [];

                    $event = new Doctrine_Event($this, Doctrine_Event::TX_COMMIT);
                    $listener->preTransactionCommit($event);
                    $this->_doCommit();
                    $listener->postTransactionCommit($event);
                }
            }

            if ($this->nestingLevel > 0) {
                $this->nestingLevel--;
            }
            if ($this->internalNestingLevel > 0) {
                $this->internalNestingLevel--;
            }
        }

        return true;
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
     * @param  string $savepoint name of a savepoint to rollback to
     * @throws Doctrine_Transaction_Exception   if the rollback operation fails at database level
     * @return boolean                          false if rollback couldn't be performed, true otherwise
     * @todo   Shouldnt this method only commit a rollback if the transactionLevel is 1
     *       (STATE_ACTIVE)? Explanation: Otherwise a rollback that is triggered from inside doctrine
     *       in an (emulated) nested transaction would lead to a complete database level
     *       rollback even though the client code did not yet want to do that.
     *       In other words: if the user starts a transaction doctrine shouldnt roll it back.
     *       Doctrine should only roll back transactions started by doctrine. Thoughts?
     */
    public function rollback($savepoint = null)
    {
        if ($this->nestingLevel == 0) {
            throw new Doctrine_Transaction_Exception('Rollback failed. There is no active transaction.');
        }

        $this->conn->connect();

        if ($this->internalNestingLevel >= 1 && $this->nestingLevel > 1) {
            $this->internalNestingLevel--;
            $this->nestingLevel--;
            return false;
        } elseif ($this->nestingLevel > 1) {
            $this->nestingLevel--;
            return false;
        }

        $listener = $this->conn->getAttribute(Doctrine_Core::ATTR_LISTENER);

        if (!is_null($savepoint)) {
            $this->nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_ROLLBACK);
            $listener->preSavepointRollback($event);
            $this->rollbackSavePoint($savepoint);
            $listener->postSavepointRollback($event);
        } else {
            $event = new Doctrine_Event($this, Doctrine_Event::TX_ROLLBACK);
            $listener->preTransactionRollback($event);
            $this->nestingLevel         = 0;
            $this->internalNestingLevel = 0;
            try {
                $this->_doRollback();
            } catch (Exception $e) {
                throw new Doctrine_Transaction_Exception($e->getMessage());
            }
            $listener->postTransactionRollback($event);
        }

        return true;
    }

    /**
     * releaseSavePoint
     * creates a new savepoint
     *
     * @param  string $savepoint name of a savepoint to create
     */
    protected function createSavePoint($savepoint): Doctrine_Connection_Statement
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * releaseSavePoint
     * releases given savepoint
     *
     * @param  string $savepoint name of a savepoint to release
     */
    protected function releaseSavePoint($savepoint): Doctrine_Connection_Statement
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * rollbackSavePoint
     * releases given savepoint
     *
     * @param  string $savepoint name of a savepoint to rollback to
     */
    protected function rollbackSavePoint($savepoint): Doctrine_Connection_Statement
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * Performs the rollback.
     *
     * @return void
     */
    protected function _doRollback()
    {
        $this->conn->getDbh()->rollBack();
    }

    /**
     * Performs the commit.
     *
     * @return void
     */
    protected function _doCommit()
    {
        $this->conn->getDbh()->commit();
    }

    /**
     * Begins a database transaction.
     *
     * @return void
     */
    protected function _doBeginTransaction()
    {
        $this->conn->getDbh()->beginTransaction();
    }

    /**
     * removeSavePoints
     * removes a savepoint from the internal savePoints array of this transaction object
     * and all its children savepoints
     *
     * @param  string $savepoint name of the savepoint to remove
     * @return integer              removed savepoints
     */
    private function removeSavePoints($savepoint)
    {
        $this->savePoints = array_values($this->savePoints);

        $found = false;
        $i     = 0;

        foreach ($this->savePoints as $key => $sp) {
            if (!$found) {
                if ($sp === $savepoint) {
                    $found = true;
                }
            }
            if ($found) {
                $i++;
                unset($this->savePoints[$key]);
            }
        }

        return $i;
    }

    /**
     * setIsolation
     *
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
     * @throws Doctrine_Transaction_Exception           if the feature is not supported by the driver
     * @throws PDOException                             if something fails at the PDO level
     */
    public function setIsolation($isolation): Doctrine_Connection_Statement
    {
        throw new Doctrine_Transaction_Exception('Transaction isolation levels not supported by this driver.');
    }

    /**
     * getTransactionIsolation
     *
     * fetches the current session transaction isolation level
     *
     * note: some drivers may support setting the transaction isolation level
     * but not fetching it
     *
     * @throws Doctrine_Transaction_Exception           if the feature is not supported by the driver
     * @throws PDOException                             if something fails at the PDO level
     * @return string                                   returns the current session transaction isolation level
     */
    public function getIsolation()
    {
        throw new Doctrine_Transaction_Exception('Fetching transaction isolation level not supported by this driver.');
    }

    /**
     * Initiates a transaction.
     *
     * This method must only be used by Doctrine itself to initiate transactions.
     * Userland-code must use {@link beginTransaction()}.
     *
     * @param  string $savepoint
     * @return int
     */
    public function beginInternalTransaction($savepoint = null)
    {
        $this->internalNestingLevel++;
        return $this->beginTransaction($savepoint);
    }
}
