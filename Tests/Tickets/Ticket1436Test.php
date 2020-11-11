<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1436Test extends DoctrineUnitTestCase
{
    protected static int $group_one;
    protected static int $group_two;
    protected static int $group_three;

    public static function prepareData(): void
    {
        $user       = new \User();
        $user->name = 'John';
        $user->save();

        // Create existing groups
        $group       = new \Group();
        $group->name = 'Group One';
        $group->save();
        static::$group_one = $group['id'];

        $group       = new \Group();
        $group->name = 'Group Two';
        $group->save();
        static::$group_two = $group['id'];

        $group       = new \Group();
        $group->name = 'Group Three';
        $group->save();
        static::$group_three = $group['id'];
    }

    public function testSynchronizeAddMNLinks()
    {
        $user      = \Doctrine_Query::create()->from('User u')->fetchOne();
        $userArray = [
            'Group' => [
                static::$group_one,
                static::$group_two,
            ]
        ];

        $user->synchronizeWithArray($userArray);

        $user->save();
    }
    public function testSynchronizeAddMNLinksAfterSave()
    {
        $user = \Doctrine_Query::create()->from('User u, u.Group g')->fetchOne();
        $this->assertEquals($user->Group[0]->name, 'Group One');
        $this->assertEquals($user->Group[1]->name, 'Group Two');
        $this->assertTrue(!isset($user->Group[2]));
    }
    public function testSynchronizeChangeMNLinks()
    {
        $user      = \Doctrine_Query::create()->from('User u, u.Group g')->fetchOne();
        $userArray = [
            'Group' => [
                static::$group_two,
                static::$group_three,
            ]
        ];

        $user->synchronizeWithArray($userArray);

        $this->assertTrue(!isset($user->Groups));
        $user->save();

        $user->refresh();
        $user->loadReference('Group');

        $this->assertEquals($user->Group[0]->name, 'Group Two');
        $this->assertEquals($user->Group[1]->name, 'Group Three');
        $this->assertTrue(!isset($user->Group[2]));
    }

    public function testFromArray()
    {
        $user      = new \User();
        $userArray = ['Group' => [static::$group_two, static::$group_three]];
        $user->fromArray($userArray);
        $this->assertEquals($user->Group[0]->name, 'Group Two');
        $this->assertEquals($user->Group[1]->name, 'Group Three');
    }

    public function testSynchronizeMNRecordsDontDeleteAfterUnlink()
    {
        $group = \Doctrine_Core::getTable('Group')->find(static::$group_one);

        $this->assertTrue(!empty($group));
        $this->assertEquals($group->name, 'Group One');
    }
}
