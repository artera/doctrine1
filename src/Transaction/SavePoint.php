<?php

namespace Doctrine1\Transaction;

use Doctrine_Collection as Collection;
use Doctrine_Event as Event;
use Doctrine_Record as Record;
use Doctrine_Transaction as Transaction;

class SavePoint
{
    /** @var Collection[] */
    protected array $collections = [];

    /** @var Record[] */
    protected array $invalid = [];

    public function __construct(
        protected Transaction $tr,
        protected ?string $name,
        protected bool $internal,
    ) {
        $conn = $this->tr->getConnection();
        $listener = $conn->getAttribute(\Doctrine_Core::ATTR_LISTENER);

        if ($this->name === null) {
            $event = new Event($this->tr, Event::TX_BEGIN);
            $listener->preTransactionBegin($event);
            try {
                $conn->getDbh()->beginTransaction();
            } catch (\Throwable $e) {
                throw new \Doctrine_Transaction_Exception($e->getMessage());
            }
            $listener->postTransactionBegin($event);
        } else {
            $event = new Event($this->tr, Event::SAVEPOINT_CREATE);
            $listener->preSavepointCreate($event);

            $query = 'SAVEPOINT ' . $conn->quoteIdentifier($this->name);
            $conn->execute($query);

            $listener->postSavepointCreate($event);
        }
    }

    /**
     * adds a collection in the internal array of collections
     *
     * at the end of each commit this array is looped over and
     * of every collection Doctrine then takes a snapshot in order
     * to keep the collections up to date with the database
     *
     * @param  Collection $collection a collection to be added
     */
    public function addCollection(Collection $collection): void
    {
        $this->collections[] = $collection;
    }

    public function takeSnapshots(): void
    {
        foreach ($this->collections as $collection) {
            $collection->takeSnapshot();
        }
        $this->collections = [];
    }

    public function addInvalid(Record $record): void
    {
        $this->invalid[] = $record;
    }

    /** @return Record[] */
    public function getInvalids(): array
    {
        return array_splice($this->invalid, 0);
    }

    public function commit(): void
    {
        $this->tr->commitSavePointStack($this);
        $conn = $this->tr->getConnection();
        $conn->connect();
        $listener = $conn->getAttribute(\Doctrine_Core::ATTR_LISTENER);

        if ($this->name === null) {
            $event = new Event($this->tr, Event::TX_COMMIT);
            $listener->preTransactionCommit($event);
            $conn->getDbh()->commit();
            $listener->postTransactionCommit($event);
        } else {
            $event = new Event($this->tr, Event::SAVEPOINT_COMMIT);
            $listener->preSavepointCommit($event);

            $query = 'RELEASE SAVEPOINT ' . $conn->quoteIdentifier($this->name);
            $conn->execute($query);

            $listener->postSavepointCommit($event);
        }
    }

    public function rollback(): void
    {
        $this->tr->rollbackSavePointStack($this);
        $conn = $this->tr->getConnection();
        $conn->connect();
        $listener = $conn->getAttribute(\Doctrine_Core::ATTR_LISTENER);

        if ($this->name === null) {
            $event = new Event($this->tr, Event::TX_ROLLBACK);
            $listener->preTransactionRollback($event);
            try {
                $conn->getDbh()->rollBack();
            } catch (\Throwable $e) {
                throw new \Doctrine_Transaction_Exception($e->getMessage());
            }
            $listener->postTransactionRollback($event);
        } else {
            $event = new Event($this->tr, Event::SAVEPOINT_ROLLBACK);
            $listener->preSavepointRollback($event);

            $query = 'ROLLBACK TO SAVEPOINT ' . $conn->quoteIdentifier($this->name);
            $conn->execute($query);

            $listener->postSavepointRollback($event);
        }
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }
}
