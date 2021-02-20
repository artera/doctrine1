<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1713Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Parent1713', 'Child1713A'];

        public static function prepareData(): void
        {
            $record          = new \Child1713A();
            $record['title'] = 'Child1713A';
            $record->save();
        }

        public function testInheritanceSubclasses()
        {
            $records = \Doctrine_Query::create()->query('FROM Parent1713 m');

            foreach ($records as $rec) {
                $this->assertEquals(get_class($rec), $rec['title']);
            }
        }
    }
}

namespace {
    class Parent1713 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('mytable');
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'primary'       => true,
                'autoincrement' => true,
                'notnull'       => true,
                ]
            );

            $this->hasColumn('title', 'string', 255, []);
            $this->hasColumn('PHP_TYPE as phpType', 'integer', 11, []);

            $this->setSubclasses(
                ['Child1713A' => ['phpType' => 1]]
            );
        }

        public function setUp(): void
        {
        }
    }

    class Child1713A extends Parent1713
    {
    }
}
