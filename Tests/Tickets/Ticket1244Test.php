<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1244Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1244_Test';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $original = \Doctrine_Manager::getInstance()->getAttribute(\Doctrine_Core::ATTR_VALIDATE);
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

            $test       = new \Ticket_1244_Test();
            $test->test = null;
            $test->save();

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, $original);
        }
    }
}

namespace {
    class Ticket_1244_Test extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('test', 'integer', 4, ['range' => [
                'min' => 5,
                'max' => 10,
            ]]);
        }
    }
}
