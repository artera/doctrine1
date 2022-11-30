<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class InheritanceTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables = array_merge(static::$tables, ['SymfonyRecord']);
        parent::prepareTables();
    }
    public static function prepareData(): void
    {
        parent::prepareData();
    }

    public function testInit()
    {
        $record         = new \SymfonyRecord();
        $record['name'] = 'Test me';
        $record->save();
    }

    public function testInstantiatingRecordWithAbstractParents()
    {
        // load our record
        $record = \Doctrine1\Query::create()->query(
            'SELECT * FROM SymfonyRecord r',
            []
        )->getFirst();

        // did we get a record object?
        $this->assertTrue($record instanceof \SymfonyRecord);
        $this->assertTrue($record->exists());

        // does it have the appropriate parentage?
        $this->assertTrue($record instanceof \PluginSymfonyRecord);
        $this->assertTrue($record instanceof \BaseSymfonyRecord);
        $this->assertTrue($record instanceof \Doctrine1\Record);

        // does it have the expected data?
        $this->assertEquals($record['name'], 'Test me');
    }
}
