<?php

namespace Doctrine1\EventListener;

class Chain extends \Doctrine1\Access implements \Doctrine1\EventListenerInterface
{
    /**
     * @var array $listeners        an array containing all listeners
     */
    protected $listeners = [];

    /**
     * add
     * adds a listener to the chain of listeners
     *
     * @param  object $listener
     * @param  string $name
     * @return void
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
     * set
     *
     * @param  mixed                  $key
     * @param  \Doctrine1\EventListener $listener
     * @return void
     */
    public function set($key, $listener)
    {
        $this->listeners[$key] = $listener;
    }

    /**
     * onLoad
     * an event invoked when \Doctrine1\Record is being loaded from database
     *
     * @param  \Doctrine1\Record $record
     * @return void
     */
    public function onLoad(\Doctrine1\Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onLoad($record);
        }
    }

    /**
     * onPreLoad
     * an event invoked when \Doctrine1\Record is being loaded
     * from database but not yet initialized
     *
     * @param  \Doctrine1\Record $record
     * @return void
     */
    public function onPreLoad(\Doctrine1\Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreLoad($record);
        }
    }

    /**
     * onSleep
     * an event invoked when \Doctrine1\Record is serialized
     *
     * @param  \Doctrine1\Record $record
     * @return void
     */
    public function onSleep(\Doctrine1\Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onSleep($record);
        }
    }

    /**
     * onWakeUp
     * an event invoked when \Doctrine1\Record is unserialized
     *
     * @param  \Doctrine1\Record $record
     * @return void
     */
    public function onWakeUp(\Doctrine1\Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onWakeUp($record);
        }
    }

    /**
     * postClose
     * an event invoked after \Doctrine1\Connection is closed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postClose(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postClose($event);
        }
    }

    /**
     * preClose
     * an event invoked before \Doctrine1\Connection is closed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preClose(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preClose($event);
        }
    }

    /**
     * onOpen
     * an event invoked after \Doctrine1\Connection is opened
     *
     * @param  \Doctrine1\Connection $connection
     * @return void
     */
    public function onOpen(\Doctrine1\Connection $connection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onOpen($connection);
        }
    }

    /**
     * onTransactionCommit
     * an event invoked after a \Doctrine1\Connection transaction is committed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postTransactionCommit(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionCommit($event);
        }
    }

    /**
     * onPreTransactionCommit
     * an event invoked before a \Doctrine1\Connection transaction is committed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preTransactionCommit(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionCommit($event);
        }
    }

    /**
     * onTransactionRollback
     * an event invoked after a \Doctrine1\Connection transaction is being rolled back
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postTransactionRollback(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionRollback($event);
        }
    }

    /**
     * onPreTransactionRollback
     * an event invoked before a \Doctrine1\Connection transaction is being rolled back
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preTransactionRollback(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionRollback($event);
        }
    }

    /**
     * onTransactionBegin
     * an event invoked after a \Doctrine1\Connection transaction has been started
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postTransactionBegin(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionBegin($event);
        }
    }

    /**
     * onTransactionBegin
     * an event invoked before a \Doctrine1\Connection transaction is being started
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preTransactionBegin(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionBegin($event);
        }
    }

    /**
     * postSavepointCommit
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * is committed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postSavepointCommit(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointCommit($event);
        }
    }

    /**
     * preSavepointCommit
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is committed
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preSavepointCommit(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointCommit($event);
        }
    }

    /**
     * postSavepointRollback
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * is being rolled back
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postSavepointRollback(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointRollback($event);
        }
    }

    /**
     * preSavepointRollback
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is being rolled back
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preSavepointRollback(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointRollback($event);
        }
    }

    /**
     * postSavepointCreate
     * an event invoked after a \Doctrine1\Connection transaction with savepoint
     * has been started
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function postSavepointCreate(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postSavepointCreate($event);
        }
    }

    /**
     * preSavepointCreate
     * an event invoked before a \Doctrine1\Connection transaction with savepoint
     * is being started
     *
     * @param  \Doctrine1\Event $event
     * @return void
     */
    public function preSavepointCreate(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preSavepointCreate($event);
        }
    }
    // @end

    /**
     * onCollectionDelete
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
     * onCollectionDelete
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

    /**
     * @return void
     */
    public function postConnect(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postConnect($event);
        }
    }

    /**
     * @return void
     */
    public function preConnect(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preConnect($event);
        }
    }

    /**
     * @return void
     */
    public function preQuery(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preQuery($event);
        }
    }

    /**
     * @return void
     */
    public function postQuery(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postQuery($event);
        }
    }

    /**
     * @return void
     */
    public function prePrepare(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->prePrepare($event);
        }
    }

    /**
     * @return void
     */
    public function postPrepare(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postPrepare($event);
        }
    }

    /**
     * @return void
     */
    public function preExec(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preExec($event);
        }
    }

    /**
     * @return void
     */
    public function postExec(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postExec($event);
        }
    }

    /**
     * @return void
     */
    public function preError(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preError($event);
        }
    }

    /**
     * @return void
     */
    public function postError(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postError($event);
        }
    }

    /**
     * @return void
     */
    public function preFetch(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preFetch($event);
        }
    }

    /**
     * @return void
     */
    public function postFetch(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetch($event);
        }
    }

    /**
     * @return void
     */
    public function preFetchAll(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preFetchAll($event);
        }
    }

    /**
     * @return void
     */
    public function postFetchAll(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetchAll($event);
        }
    }

    /**
     * @return void
     */
    public function preStmtExecute(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preStmtExecute($event);
        }
    }

    /**
     * @return void
     */
    public function postStmtExecute(\Doctrine1\Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postStmtExecute($event);
        }
    }
}
