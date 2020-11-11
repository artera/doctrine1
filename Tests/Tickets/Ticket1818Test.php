<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1818Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1818_Foo';
            static::$tables[] = 'Ticket_1818_Bar';
            static::$tables[] = 'Ticket_1818_BarB';
            static::$tables[] = 'Ticket_1818_BarA';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
            $foo            = new \Ticket_1818_Foo();
                $foo->Bar       = new \Ticket_1818_BarA();
                $foo->Bar->type = 'A';
                $foo->save();
                
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1818_Foo extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('bar_id', 'integer', null, ['type' => 'integer']);
        }

        public function setUp()
        {
            $this->hasOne(
                'Ticket_1818_Bar as Bar',
                ['local' => 'bar_id',
                'foreign'                  => 'id']
            );
        }
    }

    class Ticket_1818_BarB extends Ticket_1818_Bar
    {
    }

    class Ticket_1818_BarA extends Ticket_1818_Bar
    {
    }

    class Ticket_1818_Bar extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('type', 'string', null, ['type' => 'string']);

            $this->setSubClasses(['Ticket_1818_BarA' => ['type' => 'A'], 'Ticket_1818_BarB' => ['type' => 'B']]);
        }

        public function setUp()
        {
            $this->hasMany(
                'Ticket_1818_Foo as Foos',
                ['local' => 'id',
                'foreign'           => 'bar_id']
            );
        }
    }
}
