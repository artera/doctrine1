<?php
namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class ColumnAliasTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
        $book1       = new \Book();
        $book1->name = 'Das Boot';
        $book1->save();

        $record1               = new \ColumnAliasTest();
        $record1->alias1       = 'first';
        $record1->alias2       = 123;
        $record1->anotherField = 'camelCase';
        $record1->bookId       = $book1->id;
        $record1->save();

        $record2               = new \ColumnAliasTest();
        $record2->alias1       = 'one';
        $record2->alias2       = 456;
        $record2->anotherField = 'KoQ';
        $record2->save();

        $record2->anotherField = 'foo';
    }

    protected static array $tables = ['ColumnAliasTest', 'Book'];

    public function testAliasesAreSupportedForJoins()
    {
        $q = new \Doctrine_Query();
        $q->select('c.*, b.name')->from('ColumnAliasTest c')
            ->innerJoin('c.book b')
            ->where('c.anotherField = ?', 'camelCase');
        $result = $q->execute();
        $this->assertTrue(isset($result[0]->book));
        $this->assertEquals($result[0]->book->name, 'Das Boot');
    }

    public function testAliasesAreSupportedForArrayFetching()
    {
        $q = new \Doctrine_Query();
        $q->select('c.*, b.name')->from('ColumnAliasTest c')
            ->innerJoin('c.book b')
            ->where('c.anotherField = ?', 'camelCase')
            ->setHydrationMode(\Doctrine_Core::HYDRATE_ARRAY);
        $result = $q->execute();
        $this->assertEquals($result[0]['alias1'], 'first');
        $this->assertEquals($result[0]['alias2'], 123);
        $this->assertEquals($result[0]['anotherField'], 'camelCase');
        $this->assertTrue(isset($result[0]['book']));
        $this->assertEquals($result[0]['book']['name'], 'Das Boot');
    }

    public function testAliasesAreSupportedForRecordPropertyAccessors()
    {
        $record = new \ColumnAliasTest;
        $record->alias1 = 'someone';
        $record->alias2 = 187;

        $this->assertEquals($record->alias1, 'someone');
        $this->assertEquals($record->alias2, 187);
    }

    public function testAliasesAreSupportedForDqlSelectPart()
    {
        $q = new \Doctrine_Query();
        $q->select('c.alias1, c.alias2, c.anotherField')->from('ColumnAliasTest c');
        $coll = $q->execute();

        $this->assertEquals($coll[0]->alias1, 'first');
        $this->assertEquals($coll[0]->alias2, 123);
        $this->assertEquals($coll[0]->anotherField, 'camelCase');
    }

    public function testAliasesAreSupportedForDqlWherePart()
    {
        $q = new \Doctrine_Query();

        $q->select('c.alias1, c.alias2, c.anotherField')
            ->from('ColumnAliasTest c')
            ->where('c.anotherField = ?');

        $coll = $q->execute(['KoQ']);

        $this->assertEquals($coll[0]->alias1, 'one');
        $this->assertEquals($coll[0]->alias2, 456);
        $this->assertEquals($coll[0]->anotherField, 'KoQ');
    }

    public function testAliasesAreSupportedForDqlAggregateFunctions()
    {
        $q = new \Doctrine_Query();

        $q->select('MAX(c.alias2)')->from('ColumnAliasTest c');

        $coll = $q->execute();

        $this->assertEquals($coll[0]->MAX, 456);
    }

    public function testAliasesAreSupportedForDqlHavingPart()
    {
        $q = new \Doctrine_Query();

        $q->select('c.alias2')
            ->from('ColumnAliasTest c')
            ->groupby('c.id')
            ->having('c.alias2 > 123');

        $coll = $q->execute();

        $this->assertEquals($coll[0]->alias2, 456);
    }
}
