<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class AccessTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity', 'User'];

    public static function prepareData(): void
    {
    }

    public function testUnset()
    {
    }

    public function testIsset()
    {
        $user = new \User();

        $this->assertTrue(isset($user->name));
        $this->assertFalse(isset($user->unknown));

        $this->assertTrue(isset($user['name']));
        $this->assertFalse(isset($user['unknown']));

        $coll = new \Doctrine1\Collection('User');

        $this->assertFalse(isset($coll[0]));
        // test repeated call
        $this->assertFalse(isset($coll[0]));
        $coll[0];

        $this->assertTrue(isset($coll[0]));
        // test repeated call
        $this->assertTrue(isset($coll[0]));
    }

    public function testOffsetMethods()
    {
        $user = new \User();
        $this->assertEquals($user['name'], null);

        $user['name'] = 'Jack';
        $this->assertEquals($user['name'], 'Jack');

        $user->save();

        $user = static::$connection->getTable('User')->find($user->identifier());
        $this->assertEquals($user->name, 'Jack');

        $user['name'] = 'Jack';
        $this->assertEquals($user['name'], 'Jack');
        $user['name'] = 'zYne';
        $this->assertEquals($user['name'], 'zYne');
    }

    public function testOverload()
    {
        $user = new \User();
        $this->assertEquals($user->name, null);

        $user->name = 'Jack';

        $this->assertEquals($user->name, 'Jack');

        $user->save();

        $user = static::$connection->getTable('User')->find($user->identifier());
        $this->assertEquals($user->name, 'Jack');

        $user->name = 'Jack';
        $this->assertEquals($user->name, 'Jack');
        $user->name = 'zYne';
        $this->assertEquals($user->name, 'zYne');
    }

    public function testSet()
    {
        $user = new \User();
        $this->assertEquals($user->get('name'), null);

        $user->set('name', 'Jack');
        $this->assertEquals($user->get('name'), 'Jack');

        $user->save();

        $user = static::$connection->getTable('User')->find($user->identifier());

        $this->assertEquals($user->get('name'), 'Jack');

        $user->set('name', 'Jack');
        $this->assertEquals($user->get('name'), 'Jack');
    }
}
