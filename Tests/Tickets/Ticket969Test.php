<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket969Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $d        = new \T1();
            $d->t1_id = 1;
            $d->t2_id = 1;
            $d->save();

            $d           = new \T2();
            $d->t2_id    = 1;
            $d->hello_id = 10;
            $d->save();

            for ($i = 0; $i < 10; $i++) {
                $t3           = new \T3();
                $t3->hello_id = 10;
                $t3->save();
            }
        }

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1';
            static::$tables[] = 'T2';
            static::$tables[] = 'T3';

            parent::prepareTables();
        }

        public function testTicket()
        {
            $q      = new \Doctrine_Query;
            $result = $q->select('a.*, b.*, c.*')
            ->from('T1 a')
            ->leftJoin('a.T2 b')
            ->leftJoin('b.T3 c')
            ->setHydrationMode(\Doctrine_Core::HYDRATE_ARRAY)
            ->fetchOne();

            // there are 10 rows in T3, and they all have hello_id = 10, so we should have 10 rows here
            $this->assertEquals(10, count($result['T2']['T3']));

            // now with object hydration.
            $q      = new \Doctrine_Query;
            $result = $q->select('a.*, b.*, c.*')
            ->from('T1 a')
            ->leftJoin('a.T2 b')
            ->leftJoin('b.T3 c')
            ->fetchOne();

            // test that no additional queries are executed when accessing the relations (lazy-loading).
            $queryCountBefore = static::$conn->count();
            // there are 10 rows in T3, and they all have hello_id = 10, so we should have 10 rows here
            $this->assertEquals(10, count($result['T2']['T3']));
            $this->assertEquals($queryCountBefore, static::$conn->count());
        }
    }
}

namespace {
    class T1 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('t1');
            $this->hasColumn('t1_id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('t2_id', 'integer', 3);
        }

        public function setUp()
        {
            parent :: setUp();
            $this->hasOne('T2', ['local' => 't2_id', 'foreign' => 't2_id']);
        }
    }

    class T2 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('t2');
            $this->hasColumn('t2_id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('hello_id', 'integer', 3);
        }

        public function setUp()
        {
            parent :: setUp();
            $this->hasMany('T3', ['local' => 'hello_id', 'foreign' => 'hello_id']);
        }
    }

    class T3 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('t3');
            $this->hasColumn('t3_id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('hello_id', 'integer', 3);
        }

        public function setUp()
        {
            parent :: setUp();
        }
    }
}
