<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1875Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1875_Account';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

            $account         = new \Ticket_1875_Account();
            $account->name   = 'Test';
            $account->amount = '25.99';
            $this->assertTrue($account->isValid());

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1875_Account extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('amount', 'decimal', 4, ['scale' => 2]);
        }
    }
}
