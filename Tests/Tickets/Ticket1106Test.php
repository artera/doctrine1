<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1106Test extends DoctrineUnitTestCase
{
    static int $userId;

    public static function prepareData(): void
    {
        $user                 = new \User();
        $user->name           = 'John';
        $user->Group[0]->name = 'Original Group';
        $user->save();

        static::$userId = $user['id'];
    }

    public function testAfterOriginalSave()
    {
        $user = \Doctrine1\Query::create()->from('User u, u.Group')->fetchOne();
        $this->assertEquals($user->name, 'John');
        $this->assertEquals($user->Group[0]->name, 'Original Group');
    }

    public function testModifyRelatedRecord()
    {
        $user = \Doctrine1\Query::create()->from('User u, u.Group')->fetchOne();

        // Modify Record
        $user->name           = 'Stephen';
        $user->Group[0]->name = 'New Group';

        // Test After change and before save
        $this->assertEquals($user->name, 'Stephen');
        $this->assertEquals($user->Group[0]->name, 'New Group');

        $user->save();

        // Test after save
        $this->assertEquals($user->name, 'Stephen');
        $this->assertEquals($user->Group[0]->name, 'New Group');
    }

    public function testQueryAfterSave()
    {
        $user = \Doctrine1\Core::getTable('User')->find(static::$userId);
        $this->assertEquals($user->name, 'Stephen');
        $this->assertEquals($user->Group[0]->name, 'New Group');
    }
}
