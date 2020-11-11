<?php
namespace Tests\DataType;

use Tests\DoctrineUnitTestCase;

class BooleanTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['BooleanTest'];

    public function testSetFalse()
    {
        $test             = new \BooleanTest();
        $test->is_working = false;

        $this->assertSame($test->is_working, false);
        $this->assertEquals($test->state(), \Doctrine_Record::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertSame($test->is_working, false);
    }

    public function testSetTrue()
    {
        $test             = new \BooleanTest();
        $test->is_working = true;
        $this->assertSame($test->is_working, true);
        $test->save();

        $test->refresh();
        $this->assertSame($test->is_working, true);

        static::$connection->clear();

        $test = $test->getTable()->find($test->id);
        $this->assertSame($test->is_working, true);
    }
    public function testNormalQuerying()
    {
        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 0');
        $this->assertEquals(count($ret), 1);

        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 1');

        $this->assertEquals(count($ret), 1);
    }
    public function testPreparedQueries()
    {
        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', [false]);
        $this->assertEquals(count($ret), 1);

        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', [true]);
        $this->assertEquals(count($ret), 1);
    }
    public function testFetchingWithSmartConversion()
    {
        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = false');
        $this->assertEquals(count($ret), 1);

        $query = new \Doctrine_Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = true');

        $this->assertEquals(count($ret), 1);
    }

    public function testSavingNullValue()
    {
        $test             = new \BooleanTest();
        $test->is_working = null;

        $this->assertSame($test->is_working, null);
        $this->assertEquals($test->state(), \Doctrine_Record::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertSame($test->is_working, null);

        $test                     = new \BooleanTest();
        $test->is_working_notnull = null;

        $this->assertSame($test->is_working_notnull, false);
        $this->assertEquals($test->state(), \Doctrine_Record::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertSame($test->is_working_notnull, false);
    }
}
