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
            $original = \Doctrine1\Manager::getInstance()->getAttribute(\Doctrine1\Core::ATTR_VALIDATE);
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

            $test       = new \Ticket_1244_Test();
            $test->test = null;
            $test->save();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, $original);
        }
    }
}

namespace {
    class Ticket_1244_Test extends \Doctrine1\Record
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
