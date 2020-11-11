<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1315Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);
            $userTable = \Doctrine_Core::getTable('User');
            $userTable->addRecordListener(new \Ticket_1315_Listener());

            $this->expectException(\Doctrine_Exception::class);
            $q = \Doctrine_Query::create()
                ->from('User u')
                ->execute();

            $userTable->setAttribute(\Doctrine_Core::ATTR_RECORD_LISTENER, null);

            $q = \Doctrine_Query::create()
                ->from('User u')
                ->execute();

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_USE_DQL_CALLBACKS, false);
        }
    }
}

namespace {
    class Ticket_1315_Listener extends Doctrine_Record_Listener
    {
        public function preDqlSelect(Doctrine_Event $event)
        {
            throw new \Doctrine_Exception('Test');
        }
    }
}
