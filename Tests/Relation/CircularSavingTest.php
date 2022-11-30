<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class CircularSavingTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['NestTest', 'NestReference'];

    public function testMultiplePrimaryKeys()
    {
        $r               = new \Doctrine1\Collection('NestReference');
        $r[0]->parent_id = 1;
        $r[0]->child_id  = 2;
        $r[1]->parent_id = 2;
        $r[1]->child_id  = 3;
        $r->save();

        $r->delete();
        static::$conn->clear();
        $q    = new \Doctrine1\Query();
        $coll = $q->from('NestReference')->execute();
        $this->assertEquals(count($coll), 0);
    }

    public function testCircularNonEqualSelfReferencingRelationSaving()
    {
        $n1 = new \NestTest();
        $n1->set('name', 'node1');
        $n1->save();
        $n2 = new \NestTest();
        $n2->set('name', 'node2');
        $n2->save();

        $n1->get('Children')->add($n2);
        $n1->save();
        $n2->get('Children')->add($n1);
        $n2->save();

        $q    = new \Doctrine1\Query();
        $coll = $q->from('NestReference')->execute();

        $this->assertEquals(count($coll), 2);

        $coll->delete();
        static::$conn->clear();

        $q    = new \Doctrine1\Query();
        $coll = $q->from('NestReference')->execute();
        $this->assertEquals(count($coll), 0);
    }

    public function testCircularEqualSelfReferencingRelationSaving()
    {
        $n1 = new \NestTest();
        $n1->set('name', 'node1');
        $n1->save();
        $n2 = new \NestTest();
        $n2->set('name', 'node2');
        $n2->save();

        $n1->get('Relatives')->add($n2);
        $n1->save();
        $n2->get('Relatives')->add($n1);
        $n2->save();

        $q    = new \Doctrine1\Query();
        $coll = $q->from('NestReference')->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertEquals(count($coll), 1);
    }
}
