<?php
namespace Tests\Sequence;

use Tests\DoctrineUnitTestCase;

class SequenceTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }

    public function testSequencesAreSupportedForRecords()
    {
        static::$adapter->forceLastInsertIdFail();
        $r       = new \CustomSequenceRecord;
        $r->name = 'custom seq';
        $r->save();
    }
}
