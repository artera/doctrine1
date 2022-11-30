<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC74Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC74_Test';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $r        = new \Ticket_DC74_Test();
            $r->test1 = 'test1';
            $r->test2 = 'test2';
            $r->save();

            // following clear should be done automatically, as noted in DC73 ticket
            $r->getTable()->clear();
        }

        public function testTest()
        {
            // we are selecting "id" and "test1" fields and ommiting "test2"
            $r1 = \Doctrine1\Query::create()
                ->select('id, test1')
                ->from('Ticket_DC74_Test')
                ->fetchOne();

            // so we have object in PROXY state
            $this->assertEquals(\Doctrine1\Record\State::PROXY, $r1->state());

            // now we are modifing one of loaded properties "test1"
            $r1->test1 = 'testx';

            // so record is in DIRTY state
            $this->assertEquals(\Doctrine1\Record\State::DIRTY, $r1->state());

            // when accessing to not loaded field "test2" no additional loading
            // currently such loading is performed is executed only in PROXY state
            $this->assertEquals('test2', $r1->test2);
        }
    }
}

namespace {
    class Ticket_DC74_Test extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary', 'notnull', 'autoincrement']);
            $this->hasColumn('test1', 'string', 255);
            $this->hasColumn('test2', 'string', 255);
        }
    }
}
