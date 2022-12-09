<?php

namespace Doctrine1;

interface EventListenerInterface
{
    public function onOpen(Connection $connection): void;
    public function preClose(Event $event): void;
    public function postClose(Event $event): void;
    public function preTransactionCommit(Event $event): void;
    public function postTransactionCommit(Event $event): void;
    public function preTransactionRollback(Event $event): void;
    public function postTransactionRollback(Event $event): void;
    public function preTransactionBegin(Event $event): void;
    public function postTransactionBegin(Event $event): void;
    public function preSavepointCommit(Event $event): void;
    public function postSavepointCommit(Event $event): void;
    public function preSavepointRollback(Event $event): void;
    public function postSavepointRollback(Event $event): void;
    public function preSavepointCreate(Event $event): void;
    public function postSavepointCreate(Event $event): void;
    public function postConnect(Event $event): void;
    public function preConnect(Event $event): void;
    public function preQuery(Event $event): void;
    public function postQuery(Event $event): void;
    public function prePrepare(Event $event): void;
    public function postPrepare(Event $event): void;
    public function preExec(Event $event): void;
    public function postExec(Event $event): void;
    public function preError(Event $event): void;
    public function postError(Event $event): void;
    public function preFetch(Event $event): void;
    public function postFetch(Event $event): void;
    public function preFetchAll(Event $event): void;
    public function postFetchAll(Event $event): void;
    public function preStmtExecute(Event $event): void;
    public function postStmtExecute(Event $event): void;
}
