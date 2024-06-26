<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1251Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1251_Record';
            parent::prepareTables();
        }


        public function testAccessDataNamedField()
        {
            $t       = new \Ticket_1251_Record();
            $t->data = 'Foo';
            $t->save();

            $this->assertEquals($t->data, 'Foo');
        }
    }
}

namespace {
    class Ticket_1251_Record extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('data', 'string', 255);
        }
    }
}
