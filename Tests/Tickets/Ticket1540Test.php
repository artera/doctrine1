<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1540Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1540_TableName';
            parent::prepareTables();
        }

        public function testShouldNotConvertToAmpersandsInSelect()
        {
            $q = \Doctrine_Query::create()
            ->select('if(1 AND 2, 1, 2)')
            ->from('Ticket_1540_TableName t');
            $this->assertEquals($q->getSqlQuery(), 'SELECT if(1 AND 2, 1, 2) AS t__0 FROM ticket_1540__table_name t');
        }

        public function testShouldNotConvertToAmpersandsInWhere()
        {
            $q = \Doctrine_Query::create()
            ->from('Ticket_1540_TableName t')
            ->where('if(1 AND 2, 1, 2)', 1);
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id FROM ticket_1540__table_name t WHERE (if(1 AND 2, 1, 2))');
        }
    }
}

namespace {
    class Ticket_1540_TableName extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
        }

        public function setUp(): void
        {
        }
    }
}
