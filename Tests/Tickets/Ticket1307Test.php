<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1307Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $charset = 'test_charset';
            $collate = 'test_collate';
            $conn    = \Doctrine1\Manager::connection('sqlite::memory:');
            $conn->setCharset($charset);
            $conn->setCollate($collate);

            $userTable = \Doctrine1\Core::getTable('Ticket_1307_User');
            $this->assertEquals($charset, $userTable->charset);
            $this->assertEquals($collate, $userTable->collate);
        }
    }
}

namespace {
    class Ticket_1307_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
