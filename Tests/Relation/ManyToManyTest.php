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

        $this->assertTrue($rel instanceof \Doctrine1\Relation\Association);

        $this->assertTrue($component->RTC5 instanceof \Doctrine1\Collection);

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
        $q = new \Doctrine1\Query();

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

        $this->assertTrue($rel instanceof \Doctrine1\Relation\Association);

        $this->assertTrue($component->RTC3 instanceof \Doctrine1\Collection);
    }


    public function testManyToManyHasRelationWithAliases()
    {
        $component = new \M2MTest();

        $rel = $component->getTable()->getRelation('RTC1');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\Association);

        $this->assertTrue($component->RTC1 instanceof \Doctrine1\Collection);
    }

    public function testManyToManyHasRelationWithAliases2()
    {
        $component = new \M2MTest();

        $rel = $component->getTable()->getRelation('RTC2');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\Association);

        $this->assertTrue($component->RTC1 instanceof \Doctrine1\Collection);
    }


    public function testManyToManyRelationSaving()
    {
        $component = new \M2MTest();

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';
        $component->name          = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertCount($count + 15, static::$connection);

        $this->assertCount(2, $component->RTC1);

        $component = $component->getTable()->find($component->id);

        $this->assertCount(2, $component->RTC1);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertCount($count + 20, static::$connection);

        $this->assertCount(2, $component->RTC2);

        $component = $component->getTable()->find($component->id);

        $this->assertCount(2, $component->RTC2);
    }

    public function testManyToManyRelationSaving2()
    {
        $component = new \M2MTest();

        $component->RTC2[0]->name = '1';
        $component->RTC2[1]->name = '2';
        $component->name          = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertCount($count + 15, static::$connection);

        $this->assertCount(2, $component->RTC2);

        $component = $component->getTable()->find($component->id);

        $this->assertCount(2, $component->RTC2);

        // check that it doesn't matter saving the other M2M components as well

        $component->RTC1[0]->name = '1';
        $component->RTC1[1]->name = '2';

        $count = static::$connection->count();

        $component->save();

        $this->assertCount($count + 23, static::$connection);

        $this->assertCount(2, $component->RTC1);

        $component = $component->getTable()->find($component->id);

        $this->assertCount(2, $component->RTC1);
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
