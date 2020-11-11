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

        public function testCreateSavePointExecutesSql()
        {
            static::$transaction->beginTransaction('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'SAVEPOINT mypoint');
        }

        public function testReleaseSavePointExecutesSql()
        {
            static::$transaction->commit('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'RELEASE SAVEPOINT mypoint');
        }

        public function testRollbackSavePointExecutesSql()
        {
            static::$transaction->beginTransaction('mypoint');
            static::$transaction->rollback('mypoint');

            $this->assertEquals(static::$adapter->pop(), 'ROLLBACK TO SAVEPOINT mypoint');
        }

        public function testGetIsolationExecutesSql()
        {
            static::$transaction->getIsolation();

            $this->assertEquals(static::$adapter->pop(), 'SELECT @@tx_isolation');
        }

        public function testSetIsolationThrowsExceptionOnUnknownIsolationMode()
        {
            $this->expectException(\Doctrine_Transaction_Exception::class);
            static::$transaction->setIsolation('unknown');
        }

        public function testSetIsolationExecutesSql()
        {
            static::$transaction->setIsolation('READ UNCOMMITTED');

            $this->assertEquals(static::$adapter->pop(), 'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
        }

        public function testCreateSavepointListenersGetInvoked()
        {
            static::$transaction->beginTransaction('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointCreate');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCreate');
        }

        public function testCommitSavepointListenersGetInvoked()
        {
            static::$transaction->commit('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointCommit');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCommit');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 0);
        }

        public function testNestedSavepoints()
        {
            $this->assertEquals(static::$transaction->getTransactionLevel(), 0);
            static::$transaction->beginTransaction();
            $this->assertEquals(static::$transaction->getTransactionLevel(), 1);
            static::$transaction->beginTransaction('point 1');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 2);
            static::$transaction->beginTransaction('point 2');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 3);
            static::$transaction->commit('point 2');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 2);
            static::$transaction->commit('point 1');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 1);
            static::$transaction->commit();
            $this->assertEquals(static::$transaction->getTransactionLevel(), 0);
        }

        public function testRollbackSavepointListenersGetInvoked()
        {
            static::$transaction->beginTransaction('point');
            static::$transaction->rollback('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'preSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'postSavepointCreate');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCreate');
            $this->assertEquals(static::$transaction->getTransactionLevel(), 0);

            static::$listener = new \Doctrine_Eventlistener();
            static::$conn->setListener(static::$listener);
        }
    }
}

namespace {
    class TransactionListener extends Doctrine_EventListener
    {
        protected $_messages = [];

        public function preTransactionCommit(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }
        public function postTransactionCommit(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function preTransactionRollback(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }
        public function postTransactionRollback(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function preTransactionBegin(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }
        public function postTransactionBegin(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }


        public function preSavepointCommit(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }
        public function postSavepointCommit(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function preSavepointRollback(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }
        public function postSavepointRollback(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function preSavepointCreate(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function postSavepointCreate(Doctrine_Event $event)
        {
            $this->_messages[] = __FUNCTION__;
        }

        public function pop()
        {
            return array_pop($this->_messages);
        }
    }
}
