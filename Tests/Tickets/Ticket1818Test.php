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
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);
            $foo            = new \Ticket_1818_Foo();
                $foo->Bar       = new \Ticket_1818_BarA();
                $foo->Bar->type = 'A';
                $foo->save();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1818_Foo extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('bar_id', 'integer', null, ['type' => 'integer']);
        }

        public function setUp(): void
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

    class Ticket_1818_Bar extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('type', 'string', null, ['type' => 'string']);

            $this->setSubClasses(['Ticket_1818_BarA' => ['type' => 'A'], 'Ticket_1818_BarB' => ['type' => 'B']]);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1818_Foo as Foos',
                ['local' => 'id',
                'foreign'           => 'bar_id']
            );
        }
    }
}
