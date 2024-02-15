<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

#[RequiresPhpExtension('pgsql')]
class TicketDC521Test extends DoctrineUnitTestCase
{
    protected static array $tables = ['DC521TestModel', 'DC521IdOnlyTestModel'];
    protected static ?string $driverName = 'Pgsql';

    public static function setUpBeforeClass(): void
    {
        \Doctrine1\Manager::connection('pgsql://test:test@localhost/doctrine', 'Pgsql');
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        \Doctrine1\Manager::resetInstance();
        static::$driverName = null;
        parent::tearDownAfterClass();
    }

    public function testEmptyPgsqlAutoIncrementField()
    {
        $m = new \DC521TestModel();
        $m->save();
        $this->assertEquals($m->id, 1);
    }

    public function testIdOnlyPgsqlAutoIncrementField()
    {
        $m = new \DC521IdOnlyTestModel();
        $m->save();
        $this->assertEquals($m->id, 1);
    }

    public function testIdOnlyPgsqlAutoIncrementFieldWithDefinedValue()
    {
        $m     = new \DC521IdOnlyTestModel();
        $m->id = 9999;
        $m->save();
        $this->assertEquals($m->id, 9999);
    }

    public function testPgsqlAutoIncrementFieldWithDefinedValue()
    {
        $m     = new \DC521TestModel();
        $m->id = 9999;
        $m->save();
        $this->assertEquals($m->id, 9999);
    }

    public function testPgsqlAutoIncrementFieldWithDefinedValueAndData()
    {
        $m = new \DC521TestModel();
        $this->assertEquals($m->id, null);
        $m->id   = 111111;
        $m->data = 'testdata';
        $m->save();
        $this->assertEquals($m->id, 111111);
    }
}
