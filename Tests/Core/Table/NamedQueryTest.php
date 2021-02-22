<?php
namespace Tests\Core\Table {
    use Tests\DoctrineUnitTestCase;

    class NamedQueryTest extends DoctrineUnitTestCase
    {
        protected static array $tables = ['MyFoo'];

        public static function prepareData(): void
        {
            $f1         = new \MyFoo();
            $f1->name   = 'jwage';
            $f1->value0 = 0;
            $f1->save();

            $f2         = new \MyFoo();
            $f2->name   = 'jonwage';
            $f2->value0 = 1;
            $f2->save();

            $f3         = new \MyFoo();
            $f3->name   = 'jonathanwage';
            $f3->value0 = 2;
            $f3->save();
        }


        public function testNamedQuerySupport()
        {
            $table = \Doctrine_Core::getTable('MyFoo');

            $this->assertEquals(
                $table->createNamedQuery('get.by.id')->getSqlQuery(),
                'SELECT m.id AS m__id, m.name AS m__name, m.value0 AS m__value0 FROM my_foo m WHERE (m.id = ?)'
            );

            $this->assertEquals(
                $table->createNamedQuery('get.by.similar.names')->getSqlQuery(),
                'SELECT m.id AS m__id, m.value0 AS m__value0 FROM my_foo m WHERE (LOWER(m.name) LIKE LOWER(?))'
            );

            $this->assertEquals($table->createNamedQuery('get.by.similar.names')->count(['%jon%wage%']), 2);

            $items = $table->find(['%jon%wage%'], name: 'get.by.similar.names');

            $this->assertEquals(count($items), 2);
        }
    }
}

namespace {
    class MyFoo extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('value0', 'integer', 4);
        }
    }


    class MyFooTable extends Doctrine_Table
    {
        public function construct()
        {
            $this->addNamedQuery('get.by.id', 'SELECT f.* FROM MyFoo f WHERE f.id = ?');
            $this->addNamedQuery(
                'get.by.similar.names',
                \Doctrine_Query::create()
                    ->select('f.id, f.value0')
                    ->from('MyFoo f')
                    ->where('LOWER(f.name) LIKE LOWER(?)')
            );
        }
    }
}
