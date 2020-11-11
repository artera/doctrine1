<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC136Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC136_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            $user           = new \Ticket_DC136_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->save();
            $id = $user->id;

            $table = \Doctrine::getTable('Ticket_DC136_User');

            $user1           = $table->find($id);
            $user1->username = 'jonwage';

            $user2 = $table->find($id);
            $this->assertEquals($user2->getModified(), []);
        }
    }
}

namespace {
    class Ticket_DC136_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
