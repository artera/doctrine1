<?php
namespace Tests\EventListener {
    use Tests\DoctrineUnitTestCase;

    class EventListenerTest extends DoctrineUnitTestCase
    {
        private $logger;


        public static function prepareData(): void
        {
        }

        protected static array $tables = ['EventListenerTest'];

        public function testSetListener()
        {
            $this->logger = new \Doctrine_EventListener_TestLogger();

            $e = new \EventListenerTest;

            $e->getTable()->setListener($this->logger);

            $e->name = 'listener';
            $e->save();

            $this->assertEquals($e->getTable()->getListener(), $this->logger);
        }
    }
}

namespace {
    class Doctrine_EventListener_TestLogger implements Doctrine_Overloadable, Countable
    {
        private $messages = [];

        public function __call($m, $a)
        {
            $this->messages[] = $m;
        }
        public function pop()
        {
            return array_pop($this->messages);
        }
        public function clear()
        {
            $this->messages = [];
        }
        public function getAll()
        {
            return $this->messages;
        }
        public function count(): int
        {
            return count($this->messages);
        }
    }
}
