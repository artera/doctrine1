<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket952Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_952_Parent';
            static::$tables[] = 'Ticket_952_Child';
            parent::prepareTables();
        }

        public function testTest()
        {
            $parent                    = new \Ticket_952_Parent();
            $parent->name              = 'Parent';
            $parent->Children[0]->name = 'Child 1';
            $parent->Children[1]->name = 'Child 2';
            $parent->save();
            $parent->free(true);

            $profiler = new \Doctrine_Connection_Profiler();
            \Doctrine_Manager::connection()->setListener($profiler);

            $q = \Doctrine_Query::create()
            ->from('Ticket_952_Parent p')
            ->leftJoin('p.Children c');
            $parents = $q->execute();
            $this->assertEquals($parents[0]['Children'][0]['Parent']->name, 'Parent'); // Invoked additional queries
            $this->assertEquals($profiler->count(), 1);
        }
    }
}

namespace {
    class Ticket_952_Parent extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp()
        {
            $this->hasMany('Ticket_952_Child as Children', ['local' => 'id', 'foreign' => 'parent_id']);
        }
    }

    class Ticket_952_Child extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('parent_id', 'integer');
        }

        public function setUp()
        {
            $this->hasOne('Ticket_952_Parent as Parent', ['local' => 'parent_id', 'foreign' => 'id']);
        }
    }
}
