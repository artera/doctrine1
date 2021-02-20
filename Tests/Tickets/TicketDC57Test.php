<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC57Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC57_Article';
            parent::prepareTables();
        }

        public function testTest()
        {
            $test            = new \Ticket_DC57_Article();
            $test->date      = '1985-09-01';
            $test->timestamp = '1985-09-01 00:00:00';
            $test->save();

            $test->date      = '1985-09-01';
            $test->timestamp = '1985-09-01';
            $this->assertFalse($test->isModified());
        }

        public function testOldDates()
        {
            $test            = new \Ticket_DC57_Article();
            $test->timestamp = '1776-07-04';
            $test->save();

            $test->timestamp = '1492-09-01';
            $this->assertTrue($test->isModified());
        }
    }
}

namespace {
    class Ticket_DC57_Article extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('title', 'string', 255);
            $this->hasColumn('date', 'date');
            $this->hasColumn('timestamp', 'timestamp');
        }
    }
}
