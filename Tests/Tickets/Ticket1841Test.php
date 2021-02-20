<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1841Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Ticket_1841_User'];

        public function testTest()
        {
            $user           = new \Ticket_1841_User();
            $user->password = 'changeme';
            $this->assertEquals($user->username, 'jwage');
        }
    }
}

namespace {
    class Ticket_1841_User extends Doctrine_Record
    {
        public function construct()
        {
            $this->username = 'jwage';
        }

        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
