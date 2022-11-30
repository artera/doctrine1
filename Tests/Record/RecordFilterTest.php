<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class RecordFilterTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['RecordFilterTest'];

    public function testValueWrapper()
    {
        $orig = \Doctrine1\Manager::getInstance()->getAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE);
        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);

        $e = new \RecordFilterTest;
        $e->name = 'something';
        // $e->password = '123';

        // $this->assertEquals($e->get('name'), 'SOMETHING');

        // // test repeated calls
        // $this->assertEquals($e->get('name'), 'SOMETHING');
        // $this->assertEquals($e->id, null);
        // $this->assertEquals($e->rawGet('name'), 'something');
        // $this->assertEquals($e->password, '202cb962ac59075b964b07152d234b70');

        // $e->save();

        // $this->assertEquals($e->id, 1);
        // $this->assertEquals($e->name, 'SOMETHING');
        // $this->assertEquals($e->rawGet('name'), 'something');
        // $this->assertEquals($e->password, '202cb962ac59075b964b07152d234b70');

        // static::$connection->clear();

        // $e->refresh();

        // $this->assertEquals($e->id, 1);
        // $this->assertEquals($e->name, 'SOMETHING');
        // $this->assertEquals($e->rawGet('name'), 'something');
        // $this->assertEquals($e->password, '202cb962ac59075b964b07152d234b70');

        // static::$connection->clear();

        // $e = $e->getTable()->find($e->id);

        // $this->assertEquals($e->id, 1);
        // $this->assertEquals($e->name, 'SOMETHING');
        // $this->assertEquals($e->rawGet('name'), 'something');
        // $this->assertEquals($e->password, '202cb962ac59075b964b07152d234b70');

        // \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, $orig);
    }
}
