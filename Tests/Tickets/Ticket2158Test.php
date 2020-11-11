<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2158Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'T2158_Model1';
            static::$tables[] = 'T2158_Model2';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $myModel = new \T2158_Model1();
            $myModel->save();
        }

        public function testInit()
        {
        }

        // This produces a failing test
        public function testTest()
        {
            $q = \Doctrine_Core::getTable('T2158_Model2')->createQuery('m2')->leftJoin('m2.Relation m1 ON m2.id    = m1.m2_id');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t2.id AS t2__id, t2.title AS t2__title, t2.m2_id AS t2__m2_id FROM t2158__model2 t LEFT JOIN t2158__model1 t2 ON (t.id = t2.m2_id)');
            //$rs = $q->execute();
        }
    }
}

namespace {
    class T2158_Model1 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('title', 'string');
            $this->hasColumn('m2_id', 'integer');
        }
    }

    class T2158_Model2 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
        }


        public function setUp()
        {
            $this->hasMany(
                'T2158_Model1 as Relation',
                [
                'local'   => 'id',
                'foreign' => 'm2_id'
                ]
            );
        }
    }
}
