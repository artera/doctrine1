<?php

namespace Doctrine1;

/**
 * EventListener     all event listeners extend this base class
 *                            the empty methods allow child classes to only implement the methods they need to implement
 */
class EventListener implements EventListenerInterface
{
    /**
     * @return void
     */
    public function preClose(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postClose(Event $event)
    {
    }

    /**
     * @return void
     */
    public function onCollectionDelete(Collection $collection)
    {
    }
    /**
     * @return void
     */
    public function onPreCollectionDelete(Collection $collection)
    {
    }

    /**
     * @return void
     */
    public function onOpen(Connection $connection)
    {
    }

    /**
     * @return void
     */
    public function preTransactionCommit(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postTransactionCommit(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preTransactionRollback(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postTransactionRollback(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preTransactionBegin(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postTransactionBegin(Event $event)
    {
    }


    /**
     * @return void
     */
    public function preSavepointCommit(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postSavepointCommit(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preSavepointRollback(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postSavepointRollback(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preSavepointCreate(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postSavepointCreate(Event $event)
    {
    }

    /**
     * @return void
     */
    public function postConnect(Event $event)
    {
    }
    /**
     * @return void
     */
    public function preConnect(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preQuery(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postQuery(Event $event)
    {
    }

    /**
     * @return void
     */
    public function prePrepare(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postPrepare(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preExec(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postExec(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preError(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postError(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preFetch(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postFetch(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preFetchAll(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postFetchAll(Event $event)
    {
    }

    /**
     * @return void
     */
    public function preStmtExecute(Event $event)
    {
    }
    /**
     * @return void
     */
    public function postStmtExecute(Event $event)
    {
    }
}
