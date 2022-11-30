<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1305Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1305_Record';

            parent::prepareTables();
        }


        public function testTicket()
        {
            $t = new \Ticket_1305_Record();
            $t->save();

            $this->assertEquals($t['name'], 'test');

            $t->name = 'foo';
            $t->save();

            $this->assertEquals($t['name'], 'foo');

            $t->name = null;
            $t->save();

            $this->assertEquals($t['name'], 'test');
        }
    }
}

namespace {
    class Ticket_1305_Record extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255, ['notnull' => true, 'default' => 'test']);
        }
    }
}
