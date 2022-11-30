<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class SaveBlankRecordTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables[] = 'MyUserGroup';
        static::$tables[] = 'MyUser';

        parent::prepareTables();
    }

    public static function prepareData(): void
    {
    }

    public function testSaveBlankRecord()
    {
        $user = new \MyUser();
        $user->state(\Doctrine1\Record\State::TDIRTY);
        $user->save();

        $this->assertTrue(isset($user['id']) && $user['id']);
    }

    public function testSaveBlankRecord2()
    {
        $group = new \MyUserGroup();
        $group->state(\Doctrine1\Record\State::TDIRTY);
        $group->save();

        $this->assertTrue(isset($group['id']) && $group['id']);
    }
}
