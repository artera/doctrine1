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
    class Ticket_1545_Foo extends \Doctrine1\Record
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

    class Ticket_1545_FooFilter extends \Doctrine1\Record\Filter
    {
        public function init(): void
        {
        }

        public function filterGet(\Doctrine1\Record $record, $name)
        {
            if ($name == 'b') {
                return $record->a;
            } elseif ($name == 'custom') {
                return $record->a;
            }
        }

        public function filterSet(\Doctrine1\Record $record, $name, $value)
        {
            if ($name == 'custom') {
                return $record->a = $value . '2';
            }
        }
    }
}
