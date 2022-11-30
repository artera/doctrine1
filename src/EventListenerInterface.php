<?php

namespace Doctrine1;

interface EventListenerInterface
{
    /**
     * @return void
     */
    public function preTransactionCommit(Event $event);

    /**
     * @return void
     */
    public function postTransactionCommit(Event $event);

    /**
     * @return void
     */
    public function preTransactionRollback(Event $event);

    /**
     * @return void
     */
    public function postTransactionRollback(Event $event);

    /**
     * @return void
     */
    public function preTransactionBegin(Event $event);

    /**
     * @return void
     */
    public function postTransactionBegin(Event $event);

    /**
     * @return void
     */
    public function postConnect(Event $event);

    /**
     * @return void
     */
    public function preConnect(Event $event);

    /**
     * @return void
     */
    public function preQuery(Event $event);

    /**
     * @return void
     */
    public function postQuery(Event $event);

    /**
     * @return void
     */
    public function prePrepare(Event $event);

    /**
     * @return void
     */
    public function postPrepare(Event $event);

    /**
     * @return void
     */
    public function preExec(Event $event);

    /**
     * @return void
     */
    public function postExec(Event $event);

    /**
     * @return void
     */
    public function preError(Event $event);

    /**
     * @return void
     */
    public function postError(Event $event);

    /**
     * @return void
     */
    public function preFetch(Event $event);

    /**
     * @return void
     */
    public function postFetch(Event $event);

    /**
     * @return void
     */
    public function preFetchAll(Event $event);

    /**
     * @return void
     */
    public function postFetchAll(Event $event);

    /**
     * @return void
     */
    public function preStmtExecute(Event $event);

    /**
     * @return void
     */
    public function postStmtExecute(Event $event);
}
