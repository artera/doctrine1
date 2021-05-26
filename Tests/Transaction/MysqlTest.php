<?php
namespace Tests\Transaction {
    use Tests\DoctrineUnitTestCase;

    class MysqlTest extends DoctrineUnitTestCase
    {
        protected static ?string $driverName = 'Mysql';
        protected static $transaction;

        public static function setUpBeforeClass(): void
        {
            parent::setUpBeforeClass();
            static::$transaction = new \Doctrine_Transaction_Mysql();
            static::$listener = new \TransactionListener();
            static::$conn->setListener(static::$listener);
        }

        public function testCreateSavePointExecutesSql(): void
        {
            static::$transaction->beginTransaction('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'SAVEPOINT mypoint');
        }

        public function testReleaseSavePointExecutesSql(): void
        {
            static::$transaction->commit('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'RELEASE SAVEPOINT mypoint');
        }

        public function testRollbackSavePointExecutesSql(): void
        {
            static::$transaction->beginTransaction('mypoint');
            static::$transaction->rollback('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'ROLLBACK TO SAVEPOINT mypoint');
        }

        public function testGetIsolationExecutesSql(): void
        {
            static::$transaction->getIsolation();

            $this->assertEquals(static::$adapter->pop(), 'SELECT @@tx_isolation');
        }

        public function testSetIsolationThrowsExceptionOnUnknownIsolationMode(): void
        {
            $this->expectException(\Doctrine_Transaction_Exception::class);
            static::$transaction->setIsolation('unknown');
        }

        public function testSetIsolationExecutesSql(): void
        {
            static::$transaction->setIsolation('READ UNCOMMITTED');

            $this->assertEquals(static::$adapter->pop(), 'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
        }

        public function testCreateSavepointListenersGetInvoked(): void
        {
            static::$transaction->beginTransaction('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointCreate');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCreate');
        }

        public function testCommitSavepointListenersGetInvoked(): void
        {
            static::$transaction->commit('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointCommit');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCommit');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::SLEEP());
        }

        public function testNestedSavepoints(): void
        {
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::SLEEP());
            static::$transaction->beginTransaction();
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::ACTIVE());
            static::$transaction->beginTransaction('point 1');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::BUSY());
            static::$transaction->beginTransaction('point 2');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::BUSY());
            static::$transaction->commit('point 2');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::BUSY());
            static::$transaction->commit('point 1');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::ACTIVE());
            static::$transaction->commit();
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::SLEEP());
        }

        public function testRollbackSavepointListenersGetInvoked(): void
        {
            static::$transaction->beginTransaction('point');
            static::$transaction->rollback('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'preSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'postSavepointCreate');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCreate');
            $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction_State::SLEEP());

            static::$listener = new \Doctrine_Eventlistener();
            static::$conn->setListener(static::$listener);
        }
    }
}

namespace {
    class TransactionListener extends Doctrine_EventListener
    {
        protected $messages = [];

        public function preTransactionCommit(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionCommit(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preTransactionRollback(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionRollback(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preTransactionBegin(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionBegin(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }


        public function preSavepointCommit(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postSavepointCommit(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preSavepointRollback(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postSavepointRollback(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preSavepointCreate(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function postSavepointCreate(Doctrine_Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function pop()
        {
            return array_pop($this->messages);
        }
    }
}
