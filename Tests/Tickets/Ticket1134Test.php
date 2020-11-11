<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1134Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1134_User';
            parent::prepareTables();
        }


        public static function prepareData(): void
        {
            $user          = new \Ticket_1134_User();
            $user->is_pimp = true;
            $user->save();
        }


        public function testAfterOriginalSave()
        {
            $user = \Doctrine_Query::create()->from('Ticket_1134_User u')->fetchOne();
            $this->assertEquals($user->is_pimp, true);
        }

        public function testAfterModification()
        {
            $user          = \Doctrine_Query::create()->from('Ticket_1134_User u')->fetchOne();
            $user->is_pimp = '1';
            $this->assertEmpty($user->getModified());
        }
    }
}

namespace {
    class Ticket_1134_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('is_pimp', 'boolean', true);
        }

        public function setUp()
        {
        }
    }
}
