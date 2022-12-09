<?php

namespace Doctrine1\EventListener;

class Chain extends \Doctrine1\Access implements \Doctrine1\EventListenerInterface
{
    /**
     * @var array $listeners        an array containing all listeners
     */
    protected $listeners = [];

    /**
     * adds a listener to the chain of listeners
     *
     * @param  object $listener
     * @param  string $name
     */
    public function add($listener, $name = null): void
    {
        if (!($listener instanceof \Doctrine1\EventListenerInterface)
            && !($listener instanceof \Doctrine1\Overloadable)
        ) {
            throw new \Doctrine1\EventListener\Exception("Couldn't add eventlistener. EventListeners should implement either \Doctrine1\EventListenerInterface or \Doctrine1\Overloadable");
        }
        if ($name === null) {
            $this->listeners[] = $listener;
        } else {
            $this->listeners[$name] = $listener;
        }
    }

    /**
     * returns a \Doctrine1\EventListener on success
     * and null on failure
     *
     * @param  mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->listeners[$key])) {
            return null;
        }
        return $this->listeners[$key];
    }

    /**
     * @param  mixed                  $key
     * @param  \Doctrine1\EventListener $listener
     * @return void
     */
    public function set($key, $listener)
    {
        $this->listeners[$key] = $listener;
    }

    /**
     * an event invoked when \Doctrine1\Record is being loaded from database
     */
    public function onLoad(\Doctrine1\Record $record): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onLoad($record);
        }
    }

    /**
     * an event invoked when \Doctrine1\Record is being loaded
     * from database but not yet initialized
     */
    public function onPreLoad(\Doctrine1\Record $record): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreLoad($record);
        }
    }

    /**
     * an event invoked when \Doctrine1\Record is serialized
     */
    public function onSleep(\Doctrine1\Record $record): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onSleep($record);
        }
    }

    /**
     * an event invoked when \Doctrine1\Record is unserialized
     */
    public function onWakeUp(\Doctrine1\Record $record): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onWakeUp($record);
        }
    }

    /**
     * an event invoked after \Doctrine1\Connection is closed
     */
    public function postClose(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postClose($event);
        }
    }

    /**
     * an event invoked before \Doctrine1\Connection is closed
     */
    public function preClose(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preClose($event);
        }
    }

    /**
     * an event invoked after \Doctrine1\Connection is opened
     */
    public function onOpen(\Doctrine1\Connection $connection): void
    {
        foreach ($this->listeners as $listener) {
            $listener->onOpen($connection);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction is committed
     */
    public function postTransactionCommit(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionCommit($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction is committed
     */
    public function preTransactionCommit(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionCommit($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction is being rolled back
     */
    public function postTransactionRollback(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionRollback($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction is being rolled back
     */
    public function preTransactionRollback(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionRollback($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction has been started
     */
    public function postTransactionBegin(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionBegin($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction is being started
     */
    public function preTransactionBegin(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionBegin($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * is committed
     */
    public function postSavepointCommit(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointCommit($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is committed
     */
    public function preSavepointCommit(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointCommit($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * is being rolled back
     */
    public function postSavepointRollback(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointRollback($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is being rolled back
     */
    public function preSavepointRollback(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointRollback($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * has been started
     */
    public function postSavepointCreate(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointCreate($event);
        }
    }

    /**
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is being started
     */
    public function preSavepointCreate(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointCreate($event);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Collection is being deleted
     *
     * @param  \Doctrine1\Collection $collection
     * @return void
     */
    public function onCollectionDelete(\Doctrine1\Collection $collection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onCollectionDelete($collection);
        }
    }

    /**
     * an event invoked after a \Doctrine1\Collection is being deleted
     *
     * @param  \Doctrine1\Collection $collection
     * @return void
     */
    public function onPreCollectionDelete(\Doctrine1\Collection $collection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreCollectionDelete($collection);
        }
    }

    public function postConnect(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postConnect($event);
        }
    }

    public function preConnect(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preConnect($event);
        }
    }

    public function preQuery(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preQuery($event);
        }
    }

    public function postQuery(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postQuery($event);
        }
    }

    public function prePrepare(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->prePrepare($event);
        }
    }

    public function postPrepare(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postPrepare($event);
        }
    }

    public function preExec(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preExec($event);
        }
    }

    public function postExec(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postExec($event);
        }
    }

    public function preError(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preError($event);
        }
    }

    public function postError(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postError($event);
        }
    }

    public function preFetch(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preFetch($event);
        }
    }

    public function postFetch(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetch($event);
        }
    }

    public function preFetchAll(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preFetchAll($event);
        }
    }

    public function postFetchAll(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetchAll($event);
        }
    }

    public function preStmtExecute(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->preStmtExecute($event);
        }
    }

    public function postStmtExecute(\Doctrine1\Event $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->postStmtExecute($event);
        }
    }
}
