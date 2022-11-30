<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1513Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $q = \Doctrine1\Query::create()
            ->from('T1513_Class2 c2')
            ->leftJoin('c2.Classes1 c1 WITH (c1.max - c1.min) > 50');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.value AS t__value, t2.id AS t2__id, t2.min AS t2__min, t2.max AS t2__max FROM t1513__class2 t LEFT JOIN t1513__relation t3 ON (t.id = t3.c2_id) LEFT JOIN t1513__class1 t2 ON t2.id = t3.c1_id AND ((t2.max - t2.min) > 50)');
        }
    }
}

namespace {
    class T1513_Class1 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('min', 'integer');
            $this->hasColumn('max', 'integer');
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1513_Class2 as Classes2',
                ['local'       => 'c1_id',
                                                            'foreign'  => 'c2_id',
                'refClass' => 'T1513_Relation']
            );
        }
    }

    class T1513_Class2 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('value', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1513_Class1 as Classes1',
                ['local'     => 'c2_id',
                                                          'foreign'  => 'c1_id',
                'refClass' => 'T1513_Relation']
            );
        }
    }


    class T1513_Relation extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('c1_id', 'integer');
            $this->hasColumn('c2_id', 'integer');
        }
    }
}
