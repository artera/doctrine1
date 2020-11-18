<?php
namespace Tests\Inheritance;

use Tests\DoctrineUnitTestCase;

class ColumnAggregationInheritanceTest extends DoctrineUnitTestCase
{
    protected static ?\Entity $otherEntity = null;

    public static function prepareData(): void
    {
        parent::prepareData();
        //we create a test entity that is not a user and not a group
        $entity       = new \Entity();
        $entity->name = 'Other Entity';
        $entity->type = 2;
        $entity->save();
        static::$otherEntity = $entity;
    }

    public function testQueriedClassReturnedIfNoSubclassMatch()
    {
        $q           = new \Doctrine_Query();
        $entityOther = $q->from('Entity')->where('id = ?')->execute([static::$otherEntity->id])->getFirst();
        $this->assertTrue($entityOther instanceof \Entity);
    }

    public function testSubclassReturnedIfInheritanceMatches()
    {
        $q     = new \Doctrine_Query();
        $group = $q->from('Entity')->where('id=?')->execute([1])->getFirst();
        $this->assertTrue($group instanceof \Group);

        $q    = new \Doctrine_Query();
        $user = $q->from('Entity')->where('id=?')->execute([5])->getFirst();
        $this->assertTrue($user instanceof \User);
    }

    public function testStringColumnInheritance()
    {
        $q = new \Doctrine_Query();
        $q->select('g.name')->from('Group g');
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 1)');
    }

    public function testSubclassFieldSetWhenCreatingNewSubclassedRecord()
    {
        $child       = new \User();
        $child->name = 'Pedro';
        $this->assertTrue(isset($child->type));

        $child->save();
        $this->assertEquals($child->type, '0');
    }
}
