<?php
namespace Tests\Misc {
    use Tests\DoctrineUnitTestCase;

    class DbTest extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        public static function prepareTables(): void
        {
        }

        public function testInitialize()
        {
            static::$conn = \Doctrine_Manager::getInstance()->openConnection(['sqlite::memory:']);
            static::$conn->exec('CREATE TABLE entity (id INTEGER, name TEXT)');

            static::$conn->exec("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
            static::$conn->exec("INSERT INTO entity (id, name) VALUES (2, 'John')");


            $this->assertEquals(static::$conn->getAttribute(\Doctrine_Core::ATTR_DRIVER_NAME), 'sqlite');
        }

        public function testAddValidEventListener()
        {
            static::$conn->setListener(new \Doctrine_EventListener());

            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener);
            $ret = static::$conn->addListener(new \Doctrine_Connection_TestLogger());
            $this->assertTrue($ret instanceof \Doctrine_Connection);

            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener_Chain);
            $this->assertTrue(static::$conn->getListener()->get(0) instanceof \Doctrine_Connection_TestLogger);

            $ret = static::$conn->addListener(new \Doctrine_Connection_TestValidListener());
            $this->assertTrue($ret instanceof \Doctrine_Connection);

            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener_Chain);
            $this->assertTrue(static::$conn->getListener()->get(0) instanceof \Doctrine_Connection_TestLogger);
            $this->assertTrue(static::$conn->getListener()->get(1) instanceof \Doctrine_Connection_TestValidListener);

            $ret = static::$conn->addListener(new \Doctrine_EventListener_Chain(), 'chain');
            $this->assertTrue($ret instanceof \Doctrine_Connection);

            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener_Chain);
            $this->assertTrue(static::$conn->getListener()->get(0) instanceof \Doctrine_Connection_TestLogger);
            $this->assertTrue(static::$conn->getListener()->get(1) instanceof \Doctrine_Connection_TestValidListener);
            $this->assertTrue(static::$conn->getListener()->get('chain') instanceof \Doctrine_EventListener_Chain);

            // replacing

            $ret = static::$conn->addListener(new \Doctrine_EventListener_Chain(), 'chain');
            $this->assertTrue($ret instanceof \Doctrine_Connection);

            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener_Chain);
            $this->assertTrue(static::$conn->getListener()->get(0) instanceof \Doctrine_Connection_TestLogger);
            $this->assertTrue(static::$conn->getListener()->get(1) instanceof \Doctrine_Connection_TestValidListener);
            $this->assertTrue(static::$conn->getListener()->get('chain') instanceof \Doctrine_EventListener_Chain);
        }

        public function testListeningEventsWithSingleListener()
        {
            static::$conn->setListener(new \Doctrine_Connection_TestLogger());
            $listener = static::$conn->getListener();
            $stmt     = static::$conn->prepare('INSERT INTO entity (id) VALUES(?)');

            $this->assertEquals('postPrepare', $listener->pop());
            $this->assertEquals('prePrepare', $listener->pop());

            $stmt->execute([1]);

            $this->assertEquals('postStmtExecute', $listener->pop());
            $this->assertEquals('preStmtExecute', $listener->pop());

            static::$conn->exec('DELETE FROM entity');

            $this->assertEquals('postExec', $listener->pop());
            $this->assertEquals('preExec', $listener->pop());

            static::$conn->beginTransaction();

            $this->assertEquals('postTransactionBegin', $listener->pop());
            $this->assertEquals('preTransactionBegin', $listener->pop());

            static::$conn->exec('INSERT INTO entity (id) VALUES (1)');

            $this->assertEquals('postExec', $listener->pop());
            $this->assertEquals('preExec', $listener->pop());

            static::$conn->commit();

            $this->assertEquals('postTransactionCommit', $listener->pop());
            $this->assertEquals('preTransactionCommit', $listener->pop());
        }

        public function testListeningQueryEventsWithListenerChain()
        {
            static::$conn->exec('DROP TABLE entity');

            static::$conn->addListener(new \Doctrine_Connection_TestLogger());
            static::$conn->addListener(new \Doctrine_Connection_TestLogger());

            static::$conn->exec('CREATE TABLE entity (id INT)');

            $listener  = static::$conn->getListener()->get(0);
            $listener2 = static::$conn->getListener()->get(1);
            $this->assertEquals('postExec', $listener->pop());
            $this->assertEquals('preExec', $listener->pop());

            $this->assertEquals('postExec', $listener2->pop());
            $this->assertEquals('preExec', $listener2->pop());
        }

        public function testListeningPrepareEventsWithListenerChain()
        {
            $stmt      = static::$conn->prepare('INSERT INTO entity (id) VALUES(?)');
            $listener  = static::$conn->getListener()->get(0);
            $listener2 = static::$conn->getListener()->get(1);
            $this->assertEquals('postPrepare', $listener->pop());
            $this->assertEquals('prePrepare', $listener->pop());

            $this->assertEquals('postPrepare', $listener2->pop());
            $this->assertEquals('prePrepare', $listener2->pop());

            $stmt->execute([1]);

            $this->assertEquals('postStmtExecute', $listener->pop());
            $this->assertEquals('preStmtExecute', $listener->pop());

            $this->assertEquals('postStmtExecute', $listener2->pop());
            $this->assertEquals('preStmtExecute', $listener2->pop());
        }

        public function testListeningExecEventsWithListenerChain()
        {
            static::$conn->exec('DELETE FROM entity');
            $listener  = static::$conn->getListener()->get(0);
            $listener2 = static::$conn->getListener()->get(1);
            $this->assertEquals('postExec', $listener->pop());
            $this->assertEquals('preExec', $listener->pop());

            $this->assertEquals('postExec', $listener2->pop());
            $this->assertEquals('preExec', $listener2->pop());
        }

        public function testListeningTransactionEventsWithListenerChain()
        {
            static::$conn->beginTransaction();
            $listener  = static::$conn->getListener()->get(0);
            $listener2 = static::$conn->getListener()->get(1);
            $this->assertEquals('postTransactionBegin', $listener->pop());
            $this->assertEquals('preTransactionBegin', $listener->pop());

            $this->assertEquals('postTransactionBegin', $listener2->pop());
            $this->assertEquals('preTransactionBegin', $listener2->pop());

            static::$conn->exec('INSERT INTO entity (id) VALUES (1)');

            static::$conn->commit();

            $this->assertEquals('postTransactionCommit', $listener->pop());
            $this->assertEquals('preTransactionCommit', $listener->pop());

            $this->assertEquals('postExec', $listener->pop());
            $this->assertEquals('preExec', $listener->pop());

            static::$conn->exec('DROP TABLE entity');
        }

        public function testSetValidEventListener()
        {
            static::$conn->setListener(new \Doctrine_Connection_TestLogger());
            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_Connection_TestLogger);

            static::$conn->setListener(new \Doctrine_Connection_TestValidListener());
            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_Connection_TestValidListener);

            static::$conn->setListener(new \Doctrine_EventListener_Chain());
            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener_Chain);

            static::$conn->setListener(new \Doctrine_EventListener());
            $this->assertTrue(static::$conn->getListener() instanceof \Doctrine_EventListener);
        }

        public function testSetInvalidEventListener()
        {
            $this->expectException(\Doctrine_EventListener_Exception::class);
            static::$conn->setListener(new \Doctrine_Connection_TestInvalidListener());
        }

        public function testInvalidDSN1()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection('');
        }

        public function testInvalidDSN2()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection('unknown');
        }

        public function testInvalidDSN3()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection(0);
        }

        public function testInvalidScheme()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection('unknown://:memory:');
        }
        public function testInvalidHost()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection('mysql://user:password@');
        }
        public function testInvalidDatabase()
        {
            $manager = \Doctrine_Manager::getInstance();
            $this->expectException(\Doctrine_Exception::class);
            static::$conn = $manager->openConnection('mysql://user:password@host/');
        }
    }
}

namespace {
    class Doctrine_Connection_TestLogger implements Doctrine_Overloadable
    {
        private $messages = [];

        public function __call($m, $a)
        {
            $this->messages[] = $m;
        }

        public function clear()
        {
            $this->messages = [];
        }

        public function pop()
        {
            return array_pop($this->messages);
        }

        public function getAll()
        {
            return $this->messages;
        }
    }

    class Doctrine_Connection_TestValidListener extends Doctrine_EventListener
    {
    }

    class Doctrine_Connection_TestInvalidListener
    {
    }
}
