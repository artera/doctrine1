<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1545Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1545_Foo';
            parent::prepareTables();
        }

        public function testTest()
        {
            $foo    = new \Ticket_1545_Foo();
                $foo->a = null;
                $this->assertEquals($foo->b, null);
                $foo->custom = 'test';
                $this->assertEquals($foo->custom, $foo->b);
        }
    }
}

namespace {
    class Ticket_1545_Foo extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('a', 'string');
        }

        public function setUp(): void
        {
            $this->unshiftFilter(new \Ticket_1545_FooFilter());
        }
    }

    class Ticket_1545_FooFilter extends Doctrine_Record_Filter
    {
        public function init(): void
        {
        }

        public function filterGet(Doctrine_Record $record, $name)
        {
            if ($name == 'b') {
                return $record->a;
            } elseif ($name == 'custom') {
                return $record->a;
            }
        }

        public function filterSet(Doctrine_Record $record, $name, $value)
        {
            if ($name == 'custom') {
                return $record->a = $value . '2';
            }
        }
    }
}
