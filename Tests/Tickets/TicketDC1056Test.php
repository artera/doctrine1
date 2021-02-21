<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC1056Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC1056_Test';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $r           = new \Ticket_DC1056_Test();
            $r->id       = 1;
            $r->arraycol = [1];
            $r->save();
        }

        public function testTest()
        {
            $r = \Doctrine_Query::create()->from('Ticket_DC1056_Test')->where('id = 1')->execute()->getFirst();
            preg_match('/"arraycol";a:1:{i:0;i:1;}}/', $r->serialize(), $matches);
            $this->assertEquals(1, count($matches));

            $r2 = new \Ticket_DC1056_Test();
            $r2->unserialize('a:12:{s:3:"_id";a:1:{s:2:"id";s:1:"1";}s:5:"_data";a:2:{s:2:"id";s:1:"1";s:8:"arraycol";a:1:{i:0;i:1;}}s:7:"_values";a:0:{}s:5:"state";i:3;s:13:"_lastModified";a:0:{}s:9:"_modified";a:0:{}s:10:"_oldValues";a:0:{}s:15:"_pendingDeletes";a:0:{}s:15:"_pendingUnlinks";a:0:{}s:20:"_serializeReferences";b:0;s:17:"_invokedSaveHooks";a:0:{}s:4:"_oid";i:41;}');
            $this->assertEquals([1], $r2->arraycol);
            $r2 = unserialize($r->serialize());
        }
    }
}

namespace {
    class Ticket_DC1056_Test extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary', 'notnull']);
            $this->hasColumn('arraycol', 'array');
        }
    }
}
