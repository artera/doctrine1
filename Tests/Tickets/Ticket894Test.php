<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket894Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'T894_Day';
            parent::prepareTables();
        }


        public static function prepareData(): void
        {
        }


        public function testTicket()
        {
            $beginNumber = 1;
            $endNumber   = 3;
            $query       = \Doctrine_Query::create()
                ->from('T894_Day d')
                ->where('d.number BETWEEN ? AND ?', [$beginNumber, $endNumber]);
            $this->assertEquals(' FROM T894_Day d WHERE d.number BETWEEN ? AND ?', $query->getDql());
            $this->assertNotFalse(strstr($query->getSqlQuery(), 'BETWEEN ? AND ?'));
        }
    }
}

namespace {
    class T894_Day extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t894_days');
            $this->hasColumn('id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('number', 'integer');
        }
    }
}
