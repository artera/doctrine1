<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class ManyToManyTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['JC1', 'JC2', 'JC3', 'RTC1', 'RTC2', 'M2MTest', 'M2MTest2'];

    public function testManyToManyRelationWithAliasesAndCustomPKs()
    {
        $component = new \M2MTest2();

        $rel = $component->getTable()->getRelation('RTC5');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Association);

        $this->assertTrue($component->RTC5 instanceof \Doctrine_Collection);

        $rel = $component->getTable()->getRelation('JC3');

        $this->assertEquals($rel->getLocal(), 'oid');
    }
    public function testJoinComponent()
    {
        $component = new \JC3();

        $rel = $component->getTable()->getRelation('M2MTest2');
        $this->assertEquals($rel->getForeign(), 'oid');
    }

    public function testManyToManyRelationFetchingWithAliasesAndCustomPKs2()
    {
        $q = new \Doctrine_Query();

        $q->from('M2MTest2 m INNER JOIN m.JC3');

        $q->execute();
    }
    public function testManyToManyHasRelationWithAliases4()
    {
        $component = new \M2MTest();
    }

    public function testManyToManyHasRelationWithAliases3()
    {
        $component = new \M2MTest();

        $rel = $component->getTable()->getRelation('RTC3');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Association);

        $this->assertTrue($component->RTC3 instanceof \Doctrine_Collection);
    }


    public function testManyToManyHasRelationWithAliases()
    {
        $component = new \M2MTest();

        $rel = $component->getTable()->getRelation('RTC1');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Association);

        $this->assertTrue($component->RTC1 instanceof \Doctrine_Collection);
    }

    public function testManyToManyHasRelationWithAliases2()
    {
        $component = new \M2MTest();

        $rel = $component->getTable()->getRelation('RTC2');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Association);

        $this->assertTrue($component->RTC1 instanceof \Doctrine_Collection);
    }


    public function testManyToManyRelationSaving()
    {
        $component = new \M2MTest();

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';
        $component->name          = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertEquals(static::$connection->count(), ($count + 5));

        $this->assertEquals($component->RTC1->count(), 2);

        $component = $component->getTable()->find($component->id);

        $this->assertEquals($component->RTC1->count(), 2);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertEquals(static::$connection->count(), ($count + 4));

        $this->assertEquals($component->RTC2->count(), 2);

        $component = $component->getTable()->find($component->id);

        $this->assertEquals($component->RTC2->count(), 2);
    }

    public function testManyToManyRelationSaving2()
    {
        $component = new \M2MTest();

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';
        $component->name          = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertEquals(static::$connection->count(), ($count + 5));

        $this->assertEquals($component->RTC2->count(), 2);

        $component = $component->getTable()->find($component->id);

        $this->assertEquals($component->RTC2->count(), 2);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertEquals(static::$connection->count(), ($count + 3));

        $this->assertEquals($component->RTC1->count(), 2);

        $component = $component->getTable()->find($component->id);

        $this->assertEquals($component->RTC1->count(), 2);
    }

    public function testManyToManySimpleUpdate()
    {
        $component = static::$connection->getTable('M2MTest')->find(1);

        $this->assertEquals($component->name, 2);

        $component->name = 'changed name';

        $component->save();

        $this->assertEquals($component->name, 'changed name');
    }
}
