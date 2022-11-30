<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1315Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_USE_DQL_CALLBACKS, true);
            $userTable = \Doctrine1\Core::getTable('User');
            $userTable->addRecordListener(new \Ticket_1315_Listener());

            $this->expectException(\Doctrine1\Exception::class);
            $q = \Doctrine1\Query::create()
                ->from('User u')
                ->execute();

            $userTable->setAttribute(\Doctrine1\Core::ATTR_RECORD_LISTENER, null);

            $q = \Doctrine1\Query::create()
                ->from('User u')
                ->execute();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_USE_DQL_CALLBACKS, false);
        }
    }
}

namespace {
    class Ticket_1315_Listener extends \Doctrine1\Record\Listener
    {
        public function preDqlSelect(\Doctrine1\Event $event)
        {
            throw new \Doctrine1\Exception('Test');
        }
    }
}
