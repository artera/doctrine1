<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1488Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $q = \Doctrine1\Query::create()
            ->from('T1488_Class1 c1')
            ->leftJoin('c1.Classes2 c2 WITH c2.value BETWEEN c1.min AND c1.max');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.min AS t__min, t.max AS t__max, t2.id AS t2__id, t2.value AS t2__value FROM t1488__class1 t LEFT JOIN t1488__relation t3 ON (t.id = t3.c1_id) LEFT JOIN t1488__class2 t2 ON t2.id = t3.c2_id AND (t2.value BETWEEN t.min AND t.max)');
        }
    }
}

namespace {
    class T1488_Class1 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('min', 'integer');
            $this->hasColumn('max', 'integer');
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1488_Class2 as Classes2',
                ['local'       => 'c1_id',
                                                            'foreign'  => 'c2_id',
                'refClass' => 'T1488_Relation']
            );
        }
    }

    class T1488_Class2 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('value', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1488_Class1 as Classes1',
                ['local'     => 'c2_id',
                                                          'foreign'  => 'c1_id',
                'refClass' => 'T1488_Relation']
            );
        }
    }


    class T1488_Relation extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('c1_id', 'integer');
            $this->hasColumn('c2_id', 'integer');
        }
    }
}
