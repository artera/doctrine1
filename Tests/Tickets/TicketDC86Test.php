<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC86Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC86_Test';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $r       = new \Ticket_DC86_Test();
            $r->id   = 1;
            $r->date = date('Y-m-d h:i:s', strtotime('- 10 week'));
            $r->save();

            $r       = new \Ticket_DC86_Test();
            $r->id   = 2;
            $r->date = date('Y-m-d h:i:s', strtotime('- 5 week'));
            $r->save();

            $r       = new \Ticket_DC86_Test();
            $r->id   = 3;
            $r->date = date('Y-m-d h:i:s', strtotime('+ 1 week'));
            $r->save();
        }

        public function testTest()
        {
            $past = \Doctrine_Query::create()->from('Ticket_DC86_Test')->addWhere('date < now()')->orderBy('date')->execute();
            $this->assertEquals(2, count($past));
            $this->assertEquals(1, $past[0]->id);
            $this->assertEquals(2, $past[1]->id);

            $future = \Doctrine_Query::create()->from('Ticket_DC86_Test')->addWhere('date > now()')->orderBy('date')->execute();
            $this->assertEquals(1, count($future));
            $this->assertEquals(3, $future[0]->id);
        }
    }
}

namespace {
    class Ticket_DC86_Test extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('id', 'integer', 4, ['primary', 'notnull']);
            $this->hasColumn('date', 'timestamp');
        }
    }
}
