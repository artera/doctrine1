<?php
namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class CustomPrimaryKeyTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['CustomPK'];

    public function testOperations()
    {
        $c = new \CustomPK();
        $this->assertTrue($c instanceof \Doctrine1\Record);

        $c->name = 'custom pk test';
        $this->assertEquals($c->identifier(), []);

        $c->save();
        $this->assertEquals($c->identifier(), ['uid' => 1]);
        static::$connection->clear();

        $c = static::$connection->getTable('CustomPK')->find(1);

        $this->assertEquals($c->identifier(), ['uid' => 1]);
    }
}
