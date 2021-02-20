<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket973Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T973_Day';

            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }


        public function testTicket()
        {
            $query = \Doctrine_Query::create()
            ->from('T973_Day d')
            ->where('d.id IN(46)');
            $this->assertEquals(' FROM T973_Day d WHERE d.id IN(46)', $query->getDql());
            $this->assertEquals($query->getSqlQuery(), 'SELECT t.id AS t__id, t.number AS t__number FROM t973_days t WHERE (d.id IN(46))');
        }
    }
}

namespace {
    class T973_Day extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t973_days');
            $this->hasColumn('id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('number', 'integer');
        }
    }
}
