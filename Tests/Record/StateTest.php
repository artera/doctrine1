<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class StateTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity'];

    public static function prepareData(): void
    {
    }

    public function testAssigningAutoincId()
    {
        $user = new \User();

        $this->assertEquals($user->id, null);

        $user->name = 'zYne';

        $user->save();

        $this->assertEquals($user->id, 1);

        $user->id = 2;

        $this->assertEquals($user->id, 2);

        $user->save();
    }
}
