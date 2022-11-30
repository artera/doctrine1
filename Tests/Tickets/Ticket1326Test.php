<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1326Test extends DoctrineUnitTestCase
{
    public function createTestData()
    {
        $user1 = new \User();
        $user1->set('loginname', 'foo2');
        $user1->save();

        $user2 = new \User();
        $user2->set('loginname', 'foo3');
        $user2->save();
    }

    public function testTest()
    {
        $this->createTestData();
        \Doctrine1\Query::create()->delete()->from('User')->execute();

        $this->createTestData();
        $this->assertEquals(\Doctrine1\Query::create()->from('User')->count(), 2);

        $nbDeleted = \Doctrine1\Query::create()->delete()->from('User')->execute();
        $this->assertEquals($nbDeleted, 2);
        $this->assertEquals(\Doctrine1\Query::create()->from('User')->count(), 0);

        $this->createTestData();
        $this->assertEquals(\Doctrine1\Query::create()->from('User')->count(), 2);

        $nbDeleted = \Doctrine1\Query::create()->delete()->from('User')->where('loginname = ?', ['foo2'])->execute();
        $this->assertEquals($nbDeleted, 1);
    }
}
