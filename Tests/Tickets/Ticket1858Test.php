<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1858Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'T1858_Foo';
            parent::prepareTables();
        }

        public function testTest()
        {
            $foo_id     = 1234;
            $adjustment = -3;

            $query = \Doctrine_Query::create()
            ->update('T1858_Foo')
            ->set(
                'quantity',
                'GREATEST(CAST(quantity AS SIGNED) + :adjustment,0)',
                [':adjustment' => $adjustment]
            )
            ->where('id = :id', [':id' => $foo_id]);

            $this->assertEquals($query->getSqlQuery(), 'UPDATE t1858__foo SET quantity = GREATEST(CAST(quantity AS SIGNED + :adjustment,0)) WHERE (id = :id)');
        }
    }
}

namespace {
    class T1858_Foo extends Doctrine_Record
    {
        public $hooks = [];

        public function setTableDefinition(): void
        {
            $this->hasColumn('quantity', 'integer');
        }
    }
}
