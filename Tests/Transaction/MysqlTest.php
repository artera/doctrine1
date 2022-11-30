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
            static::$transaction = new \Doctrine1\Transaction\Mysql();
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
            $this->expectException(\Doctrine1\Transaction\Exception::class);
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
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        }

        public function testNestedSavepoints(): void
        {
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
            static::$transaction->beginTransaction();
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
            static::$transaction->beginTransaction('point 1');
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::BUSY);
            static::$transaction->beginTransaction('point 2');
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::BUSY);
            static::$transaction->commit('point 2');
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::BUSY);
            static::$transaction->commit('point 1');
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
            static::$transaction->commit();
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        }

        public function testRollbackSavepointListenersGetInvoked(): void
        {
            static::$transaction->beginTransaction('point');
            static::$transaction->rollback('point');

            $this->assertEquals(static::$listener->pop(), 'postSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'preSavepointRollback');
            $this->assertEquals(static::$listener->pop(), 'postSavepointCreate');
            $this->assertEquals(static::$listener->pop(), 'preSavepointCreate');
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);

            static::$listener = new \Doctrine1\Eventlistener();
            static::$conn->setListener(static::$listener);
        }
    }
}

namespace {
    class TransactionListener extends \Doctrine1\EventListener
    {
        protected $messages = [];

        public function preTransactionCommit(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionCommit(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preTransactionRollback(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionRollback(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preTransactionBegin(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postTransactionBegin(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }


        public function preSavepointCommit(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postSavepointCommit(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preSavepointRollback(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }
        public function postSavepointRollback(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function preSavepointCreate(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function postSavepointCreate(\Doctrine1\Event $event)
        {
            $this->messages[] = __FUNCTION__;
        }

        public function pop()
        {
            return array_pop($this->messages);
        }
    }
}
