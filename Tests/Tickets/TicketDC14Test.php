<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC14Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC14_Search';
            parent::prepareTables();
        }

        public function testTest()
        {
            $q = \Doctrine_Core::getTable('Ticket_DC14_Search')
            ->createQuery('s')
            ->where('? NOT BETWEEN s.date_from AND s.date_to', '1985-09-01 00:00:00');

            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.name AS t__name, t.date_from AS t__date_from, t.date_to AS t__date_to FROM ticket__d_c14__search t WHERE (? NOT BETWEEN t.date_from AND t.date_to)');
        }
    }
}

namespace {
    class Ticket_DC14_Search extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('date_from', 'timestamp');
            $this->hasColumn('date_to', 'timestamp');
        }
    }
}
