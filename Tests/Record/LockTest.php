<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class LockTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables[] = 'rec1';
        static::$tables[] = 'rec2';
        parent::prepareTables();
    }

    public static function prepareData(): void
    {
    }

    public function testDeleteRecords()
    {
        $rec1                   = new \Rec1();
        $rec1->first_name       = 'Some name';
        $rec1->Account          = new \Rec2();
        $rec1->Account->address = 'Some address';
        $rec1->save();

        $rec1->delete();
    }
}
