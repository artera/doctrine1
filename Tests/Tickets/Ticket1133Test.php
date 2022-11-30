<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1133Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1133_Foo';
            static::$tables[] = 'Ticket_1133_Bar';
            parent::prepareTables();
        }

        public function testTest()
        {
            $foo            = new \Ticket_1133_Foo();
            $foo->name      = 'test';
            $foo->Bar = new \Ticket_1133_Bar();
            $foo->Bar->name = 'test2';
            $foo->save();

            $q = \Doctrine1\Query::create()
            ->from('Ticket_1133_Foo f')
            ->innerJoin('f.Bar b ON b.id = ?', $foo->Bar->id)
            ->addWhere('f.name = ?', 'test');

            $this->assertEquals($q->count(), 1);
        }

        public function testTest2()
        {
            $foo            = new \Ticket_1133_Foo();
            $foo->name      = 'test';
            $foo->Bar = new \Ticket_1133_Bar();
            $foo->Bar->name = 'test2';
            $foo->save();

            $q = \Doctrine1\Query::create()
            ->from('Ticket_1133_Foo f')
            ->innerJoin('f.Bar b')
            ->addWhere('b.name = ?', 'test2')
            ->limit(1)
            ->offset(1);

            $this->assertEquals($q->count(), 2);
            $this->assertEquals($q->execute()->count(), 1);
        }
    }
}

namespace {
    class Ticket_1133_Foo extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('bar_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_1133_Bar as Bar', ['local' => 'bar_id', 'foreign' => 'id']);
        }
    }

    class Ticket_1133_Bar extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }
    }
}
