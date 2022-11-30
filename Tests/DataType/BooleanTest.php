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
        $test = new \BooleanTest();
        $test->is_working = false;

        $this->assertFalse($test->is_working);
        $this->assertEquals(\Doctrine1\Record\State::TDIRTY, $test->state());
        $test->save();

        $test->refresh();
        $this->assertFalse($test->is_working);
    }

    public function testSetTrue()
    {
        $test = new \BooleanTest();
        $test->is_working = true;
        $this->assertTrue($test->is_working);
        $test->save();

        $test->refresh();
        $this->assertTrue($test->is_working);

        static::$connection->clear();

        $test = $test->getTable()->find($test->id);
        $this->assertTrue($test->is_working);
    }
    public function testNormalQuerying()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 0');
        $this->assertCount(1, $ret);

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 1');

        $this->assertCount(1, $ret);
    }
    public function testPreparedQueries()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', [false]);
        $this->assertCount(1, $ret);

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', [true]);
        $this->assertCount(1, $ret);
    }
    public function testFetchingWithSmartConversion()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = false');
        $this->assertCount(1, $ret);

        $query = new \Doctrine1\Query(static::$connection);
        $ret   = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = true');

        $this->assertCount(1, $ret);
    }

    public function testSavingNullValue()
    {
        $_SERVER['test'] = true;
        $test = new \BooleanTest();
        $test->is_working = null;

        $this->assertNull($test->is_working);
        $this->assertEquals(\Doctrine1\Record\State::TDIRTY, $test->state());
        $test->save();

        $test->refresh();
        $this->assertNull($test->is_working);

        $test = new \BooleanTest();
        $test->is_working_notnull = null;

        $this->assertFalse($test->is_working_notnull);
        $this->assertEquals(\Doctrine1\Record\State::TDIRTY, $test->state());
        $test->save();

        $test->refresh();
        $this->assertFalse($test->is_working_notnull);
        $_SERVER['test'] = false;
    }
}
